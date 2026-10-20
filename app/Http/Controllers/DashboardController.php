<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Models\Sla;
use App\Models\Team;
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
            if ($request->input('view') !== 'it_operations') {
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
                        $parsedFiles = [];
                        foreach ($files as $file) {
                            $fileName = str_replace($backupName.'/', '', $file);
                            $timestamp = null;
                            if (preg_match('/(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})/', $fileName, $matches)) {
                                try {
                                    $timestamp = Carbon::createFromFormat(
                                        'Y-m-d-H-i-s',
                                        "{$matches[1]}-{$matches[2]}-{$matches[3]}-{$matches[4]}-{$matches[5]}-{$matches[6]}",
                                        config('app.timezone', 'Asia/Bangkok')
                                    );
                                } catch (\Throwable $e) {
                                    $timestamp = null;
                                }
                            }
                            if (! $timestamp) {
                                $timestamp = Carbon::createFromTimestamp($disk->lastModified($file), config('app.timezone', 'Asia/Bangkok'));
                            }
                            $parsedFiles[] = [
                                'file' => $file,
                                'file_name' => $fileName,
                                'timestamp' => $timestamp,
                            ];
                        }
                        usort($parsedFiles, function ($a, $b) {
                            if ($b['timestamp']->timestamp === $a['timestamp']->timestamp) {
                                return strcmp($b['file_name'], $a['file_name']);
                            }

                            return $b['timestamp']->timestamp <=> $a['timestamp']->timestamp;
                        });

                        $latest = $parsedFiles[0];
                        $latestBackup = [
                            'file_name' => $latest['file_name'],
                            'last_modified' => $latest['timestamp']->locale('th')->translatedFormat('d F Y H:i:s'),
                            'size' => round($disk->size($latest['file']) / 1048576, 2).' MB',
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignore error if backup disk is missing or unreadable
                }

                $recentAuditLogs = AuditLog::with('user:id,name,role')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'user_name' => $log->user ? $log->user->name : 'ระบบ',
                        'user_role' => $log->user ? $log->user->role : '-',
                        'action' => $log->action,
                        'target_type' => $log->target_type,
                        'created_at' => $log->created_at ? $log->created_at->translatedFormat('d F Y H:i') : '-',
                        'time_ago' => $log->created_at ? $log->created_at->diffForHumans() : '-',
                    ]);

                return Inertia::render('Dashboard/SuperadminDashboard', compact('stats', 'chartLabels', 'chartData', 'latestBackup', 'recentAuditLogs'));
            }
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

            $sortField = $request->input('sort', 'created_at');
            $sortDirection = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

            $allowedSorts = ['ticket_no', 'title', 'category', 'status', 'created_at'];
            if (! in_array($sortField, $allowedSorts, true)) {
                $sortField = 'created_at';
            }

            if ($sortField === 'status') {
                $orderStatus = "CASE status 
                    WHEN 'pending' THEN 1 
                    WHEN 'assigned' THEN 2 
                    WHEN 'analyzing' THEN 3 
                    WHEN 'in_progress' THEN 4 
                    WHEN 'resolved' THEN 5 
                    WHEN 'approved' THEN 6 
                    WHEN 'closed' THEN 7 
                    WHEN 'cancelled' THEN 8 
                    ELSE 9 END";
                $query->orderByRaw($sortDirection === 'asc' ? $orderStatus : "CASE status 
                    WHEN 'cancelled' THEN 1 
                    WHEN 'closed' THEN 2 
                    WHEN 'approved' THEN 3 
                    WHEN 'resolved' THEN 4 
                    WHEN 'in_progress' THEN 5 
                    WHEN 'analyzing' THEN 6 
                    WHEN 'assigned' THEN 7 
                    WHEN 'pending' THEN 8 
                    ELSE 9 END");
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
            if ($sortField !== 'id') {
                $query->orderBy('id', 'desc');
            }

            // ดึงเฉพาะเคสของ User คนนั้นๆ
            $cases = $query->paginate(10)->withQueryString();

            // ดึงเคสที่กำลังดำเนินการล่าสุดสำหรับ Spotlight (ไม่รวมข้อมูลช่างตามความต้องการ)
            $activeTickets = (clone $baseQuery)
                ->whereIn('status', ['analyzing', 'in_progress'])
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get(['id', 'ticket_no', 'title', 'category', 'status', 'created_at']);

            // ดึงเคสที่รอผู้แจ้งตรวจรับงาน (Resolved) สำหรับ 1-Click Acceptance
            $waitingTickets = (clone $baseQuery)
                ->where('status', 'resolved')
                ->orderBy('resolved_at', 'desc')
                ->get(['id', 'ticket_no', 'title', 'category', 'status', 'resolved_at']);

            return Inertia::render('Dashboard/UserDashboard', [
                'cases' => $cases,
                'stats' => $stats,
                'active_tickets' => $activeTickets,
                'waiting_tickets' => $waitingTickets,
                'filters' => [
                    'status' => $request->input('status', 'all'),
                    'search' => $request->input('search', ''),
                    'sort' => $sortField,
                    'direction' => $sortDirection,
                ],
            ]);
        }

        // 2. Data Scope for IT Staff (Helpdesk, Team, Manager)
        $baseQuery = HelpdeskCase::query();

        // ถ้าเป็นช่างเฉพาะทาง (Tier 2) ให้เห็นเฉพาะงานที่ Assign/Escalate มาที่ทีมตัวเอง
        if ($user->role === 'technician' || in_array($user->role, ['team_hardware', 'team_network', 'team_software'], true)) {
            if ($user->teams()->exists()) {
                $userTeamNames = $user->teams()->pluck('name')->toArray();
                $baseQuery->whereIn('escalated_to_team', $userTeamNames);
            } else {
                $teamMapping = [
                    'team_hardware' => 'Hardware',
                    'team_network' => 'Network',
                    'team_software' => 'Software',
                ];
                if (isset($teamMapping[$user->role])) {
                    $baseQuery->where('escalated_to_team', $teamMapping[$user->role]);
                }
            }
        } elseif ($user->teams()->exists() && ! in_array($user->role, ['administrator', 'manager', 'helpdesk'])) {
            $userTeamNames = $user->teams()->pluck('name')->toArray();
            $baseQuery->whereIn('escalated_to_team', $userTeamNames);
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
            $managedTeams = $user->managedTeams()->with('members')->get();

            if ($user->role === 'manager_software') {
                $targetRoles = ['team_software'];
                $teamNames = ['Software'];
                $teamTitle = 'ทีม Software (Tier 2 ในความรับผิดชอบ)';
                $technicians = User::whereIn('role', $targetRoles)->where('is_active', true)->orderBy('name')->get();
            } elseif ($user->role === 'manager_network_hardware') {
                $targetRoles = ['team_hardware', 'team_network'];
                $teamNames = ['Hardware', 'Network'];
                $teamTitle = 'ทีม Network & Hardware (Tier 2 ในความรับผิดชอบ)';
                $technicians = User::whereIn('role', $targetRoles)->where('is_active', true)->orderBy('name')->get();
            } elseif ($managedTeams->isNotEmpty()) {
                $teamNames = $managedTeams->pluck('name')->toArray();
                $teamTitle = 'ทีมในความรับผิดชอบ ('.$managedTeams->pluck('name')->join(', ').')';
                $technicianIds = DB::table('team_user')
                    ->whereIn('team_id', $managedTeams->pluck('id'))
                    ->pluck('user_id');
                $technicians = User::whereIn('id', $technicianIds)->where('is_active', true)->orderBy('name')->get();
            } else {
                $teamNames = Team::where('is_active', true)->pluck('name')->toArray();
                if (empty($teamNames)) {
                    $teamNames = ['Hardware', 'Network', 'Software'];
                }
                $teamTitle = 'ทีมช่าง IT ทั้งหมด (Tier 2)';
                $technicians = User::where(function ($q) {
                    $q->whereIn('role', ['technician', 'team_hardware', 'team_network', 'team_software'])
                        ->orWhereHas('teams');
                })->where('is_active', true)->orderBy('name')->get();
            }

            $roleLabelMap = [
                'technician' => 'Technical Specialist',
                'team_hardware' => 'Team Hardware',
                'team_network' => 'Team Network',
                'team_software' => 'Team Software',
            ];

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

        // คำนวณสถิติสำหรับ Quick Tabs ของช่าง Tier 2 และ Helpdesk Tier 1
        $myWorkCount = 0;
        $teamQueueCount = 0;
        if ($user->role === 'technician' || in_array($user->role, ['team_hardware', 'team_network', 'team_software'], true) || $user->teams()->exists()) {
            $myWorkCount = (clone $baseQuery)
                ->where(function ($q) use ($user) {
                    $q->where('in_progress_by', $user->id)
                        ->orWhere('analyzing_by', $user->id);
                })
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->count();

            $teamQueueCount = (clone $baseQuery)
                ->where('status', 'assigned')
                ->whereNull('in_progress_by')
                ->whereNull('analyzing_by')
                ->count();
        }
        $stats['my_work_count'] = $myWorkCount;
        $stats['team_queue_count'] = $teamQueueCount;
        $stats['pending_triage_count'] = HelpdeskCase::where('status', 'pending')->count();

        // กรองข้อมูลตาม Filter จาก URL (ถ้ามี)
        $query = clone $baseQuery;
        $query->filter($request->only(['search', 'status', 'priority', 'category', 'department', 'team', 'preventive', 'technician_id', 'tier2_tab', 'manager_tab']));

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['ticket_no', 'title', 'requester_name', 'priority', 'escalated_to_team', 'status', 'created_at', 'sla_due_at'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        if ($sortField === 'priority') {
            $query->orderByRaw($sortDirection === 'desc'
                ? "CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END"
                : "CASE priority WHEN 'low' THEN 1 WHEN 'medium' THEN 2 WHEN 'high' THEN 3 WHEN 'urgent' THEN 4 ELSE 5 END"
            );
        } elseif ($sortField === 'status') {
            $query->orderByRaw($sortDirection === 'asc'
                ? "CASE status WHEN 'pending' THEN 1 WHEN 'assigned' THEN 2 WHEN 'analyzing' THEN 3 WHEN 'in_progress' THEN 4 WHEN 'resolved' THEN 5 WHEN 'approved' THEN 6 WHEN 'closed' THEN 7 WHEN 'cancelled' THEN 8 ELSE 9 END"
                : "CASE status WHEN 'cancelled' THEN 1 WHEN 'closed' THEN 2 WHEN 'approved' THEN 3 WHEN 'resolved' THEN 4 WHEN 'in_progress' THEN 5 WHEN 'analyzing' THEN 6 WHEN 'assigned' THEN 7 WHEN 'pending' THEN 8 ELSE 9 END"
            );
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        if ($sortField !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $cases = $query->paginate(10)->withQueryString();

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
            'teams' => Team::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'technician_workload' => $technicianWorkload,
            'filters' => array_merge(
                $request->only(['search', 'status', 'priority', 'category', 'department', 'team', 'preventive', 'technician_id', 'tier2_tab', 'manager_tab', 'view']),
                ['sort' => $sortField, 'direction' => $sortDirection]
            ),
        ]);
    }
}
