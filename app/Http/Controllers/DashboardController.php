<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'administrator') {
            $totalTickets = HelpdeskCase::count();
            $resolvedTickets = HelpdeskCase::whereIn('status', ['resolved', 'approved', 'closed', 'cancelled'])->count();

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

            return view('dashboard-superadmin', compact('stats', 'chartLabels', 'chartData'));
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
            $cases = $query->orderBy('created_at', 'desc')->paginate(10);

            return view('dashboard-user', compact('cases', 'stats'));
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
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'assigned'])->count(),
            'analyzing' => (clone $baseQuery)->where('status', 'analyzing')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'testing' => (clone $baseQuery)->whereIn('status', ['resolved', 'approved'])->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'completed' => (clone $baseQuery)->whereIn('status', ['closed', 'cancelled'])->count(),
            'preventive_pending' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where(function ($q) {
                $q->whereNull('preventive_measure')->orWhereIn('preventive_measure', ['in_progress', 'pending_review']);
            })->count(),
            'preventive_completed' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'done')->count(),
            'urgent' => (clone $baseQuery)->where('priority', 'urgent')->whereNotIn('status', ['closed', 'cancelled'])->count(),

            // Task 2 Stats
            'task2_total' => (clone $baseQuery)->where('status', 'closed')->count(),
            'task2_manager_review' => (clone $baseQuery)->where('status', 'closed')->whereNull('requires_preventive_measure')->count(),
            'task2_assigned' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where(function ($q) {
                $q->whereNull('preventive_measure')->orWhere('preventive_measure', 'assigned');
            })->count(),
            'task2_in_progress' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'in_progress')->count(),
            'task2_pending_review' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'pending_review')->count(),
            'task2_completed' => (clone $baseQuery)->where('status', 'closed')->where('requires_preventive_measure', true)->where('preventive_measure', 'done')->count(),
        ];

        // กรองข้อมูลตาม Filter จาก URL (ถ้ามี)
        $query = clone $baseQuery;
        $query->filter($request->only(['search', 'status', 'priority', 'category', 'department', 'team', 'preventive']));

        $cases = $query->orderBy('created_at', 'desc')->paginate(10);

        // คำนวณเปอร์เซ็นต์
        $stats['percent_pending'] = $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100) : 0;
        $stats['percent_analyzing'] = $stats['total'] > 0 ? round(($stats['analyzing'] / $stats['total']) * 100) : 0;
        $stats['percent_in_progress'] = $stats['total'] > 0 ? round(($stats['in_progress'] / $stats['total']) * 100) : 0;
        $stats['percent_testing'] = $stats['total'] > 0 ? round(($stats['testing'] / $stats['total']) * 100) : 0;
        $stats['percent_completed'] = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;

        // Percentages for Task 2 relative to Task 2 Total
        $t2_total = $stats['task2_total'];
        $stats['percent_t2_manager'] = $t2_total > 0 ? round(($stats['task2_manager_review'] / $t2_total) * 100) : 0;
        $stats['percent_t2_assigned'] = $t2_total > 0 ? round(($stats['task2_assigned'] / $t2_total) * 100) : 0;
        $stats['percent_t2_in_progress'] = $t2_total > 0 ? round(($stats['task2_in_progress'] / $t2_total) * 100) : 0;
        $stats['percent_t2_pending'] = $t2_total > 0 ? round(($stats['task2_pending_review'] / $t2_total) * 100) : 0;
        $stats['percent_t2_completed'] = $t2_total > 0 ? round(($stats['task2_completed'] / $t2_total) * 100) : 0;

        return view('dashboard-admin', compact('cases', 'stats'));
    }
}
