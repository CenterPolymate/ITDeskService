<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Models\Sla;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'administrator') {
            $totalTickets = HelpdeskCase::count();
            $resolvedTickets = HelpdeskCase::whereIn('status', ['resolved', 'approved', 'closed', 'cancelled'])->count();
            $breachedTickets = HelpdeskCase::breached()->count();

            $stats = [
                'total_it_users' => User::where('role', '!=', 'user')->count(),
                'active_it_users' => User::where('role', '!=', 'user')->where('is_active', true)->count(),
                'total_normal_users' => User::where('role', 'user')->count(),
                'active_normal_users' => User::where('role', 'user')->where('is_active', true)->count(),
                'total_companies' => Company::count(),
                'active_companies' => Company::where('is_active', true)->count(),
                'total_slas' => Sla::count(),
                'total_tickets' => $totalTickets,
                'resolved_tickets' => $resolvedTickets,
                'breached_tickets' => $breachedTickets,
                'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) : 0,
            ];

            // Ticket Status Distribution for Chart
            $ticketStatuses = HelpdeskCase::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Format for Chart.js
            $statusLabels = [
                'pending' => 'รอดำเนินการ',
                'analyzing' => 'วิเคราะห์ปัญหา',
                'in_progress' => 'กำลังดำเนินการ',
                'resolved' => 'รอผู้แจ้งยืนยัน',
                'approved' => 'รอหัวหน้าอนุมัติ',
                'closed' => 'ปิดใบงาน',
                'cancelled' => 'ยกเลิก',
            ];

            $chartLabels = [];
            $chartData = [];

            foreach ($statusLabels as $key => $label) {
                $chartLabels[] = $label;
                $chartData[] = $ticketStatuses[$key] ?? 0;
            }

            // Latest Backup
            $latestBackup = null;
            try {
                $disk = Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
                $backupName = config('backup.backup.name');
                $files = $disk->files($backupName);
                $files = array_filter($files, fn ($file) => substr($file, -4) === '.zip');
                if (! empty($files)) {
                    $latestFile = end($files); // last element is usually the newest if sorted alphabetically by timestamp
                    $latestBackup = [
                        'file_name' => str_replace($backupName.'/', '', $latestFile),
                        'last_modified' => Carbon::createFromTimestamp($disk->lastModified($latestFile))->translatedFormat('d F Y H:i'),
                        'size' => round($disk->size($latestFile) / 1048576, 2).' MB',
                    ];
                }
            } catch (\Exception $e) {
                // Ignore error if backup disk is missing or unreadable
            }

            return Inertia::render('Dashboard/SuperadminDashboard', compact('stats', 'chartLabels', 'chartData', 'latestBackup'));
        }

        // 1. Data Isolation for General Users
        if ($user->role === 'user') {
            $baseQuery = HelpdeskCase::where('requester_email', $user->email);

            $stats = [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'assigned'])->count(),
                'in_progress' => (clone $baseQuery)->whereIn('status', ['analyzing', 'in_progress'])->count(),
                'completed' => (clone $baseQuery)->whereIn('status', ['resolved', 'approved', 'closed', 'cancelled'])->count(),
                'waiting_acceptance' => (clone $baseQuery)->where('status', 'resolved')->count(),
            ];

            // Apply Filters for User
            $query = clone $baseQuery;
            if ($request->has('status')) {
                $status = $request->status;
                if ($status === 'user_pending') {
                    $query->whereIn('status', ['pending', 'assigned']);
                } elseif ($status === 'user_in_progress') {
                    $query->whereIn('status', ['analyzing', 'in_progress']);
                } elseif ($status === 'user_completed') {
                    $query->whereIn('status', ['resolved', 'approved', 'closed', 'cancelled']);
                } elseif ($status === 'resolved') {
                    $query->where('status', 'resolved');
                }
            }

            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // ดึงเฉพาะเคสของ User คนนั้นๆ
            $cases = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

            return Inertia::render('Dashboard/UserDashboard', [
                'cases' => $cases,
                'stats' => $stats,
                'filters' => [
                    'status' => $request->input('status', 'all'),
                    'search' => $request->input('search', ''),
                ],
            ]);
        }

        // 2. Data Scope for IT Staff (Helpdesk, Team, Manager)
        $baseQuery = HelpdeskCase::query();

        // ถ้าเป็นทีมเฉพาะทาง (Tier 2) ให้เห็นเฉพาะงานที่ Assign/Escalate มาที่ทีมตัวเอง
        if (in_array($user->role, ['team_hardware', 'team_network', 'team_software'])) {
            $teamMapping = [
                'team_hardware' => 'Hardware',
                'team_network' => 'Network',
                'team_software' => 'Software',
            ];
            $baseQuery->where('escalated_to_team', $teamMapping[$user->role]);
        }

        // คำนวณ KPI Cards ให้ครบทุกขั้นตอนตาม Data Scope ของ User นั้นๆ (ก่อนนำไปกรองตาม Filter URL)
        $today = now()->timezone('Asia/Bangkok')->toDateString();
        $isManager = (method_exists($user, 'isManager') && $user->isManager()) || in_array($user->role, ['manager', 'manager_software', 'manager_network_hardware'], true);
        $isAdmin = $user->role === 'administrator';

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'open_total' => (clone $baseQuery)->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'assigned'])->count(),
            'analyzing' => (clone $baseQuery)->where('status', 'analyzing')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'testing' => (clone $baseQuery)->whereIn('status', ['resolved', 'approved'])->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'closed_today' => (clone $baseQuery)->where('status', 'closed')->where(function ($q) use ($today) {
                $q->whereDate('closed_at', $today)
                    ->orWhere(function ($sq) use ($today) {
                        $sq->whereNull('closed_at')->whereDate('updated_at', $today);
                    });
            })->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'cancelled_today' => (clone $baseQuery)->where('status', 'cancelled')->where(function ($q) use ($today) {
                $q->whereDate('cancelled_at', $today)
                    ->orWhere(function ($sq) use ($today) {
                        $sq->whereNull('cancelled_at')->whereDate('updated_at', $today);
                    });
            })->count(),
            'completed' => (clone $baseQuery)->whereIn('status', ['closed', 'cancelled'])->count(),
            'preventive_pending' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where(function ($q) {
                $q->whereNull('preventive_measure')->orWhereIn('preventive_measure', ['in_progress', 'pending_review']);
            })->count(),
            'preventive_completed' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'done')->count(),
            'urgent' => (clone $baseQuery)->where('priority', 'urgent')->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'sla_warning' => (clone $baseQuery)->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '>', now())
                ->where('sla_due_at', '<=', now()->addMinutes(60))->count(),
            'sla_overdue' => (clone $baseQuery)->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<=', now())->count(),
            'breached' => (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])
                ->whereNotNull('sla_due_at')
                ->whereColumn('resolved_at', '>', 'sla_due_at')->count(),

            // Task 2 Stats
            'task2_total' => ($isManager || $isAdmin)
                ? (clone $baseQuery)->where('status', 'closed')->where(function ($q) {
                    $q->whereNull('requires_preventive_measure')->orWhere('requires_preventive_measure', true);
                })->count()
                : (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->count(),
            'task2_manager_review' => (clone $baseQuery)->where('status', 'closed')->whereNull('requires_preventive_measure')->count(),
            'task2_assigned' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where(function ($q) {
                $q->whereNull('preventive_measure')->orWhere('preventive_measure', 'assigned');
            })->count(),
            'task2_in_progress' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'in_progress')->count(),
            'task2_pending_review' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'pending_review')->count(),
            'task2_completed' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'done')->count(),
        ];

        // Technician Workload Data for Tier 3 Managers & Administrator
        $isManager = (method_exists($user, 'isManager') && $user->isManager()) || in_array($user->role, ['manager', 'manager_software', 'manager_network_hardware'], true);
        $isAdmin = $user->role === 'administrator';
        $technicianWorkload = null;

        if ($isManager || $isAdmin) {
            $targetRoles = match ($user->role) {
                'manager_software' => ['team_software'],
                'manager_network_hardware' => ['team_hardware', 'team_network'],
                default => ['team_hardware', 'team_network', 'team_software'],
            };

            $teamNames = match ($user->role) {
                'manager_software' => ['Software'],
                'manager_network_hardware' => ['Hardware', 'Network'],
                default => ['Hardware', 'Network', 'Software'],
            };

            $teamTitle = match ($user->role) {
                'manager_software' => 'ทีม Software (Tier 2 ในความรับผิดชอบ)',
                'manager_network_hardware' => 'ทีม Network & Hardware (Tier 2 ในความรับผิดชอบ)',
                default => 'ทีมช่าง IT ทั้งหมด (Tier 2)',
            };

            $roleLabelMap = [
                'team_hardware' => 'Team Hardware',
                'team_network' => 'Team Network',
                'team_software' => 'Team Software',
            ];

            $technicians = User::whereIn('role', $targetRoles)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $technicianList = [];
            foreach ($technicians as $tech) {
                $techTickets = HelpdeskCase::whereIn('status', ['analyzing', 'in_progress'])
                    ->where(function ($q) use ($tech) {
                        $q->where('in_progress_by', $tech->id)
                            ->orWhere('analyzing_by', $tech->id);
                    })
                    ->get();

                $analyzingCount = $techTickets->where('status', 'analyzing')->count();
                $inProgressCount = $techTickets->where('status', 'in_progress')->count();
                $totalActive = $techTickets->count();

                $slaWarningCount = $techTickets->filter(fn ($t) => $t->is_near_overdue)->count();
                $slaOverdueCount = $techTickets->filter(fn ($t) => $t->is_overdue)->count();

                $resolvedTodayCount = HelpdeskCase::where('resolved_by', $tech->id)
                    ->whereDate('resolved_at', $today)
                    ->count();

                $technicianList[] = [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'role' => $tech->role,
                    'role_label' => $roleLabelMap[$tech->role] ?? $tech->role,
                    'email' => $tech->email,
                    'phone' => $tech->phone,
                    'analyzing_count' => $analyzingCount,
                    'in_progress_count' => $inProgressCount,
                    'total_active' => $totalActive,
                    'sla_warning_count' => $slaWarningCount,
                    'sla_overdue_count' => $slaOverdueCount,
                    'resolved_today_count' => $resolvedTodayCount,
                    'recent_tickets' => $techTickets->take(5)->map(fn ($t) => [
                        'id' => $t->id,
                        'ticket_no' => $t->ticket_no,
                        'title' => $t->title,
                        'priority' => $t->priority,
                        'status' => $t->status,
                        'status_label' => $t->status_label,
                        'is_overdue' => $t->is_overdue,
                        'is_near_overdue' => $t->is_near_overdue,
                    ])->values()->all(),
                ];
            }

            $unassignedCount = HelpdeskCase::where('status', 'assigned')
                ->whereIn('escalated_to_team', $teamNames)
                ->count();

            $technicianWorkload = [
                'team_title' => $teamTitle,
                'manager_role' => $user->role,
                'target_teams' => $teamNames,
                'total_technicians' => $technicians->count(),
                'total_active_tickets' => collect($technicianList)->sum('total_active'),
                'total_warning_tickets' => collect($technicianList)->sum('sla_warning_count'),
                'total_overdue_tickets' => collect($technicianList)->sum('sla_overdue_count'),
                'unassigned_queue_count' => $unassignedCount,
                'technicians' => $technicianList,
            ];
        }

        // กรองข้อมูลตาม Filter จาก URL (ถ้ามี)
        $query = clone $baseQuery;
        $query->filter($request->only(['search', 'status', 'priority', 'category', 'department', 'team', 'preventive', 'technician_id']));

        $cases = $query->orderBy('created_at', 'desc')->paginate(10);

        // คำนวณเปอร์เซ็นต์สำหรับ Pipeline แยกตามขั้นตอน (อิงจากงานค้างที่เปิดอยู่ open_total)
        $openTotal = $stats['open_total'];
        $stats['percent_open_pending'] = $openTotal > 0 ? round(($stats['pending'] / $openTotal) * 100) : 0;
        $stats['percent_open_analyzing'] = $openTotal > 0 ? round(($stats['analyzing'] / $openTotal) * 100) : 0;
        $stats['percent_open_in_progress'] = $openTotal > 0 ? round(($stats['in_progress'] / $openTotal) * 100) : 0;
        $stats['percent_open_testing'] = $openTotal > 0 ? round(($stats['testing'] / $openTotal) * 100) : 0;

        // คำนวณเปอร์เซ็นต์เดิม
        $stats['percent_pending'] = $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100) : 0;
        $stats['percent_analyzing'] = $stats['total'] > 0 ? round(($stats['analyzing'] / $stats['total']) * 100) : 0;
        $stats['percent_in_progress'] = $stats['total'] > 0 ? round(($stats['in_progress'] / $stats['total']) * 100) : 0;
        $stats['percent_testing'] = $stats['total'] > 0 ? round(($stats['testing'] / $stats['total']) * 100) : 0;
        $stats['percent_completed'] = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
        $stats['percent_breached'] = $stats['completed'] > 0 ? round(($stats['breached'] / max($stats['completed'], 1)) * 100) : 0;

        // Percentages for Task 2 relative to Task 2 Total
        $t2_total = $stats['task2_total'];
        $stats['percent_t2_manager'] = $t2_total > 0 ? round(($stats['task2_manager_review'] / $t2_total) * 100) : 0;
        $stats['percent_t2_assigned'] = $t2_total > 0 ? round(($stats['task2_assigned'] / $t2_total) * 100) : 0;
        $stats['percent_t2_in_progress'] = $t2_total > 0 ? round(($stats['task2_in_progress'] / $t2_total) * 100) : 0;
        $stats['percent_t2_pending'] = $t2_total > 0 ? round(($stats['task2_pending_review'] / $t2_total) * 100) : 0;
        $stats['percent_t2_completed'] = $t2_total > 0 ? round(($stats['task2_completed'] / $t2_total) * 100) : 0;

        return Inertia::render('Dashboard/ItStaffDashboard', [
            'cases' => $cases,
            'stats' => $stats,
            'technician_workload' => $technicianWorkload,
            'filters' => $request->only(['search', 'status', 'priority', 'category', 'department', 'team', 'preventive', 'technician_id']),
        ]);
    }
}
