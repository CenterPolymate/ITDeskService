<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Services\SlaService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * Show the form for creating a new ticket.
     */
    public function create(): Response
    {
        $categories = Category::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Tickets/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'category' => 'nullable|string|max:100',
            'description' => 'required|string|max:400',
            'location' => 'required|string|max:255',
            'requester_phone' => ['required', 'string', 'regex:/^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$/'],
            'actual_user_name' => 'nullable|string|max:100',
            'machine_name' => 'nullable|string|max:100',
            'machine_code' => 'nullable|string|max:50',
            'is_machine_stopped' => 'required|boolean',
            'attachments' => 'nullable|array|max:2',
            'attachments.*' => 'file|mimes:jpg,jpeg,png|max:5120', // 5MB max per file
        ], [
            'location.required' => 'กรุณาระบุสถานที่/จุดที่เกิดปัญหา',
            'requester_phone.required' => 'กรุณากรอกเบอร์โทรศัพท์ติดต่อกลับ',
            'requester_phone.regex' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (เช่น 081-123-4567 หรือ 0811234567)',
            'is_machine_stopped.required' => 'กรุณาระบุสถานะเครื่องจักร ณ ปัจจุบัน',
            'attachments.max' => 'แนบรูปภาพได้สูงสุด 2 รูปเท่านั้น',
        ]);

        $user = Auth::user();

        // Generate Ticket Number: IT-YYYYMM-XXXX
        $prefix = 'IT-'.now()->format('Ym').'-';

        // Find the last ticket created in this month
        $lastTicket = HelpdeskCase::where('ticket_no', 'like', $prefix.'%')
            ->orderBy('ticket_no', 'desc')
            ->first();

        if ($lastTicket) {
            // Extract the last 4 digits and increment
            $lastNumber = (int) substr($lastTicket->ticket_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no tickets exist this month
            $newNumber = 1;
        }

        $ticketNo = $prefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        $companyModel = Company::where('name', $user->company)->first();
        $slaType = $companyModel ? $companyModel->sla_type : '8x5';
        $slaService = app(SlaService::class);

        // Create Case
        $ticket = HelpdeskCase::create([
            'ticket_no' => $ticketNo,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category ?? 'Other',
            'priority' => 'medium', // Default priority for user submissions
            'status' => 'pending',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => $request->requester_phone,
            'actual_user_name' => $request->actual_user_name,
            'machine_name' => $request->machine_name,
            'machine_code' => $request->machine_code,
            'is_machine_stopped' => $request->is_machine_stopped !== null ? filter_var($request->is_machine_stopped, FILTER_VALIDATE_BOOLEAN) : null,
            'user_id' => $user->id,
            'company' => $user->company,
            'department' => $user->department,
            'location' => $request->location,
            'sla_due_at' => $slaService->calculateDueDate(now(), 4, $slaType),
        ]);

        // Handle File Upload (Max 2 files)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $ticket->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Send Notification
        // $user->notify(new \App\Notifications\TicketCreated($ticket)); // TBD: Wait for user approval for Email/Line

        return redirect()->route('dashboard')->with('success', "เปิดใบงานใหม่สำเร็จ! หมายเลข Ticket: {$ticketNo}");
    }

    /**
     * Display the specified ticket.
     */
    public function show(string $id): Response
    {
        $ticket = HelpdeskCase::with([
            'attachments',
            'parts',
            'comments.user',
            'analyzingBy',
            'inProgressBy',
            'resolvedBy',
            'assignedBy',
            'approvedBy',
            'closedBy',
            'cancelledBy',
            'pcarOpenedBy',
            'pcarAnalyzedBy',
            'pcarClosedBy',
        ])->findOrFail($id);

        // Check if user has permission to view
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->requester_email !== $user->email) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('Tickets/Show', compact('ticket'));
    }

    /**
     * Print the specified ticket report (P-CAR Form).
     */
    public function print(string $id): View|Factory
    {
        $ticket = HelpdeskCase::with(['analyzingBy', 'inProgressBy', 'resolvedBy', 'closedBy', 'cancelledBy'])->findOrFail($id);

        // Check if user has permission to view
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->requester_email !== $user->email) {
            abort(403, 'Unauthorized action.');
        }

        return view('tickets.print', compact('ticket'));
    }

    /**
     * Update the specified ticket (Triage & Escalate).
     */
    public function assign(Request $request, string $id): RedirectResponse
    {
        $ticket = HelpdeskCase::findOrFail($id);

        if ($ticket->status === 'closed') {
            return redirect()->back()->withErrors('ไม่สามารถดำเนินการได้ เนื่องจากใบงานถูกปิดแล้ว');
        }

        if (! in_array($ticket->status, ['pending', 'assigned']) && ! is_null($ticket->escalated_to_team)) {
            return redirect()->back()->withErrors('ไม่สามารถแก้ไขการมอบหมายงานได้ เนื่องจากทีมได้รับงานไปแล้ว');
        }

        $request->validate([
            'priority' => 'required|in:urgent,high,medium,low',
            'escalated_to_team' => 'required|string',
        ]);

        // Default status update based on team
        // If escalated to a team, status becomes 'assigned'
        $status = 'assigned';
        if ($request->escalated_to_team === 'None') {
            // If helpdesk keeps it
            $request->merge(['escalated_to_team' => null]);
        }

        $updateData = [
            'priority' => $request->priority,
            'escalated_to_team' => $request->escalated_to_team === 'None' ? null : $request->escalated_to_team,
            'status' => $status,
            'assigned_at' => now(),
            'assigned_by' => Auth::id(),
        ];

        if ($status === 'in_progress') {
            $updateData['in_progress_at'] = now();
            $updateData['in_progress_by'] = Auth::id();
            $updateData['analyzing_at'] = now();
            $updateData['analyzing_by'] = Auth::id();
        } else {
            $updateData['analyzing_at'] = null;
            $updateData['analyzing_by'] = null;
            $updateData['in_progress_at'] = null;
            $updateData['in_progress_by'] = null;
            $updateData['resolved_at'] = null;
            $updateData['resolved_by'] = null;
            $updateData['analysis_notes'] = null;
            $updateData['resolution_notes'] = null;
        }

        $ticket->update($updateData);

        return redirect()->route('tickets.show', $id)->with('success', 'ประเมินและมอบหมายงานเรียบร้อยแล้ว');
    }

    /**
     * Update the ticket status by assigned team (Step 3 & 4).
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $ticket = HelpdeskCase::findOrFail($id);

        // Allow manager to 'close' (review) and allow IT team to submit 'preventive_action' even if closed
        if ($ticket->status === 'closed' && ! ($request->action === 'close' && Auth::user()->role === 'manager') && ! in_array($request->action, ['start_preventive_measure', 'preventive_action', 'close_preventive_measure'])) {
            return redirect()->back()->withErrors('ไม่สามารถดำเนินการได้ เนื่องจากใบงานถูกปิดแล้ว');
        }

        $request->validate([
            'action' => 'required|in:start_analyzing,start_progress,save_notes,resolve,update_notes,update_analysis,accept_resolution,close,cancel,start_preventive_measure,preventive_action,close_preventive_measure',
            'resolution_notes' => 'nullable|string',
            'analysis_notes' => 'nullable|array',
            'why_1' => 'nullable|string',
            'why_2' => 'nullable|string',
            'why_3' => 'nullable|string',
            'root_cause_detail' => 'nullable|string',
            'requires_preventive_measure' => 'required_if:action,close|nullable|boolean',
            'cancellation_reason' => 'required_if:action,cancel|nullable|string',
        ]);

        if ($request->action === 'start_analyzing') {
            return DB::transaction(function () use ($id) {
                $ticket = HelpdeskCase::where('id', $id)->lockForUpdate()->firstOrFail();
                $user = Auth::user();

                // 1. Permission check: Check team / role
                if ($ticket->escalated_to_team) {
                    $teamMap = [
                        'team_hardware' => 'Hardware',
                        'team_network' => 'Network',
                        'team_software' => 'Software',
                    ];
                    $userTeam = $teamMap[$user->role] ?? null;
                    if ($userTeam !== $ticket->escalated_to_team && ! in_array($user->role, ['administrator', 'manager'])) {
                        return redirect()->route('tickets.show', $id)
                            ->with('error', 'ไม่อนุญาต: ใบงานนี้ถูกมอบหมายให้ทีม '.$ticket->escalated_to_team.' เท่านั้น');
                    }
                } else {
                    if (! in_array($user->role, ['helpdesk', 'administrator', 'manager'])) {
                        return redirect()->route('tickets.show', $id)
                            ->with('error', 'ไม่อนุญาต: ใบงานนี้อยู่ในความรับผิดชอบของ Helpdesk');
                    }
                }

                // 2. Status guard: Must be in 'assigned' status
                if ($ticket->status !== 'assigned') {
                    $investigator = $ticket->analyzingBy?->name ?? 'เจ้าหน้าที่ท่านอื่น';

                    return redirect()->route('tickets.show', $id)
                        ->with('error', "ไม่สามารถเริ่มสืบสภาพได้ เนื่องจากใบงานนี้อยู่ในสถานะ \"{$ticket->status_label}\" แล้ว (ผู้ดำเนินการ: {$investigator})");
                }

                // 3. Ownership guard: Check if analyzing_by is already set
                if (! empty($ticket->analyzing_by)) {
                    $investigator = $ticket->analyzingBy?->name ?? 'เจ้าหน้าที่ท่านอื่น';

                    return redirect()->route('tickets.show', $id)
                        ->with('error', "ใบงานนี้มีผู้เริ่มดำเนินการสืบสภาพแล้ว โดยคุณ {$investigator}");
                }

                $ticket->update([
                    'status' => 'analyzing',
                    'analyzing_at' => now(),
                    'analyzing_by' => $user->id,
                ]);

                return redirect()->route('tickets.show', $id)->with('success', 'เริ่มดำเนินการสืบสภาพแล้ว');
            });
        } elseif ($request->action === 'update_analysis') {
            return DB::transaction(function () use ($id, $request) {
                $ticket = HelpdeskCase::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($ticket->status !== 'analyzing') {
                    return redirect()->route('tickets.show', $id)
                        ->with('error', "ไม่สามารถบันทึกการสืบสภาพได้ เนื่องจากใบงานไม่ได้อยู่ในสถานะสืบสภาพ (สถานะปัจจุบัน: {$ticket->status_label})");
                }

                $updateData = [
                    'why_1' => $request->why_1,
                    'why_2' => $request->why_2,
                    'why_3' => $request->why_3,
                    'root_cause_detail' => $request->root_cause_detail,
                    // Preserve the initial investigator! Only fallback to Auth::id() if never set
                    'analyzing_by' => $ticket->analyzing_by ?: Auth::id(),
                ];

                // If root cause is filled, automatically transition to in_progress
                if (! empty($request->root_cause_detail)) {
                    $updateData['status'] = 'in_progress';
                    $updateData['in_progress_at'] = now();
                    $updateData['in_progress_by'] = Auth::id();
                    $message = 'บันทึกสาเหตุรากเหง้าเรียบร้อย ระบบได้เริ่มนับเวลาการแก้ไขแล้ว';
                } else {
                    $message = 'บันทึกข้อมูลสืบสภาพเบื้องต้นแล้ว (กรุณาระบุสาเหตุรากเหง้าเพื่อเริ่มการแก้ไข)';
                }

                $ticket->update($updateData);

                return redirect()->route('tickets.show', $id)->with('success', $message);
            });
        } elseif ($request->action === 'save_notes') {
            $ticket->update([
                'resolution_notes' => $request->resolution_notes,
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'บันทึกข้อมูลการแก้ไขปัญหาเฉพาะหน้าเรียบร้อยแล้ว (สถานะยังคงเป็น: กำลังดำเนินการแก้ไข)');
        } elseif ($request->action === 'resolve') {
            $resolutionNotes = $request->resolution_notes ?: $ticket->resolution_notes;
            if (empty(trim((string) $resolutionNotes))) {
                return back()->with('error', 'กรุณาระบุบันทึกการแก้ไขปัญหาเฉพาะหน้าก่อนส่งมอบงาน');
            }

            $updateData = [
                'status' => 'resolved',
                'resolution_notes' => $resolutionNotes,
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ];

            if (is_null($ticket->in_progress_at)) {
                $updateData['in_progress_at'] = now();
                $updateData['in_progress_by'] = Auth::id();
            }
            if (is_null($ticket->analyzing_at) && is_null($ticket->escalated_to_team)) {
                $updateData['analyzing_at'] = now();
                $updateData['analyzing_by'] = Auth::id();
            }

            $ticket->update($updateData);

            if ($request->has('parts') && is_array($request->parts)) {
                foreach ($request->parts as $part) {
                    if (! empty($part['part_name']) && ! empty($part['quantity']) && ! empty($part['unit'])) {
                        $ticket->parts()->create([
                            'part_name' => $part['part_name'],
                            'quantity' => $part['quantity'],
                            'unit' => $part['unit'],
                            'document_no' => $part['document_no'] ?? null,
                            'vendor' => $part['vendor'] ?? null,
                            'unit_price' => $part['unit_price'] ?? null,
                            'remarks' => $part['remarks'] ?? null,
                        ]);
                    }
                }
            }

            return redirect()->route('tickets.show', $id)->with('success', 'ส่งมอบงานให้ผู้แจ้งเรียบร้อยแล้ว ใบงานเข้าสู่สถานะ: รอผู้แจ้งกดรับงาน');
        } elseif ($request->action === 'update_notes') {
            $updateData = [
                'resolution_notes' => $request->resolution_notes,
                'why_1' => $request->why_1,
                'why_2' => $request->why_2,
                'why_3' => $request->why_3,
                'root_cause_detail' => $request->root_cause_detail,
            ];

            if ($ticket->preventive_measure === 'pending_review') {
                $updateData['pcar_analyzed_at'] = now();
            }

            $ticket->update($updateData);

            // Sync parts if provided
            if ($request->has('parts')) {
                $ticket->parts()->delete();
                if (is_array($request->parts)) {
                    foreach ($request->parts as $part) {
                        if (! empty($part['part_name']) && ! empty($part['quantity']) && ! empty($part['unit'])) {
                            $ticket->parts()->create([
                                'part_name' => $part['part_name'],
                                'quantity' => $part['quantity'],
                                'unit' => $part['unit'],
                                'document_no' => $part['document_no'] ?? null,
                                'vendor' => $part['vendor'] ?? null,
                                'unit_price' => $part['unit_price'] ?? null,
                                'remarks' => $part['remarks'] ?? null,
                            ]);
                        }
                    }
                }
            }

            return redirect()->route('tickets.show', $id)->with('success', 'อัปเดตข้อมูลสำเร็จแล้ว');
        } elseif ($request->action === 'accept_resolution') {
            $isRequester = Auth::user()->email === $ticket->requester_email;
            $isHelpdesk = Auth::user()->role === 'helpdesk';

            if (! $isRequester && $isHelpdesk) {
                if ($ticket->resolved_at && now()->lessThan($ticket->resolved_at->copy()->addMinutes(5))) {
                    return back()->with('error', 'Helpdesk สามารถกดยืนยันแทนผู้แจ้งได้เมื่อเวลาผ่านไปแล้ว 5 นาทีเท่านั้น');
                }
            }

            $updateData = [
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ];

            // Only requester can submit rating
            if ($isRequester && $request->has('rating')) {
                $updateData['rating'] = $request->rating;
                $updateData['feedback'] = $request->feedback;
            }

            $updateData['status'] = 'closed';
            $updateData['closed_at'] = now();
            $updateData['closed_by'] = Auth::id();
            $msg = 'รับทราบการแก้ไขและปิดใบงานเรียบร้อยแล้ว';

            $ticket->update($updateData);

            return redirect()->route('tickets.show', $id)->with('success', $msg);
        } elseif ($request->action === 'cancel') {
            $isRequester = Auth::user()->email === $ticket->requester_email;
            $isHelpdesk = Auth::user()->role === 'helpdesk';
            $isManager = str_contains(Auth::user()->role, 'manager');

            if (! $isRequester && ! $isHelpdesk && ! $isManager) {
                return back()->with('error', 'คุณไม่มีสิทธิ์ยกเลิกใบงานนี้');
            }

            if (in_array($ticket->status, ['resolved', 'approved', 'closed', 'cancelled'])) {
                return back()->with('error', 'ไม่สามารถยกเลิกเคสในสถานะปัจจุบันได้');
            }

            $ticket->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'ยกเลิกเคสเรียบร้อยแล้ว');
        } elseif ($request->action === 'close') {
            $updateData = [
                'requires_preventive_measure' => $request->requires_preventive_measure,
            ];

            if ($request->requires_preventive_measure == 1) {
                if ($request->filled('escalated_to_team')) {
                    $updateData['escalated_to_team'] = $request->escalated_to_team;
                    $updateData['preventive_measure'] = 'assigned';
                }

                $pcarNo = $ticket->pcar_no;
                if (! $pcarNo) {
                    $prefix = 'PCAR-'.date('Ym').'-';
                    $lastPcar = HelpdeskCase::where('pcar_no', 'like', $prefix.'%')->orderBy('pcar_no', 'desc')->first();
                    $lastNumber = $lastPcar ? (int) substr($lastPcar->pcar_no, -4) : 0;
                    $pcarNo = $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                }

                $updateData['pcar_no'] = $pcarNo;
                $updateData['pcar_opened_at'] = now();
                $updateData['pcar_opened_by'] = Auth::id();
            } elseif ($request->requires_preventive_measure == 0) {
                $updateData['preventive_measure'] = null;
            }

            $ticket->update($updateData);

            return redirect()->route('tickets.show', $id)->with('success', 'บันทึกการตรวจสอบและส่งต่องานเรียบร้อยแล้ว');
        } elseif ($request->action === 'start_preventive_measure') {
            $user = Auth::user();
            $tier2Roles = ['team_hardware', 'team_network', 'team_software'];

            if (! in_array($user->role, $tier2Roles)) {
                return redirect()->route('tickets.show', $id)
                    ->with('error', 'ไม่อนุญาต: สิทธิ์การกดเริ่มดำเนินการ Task 2 เป็นของช่างเฉพาะทาง (Tier 2) เท่านั้น');
            }

            if ($ticket->escalated_to_team) {
                $teamMap = [
                    'team_hardware' => 'Hardware',
                    'team_network' => 'Network',
                    'team_software' => 'Software',
                ];
                if (($teamMap[$user->role] ?? null) !== $ticket->escalated_to_team) {
                    return redirect()->route('tickets.show', $id)
                        ->with('error', 'ไม่อนุญาต: ใบงาน Task 2 นี้ถูกมอบหมายให้ทีม '.$ticket->escalated_to_team.' เท่านั้น');
                }
            }

            $ticket->update([
                'preventive_measure' => 'in_progress',
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'เริ่มดำเนินการมาตรการป้องกัน (Task 2) เรียบร้อยแล้ว');
        } elseif ($request->action === 'preventive_action') {
            $ticket->update([
                'why_1' => $request->why_1,
                'why_2' => $request->why_2,
                'why_3' => $request->why_3,
                'root_cause_category' => $request->root_cause_category,
                'root_cause_detail' => $request->root_cause_detail,
                'resolution_notes' => $request->resolution_notes,
                'preventive_measure_specific' => $request->preventive_measure_specific,
                'preventive_measure_specific_due_date' => $request->preventive_measure_specific_due_date,
                'preventive_measure_systemic' => $request->preventive_measure_systemic,
                'preventive_measure_systemic_due_date' => $request->preventive_measure_systemic_due_date,
                'preventive_measure' => 'pending_review',
                'pcar_analyzed_at' => now(),
                'pcar_analyzed_by' => Auth::id(),
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'บันทึกข้อมูล Task 2 แล้ว รอหัวหน้าตรวจสอบและปิดมาตรการ');
        } elseif ($request->action === 'close_preventive_measure') {
            $ticket->update([
                'preventive_measure' => 'done',
                'pcar_closed_at' => now(),
                'pcar_closed_by' => Auth::id(),
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'ปิดมาตรการป้องกันเรียบร้อยแล้ว');
        }

        return back();
    }
}
