<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Services\SlaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpdeskCaseController extends Controller
{
    /**
     * Display the Helpdesk monitor dashboard.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status', 'all'),
            'priority' => $request->query('priority', 'all'),
            'category' => $request->query('category', 'all'),
            'department' => $request->query('department', 'all'),
        ];

        $casesQuery = HelpdeskCase::query()->filter($filters);

        // Sorting: Urgent pending first, then by latest created
        $cases = $casesQuery
            ->orderByRaw("CASE WHEN status = 'pending' AND priority = 'urgent' THEN 0 WHEN status = 'analyzing' AND priority = 'urgent' THEN 1 WHEN status = 'in_progress' AND priority = 'urgent' THEN 2 WHEN status = 'pending' THEN 3 WHEN status = 'analyzing' THEN 4 WHEN status = 'in_progress' THEN 5 ELSE 6 END")
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics calculations
        $totalCases = HelpdeskCase::count();
        $pendingCases = HelpdeskCase::where('status', 'pending')->count();
        $analyzingCases = HelpdeskCase::where('status', 'analyzing')->count();
        $inProgressCases = HelpdeskCase::where('status', 'in_progress')->count();
        $resolvedCases = HelpdeskCase::where('status', 'resolved')->count();
        $closedCases = HelpdeskCase::where('status', 'closed')->count();
        $cancelledCases = HelpdeskCase::where('status', 'cancelled')->count();
        $urgentActiveCases = HelpdeskCase::where('priority', 'urgent')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->count();
        $overdueCases = HelpdeskCase::where('sla_due_at', '<', Carbon::now())
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->count();

        // For completion rate, we can consider both resolved and closed as completed
        $completedCount = $resolvedCases + $closedCases;
        $completionRate = $totalCases > 0 ? round(($completedCount / $totalCases) * 100) : 0;

        // Filter dropdown options
        $departments = HelpdeskCase::distinct()->pluck('department')->filter()->values();
        $categories = HelpdeskCase::distinct()->pluck('category')->filter()->values();
        $technicians = ['ช่างสมศักดิ์', 'ณัฐนนท์ ไอที', 'อรรถพล บริการไอที', 'วิโรจน์ ซัพพอร์ต'];

        return view('dashboard', compact(
            'cases',
            'filters',
            'totalCases',
            'pendingCases',
            'analyzingCases',
            'inProgressCases',
            'resolvedCases',
            'closedCases',
            'cancelledCases',
            'urgentActiveCases',
            'overdueCases',
            'completionRate',
            'departments',
            'categories',
            'technicians'
        ));
    }

    /**
     * Display the full case detail and management page.
     */
    public function show(HelpdeskCase $case): View
    {
        $technicians = ['ช่างสมศักดิ์', 'ณัฐนนท์ ไอที', 'อรรถพล บริการไอที', 'วิโรจน์ ซัพพอร์ต'];

        return view('case-detail', compact('case', 'technicians'));
    }

    /**
     * Update all editable fields of a helpdesk case.
     */
    public function update(Request $request, HelpdeskCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'in:urgent,high,medium,low'],
            'status' => ['required', 'in:pending,analyzing,in_progress,resolved,closed,cancelled'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'resolution_notes' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);

        $status = $validated['status'];
        $now = Carbon::now();

        // 1. Analyzing
        if ($status === 'analyzing' && is_null($case->analyzing_at)) {
            $validated['analyzing_at'] = $now;
        } elseif ($status === 'pending') {
            $validated['analyzing_at'] = null;
        }

        // 2. In Progress
        if ($status === 'in_progress' && is_null($case->in_progress_at)) {
            $validated['in_progress_at'] = $now;
        } elseif (in_array($status, ['pending', 'analyzing'])) {
            $validated['in_progress_at'] = null;
        }

        // 3. Resolved
        if (in_array($status, ['resolved', 'closed']) && is_null($case->resolved_at)) {
            $validated['resolved_at'] = $now;
        } elseif (! in_array($status, ['resolved', 'closed'])) {
            $validated['resolved_at'] = null;
        }

        // 4. Closed
        if ($status === 'closed' && is_null($case->closed_at)) {
            $validated['closed_at'] = $now;
        } elseif ($status !== 'closed') {
            $validated['closed_at'] = null;
        }

        // 5. Cancelled
        if ($status === 'cancelled' && is_null($case->cancelled_at)) {
            $validated['cancelled_at'] = $now;
        } elseif ($status !== 'cancelled') {
            $validated['cancelled_at'] = null;
        }

        $case->update($validated);

        return redirect()->route('cases.show', $case)->with('success', "อัปเดตเคส {$case->ticket_no} เรียบร้อยแล้ว");
    }

    /**
     * Store a newly created helpdesk repair case.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'in:urgent,high,medium,low'],
            'location' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'attachment' => ['nullable', 'image', 'max:5120'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Generate next ticket number
        $nextId = (HelpdeskCase::max('id') ?? 0) + 1;
        $ticketNo = 'ITD-'.date('Y').'-'.str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

        $user = auth()->user();

        // Handle attachment upload if exists
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $companyModel = Company::where('name', $user->company)->first();
        $slaType = $companyModel ? $companyModel->sla_type : '8x5x4';
        $slaService = app(SlaService::class);

        HelpdeskCase::create([
            ...$validated,
            'ticket_no' => $ticketNo,
            'user_id' => $user->id,
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => $request->requester_phone ?? $user->phone,
            'company' => $user->company,
            'department' => $user->department,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
            'sla_due_at' => $slaService->calculateDueDate(Carbon::now(), 4, $slaType),
        ]);

        return redirect()->route('dashboard')->with('success', "เปิดเคสแจ้งซ่อม {$ticketNo} เรียบร้อยแล้ว");
    }

    /**
     * Update the status and assignment of a helpdesk case.
     */
    public function updateStatus(Request $request, HelpdeskCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,resolved,closed,cancelled'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $updateData = [
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? $case->assigned_to,
            'resolution_notes' => $validated['resolution_notes'] ?? $case->resolution_notes,
        ];

        if (in_array($validated['status'], ['resolved', 'closed'], true) && is_null($case->resolved_at)) {
            $updateData['resolved_at'] = Carbon::now();
        } elseif (! in_array($validated['status'], ['resolved', 'closed'], true)) {
            $updateData['resolved_at'] = null;
        }

        $case->update($updateData);

        return redirect()->route('dashboard')->with('success', "อัปเดตสถานะเคส {$case->ticket_no} เป็น '{$case->status_label}' เรียบร้อยแล้ว");
    }
}
