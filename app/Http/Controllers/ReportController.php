<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HelpdeskCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display monthly statistical report and P-CAR analytics.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        if (! in_array($user?->role, ['administrator', 'manager', 'manager_software', 'manager_network_hardware', 'helpdesk'], true)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้ารายงานสถิตินี้');
        }

        $now = Carbon::now('Asia/Bangkok');
        $selectedMonth = (int) $request->input('month', $now->month);
        $selectedYear = (int) $request->input('year', $now->year);
        $selectedCompany = (string) $request->input('company', 'all');
        $selectedTeam = (string) $request->input('team', 'all');

        // Define start and end of selected month
        $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1, 'Asia/Bangkok')->startOfMonth();
        $endDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1, 'Asia/Bangkok')->endOfMonth();

        // Base query for tickets in this period
        $query = HelpdeskCase::whereBetween('created_at', [$startDate, $endDate]);

        if ($selectedCompany !== 'all' && $selectedCompany !== '') {
            $query->where('company', $selectedCompany);
        }

        if ($selectedTeam !== 'all' && $selectedTeam !== '') {
            $query->where('escalated_to_team', $selectedTeam);
        }

        $allTickets = $query->get();

        // 1. KPI Statistics
        $totalTickets = $allTickets->count();
        $resolvedTickets = $allTickets->filter(fn ($t) => in_array($t->status, ['resolved', 'approved', 'closed'], true));
        $closedTickets = $allTickets->where('status', 'closed');
        $cancelledTickets = $allTickets->where('status', 'cancelled');
        $activeTickets = $allTickets->filter(fn ($t) => ! in_array($t->status, ['resolved', 'approved', 'closed', 'cancelled'], true));

        $nonCancelledTotal = $totalTickets - $cancelledTickets->count();
        $resolutionRate = $nonCancelledTotal > 0
            ? round(($resolvedTickets->count() / $nonCancelledTotal) * 100, 1)
            : 0;

        // SLA Compliance
        $slaMetTickets = $resolvedTickets->filter(function ($t) {
            if (! $t->sla_due_at || ! $t->resolved_at) {
                return true;
            }

            return $t->resolved_at->lte($t->sla_due_at);
        });

        $slaBreachedTickets = $resolvedTickets->filter(function ($t) {
            if (! $t->sla_due_at || ! $t->resolved_at) {
                return false;
            }

            return $t->resolved_at->gt($t->sla_due_at);
        });

        // Add currently overdue active tickets
        $activeOverdueCount = $activeTickets->filter(function ($t) {
            return $t->sla_due_at && $t->sla_due_at->isPast();
        })->count();

        $totalBreachedCount = $slaBreachedTickets->count() + $activeOverdueCount;

        $slaComplianceRate = $resolvedTickets->count() > 0
            ? round(($slaMetTickets->count() / $resolvedTickets->count()) * 100, 1)
            : 100.0;

        // Average Resolution Time (in minutes)
        $resolutionDurations = $resolvedTickets->map(function ($t) {
            if ($t->resolved_at && $t->created_at) {
                return $t->created_at->diffInMinutes($t->resolved_at);
            }

            return null;
        })->filter();

        $avgResolutionMinutes = $resolutionDurations->count() > 0
            ? round($resolutionDurations->avg())
            : 0;

        $avgHours = floor($avgResolutionMinutes / 60);
        $avgMins = $avgResolutionMinutes % 60;
        $avgResolutionFormatted = $avgResolutionMinutes > 0
            ? ($avgHours > 0 ? "{$avgHours} ชม. {$avgMins} นาที" : "{$avgMins} นาที")
            : '-';

        // Average CSAT
        $ratedTickets = $allTickets->filter(fn ($t) => $t->rating !== null && $t->rating > 0);
        $avgCsat = $ratedTickets->count() > 0
            ? round((float) $ratedTickets->avg('rating'), 2)
            : 0;

        // 2. P-CAR Task 2 Statistics
        $pcarCases = $allTickets->filter(function ($t) {
            return $t->requires_preventive_measure === true
                || ! empty($t->pcar_no)
                || ! empty($t->root_cause_category);
        });

        $pcarTotal = $pcarCases->count();
        $pcarDone = $pcarCases->filter(fn ($t) => $t->preventive_measure === 'done' || $t->pcar_closed_at !== null)->count();
        $pcarPending = $pcarTotal - $pcarDone;

        // 3. 5M Root Cause Analytics
        $known5MCategories = [
            'คน' => [
                'name' => 'คน (Man)',
                'key' => 'man',
                'color' => '#3b82f6', // blue-500
                'subs' => [
                    'คน - ไม่รู้มาตรฐาน' => 0,
                    'คน - ไม่ทำตามมาตรฐาน' => 0,
                    'คน - ทำตามแล้วยังเกิด' => 0,
                ],
                'count' => 0,
            ],
            'เครื่องจักร' => [
                'name' => 'เครื่องจักร (Machine)',
                'key' => 'machine',
                'color' => '#f59e0b', // amber-500
                'subs' => [
                    'เครื่องจักร - ออกแบบไม่ดี' => 0,
                    'เครื่องจักร - ไม่ได้กำหนดมาตรฐาน' => 0,
                ],
                'count' => 0,
            ],
            'วัสดุ' => [
                'name' => 'วัสดุ (Material)',
                'key' => 'material',
                'color' => '#8b5cf6', // purple-500
                'subs' => [
                    'วัสดุ - ไม่ได้กำหนดมาตรฐาน' => 0,
                    'วัสดุ - กำหนดไม่เหมาะสม' => 0,
                ],
                'count' => 0,
            ],
            'วิธีการ' => [
                'name' => 'วิธีการ (Method)',
                'key' => 'method',
                'color' => '#10b981', // emerald-500
                'subs' => [
                    'วิธีการ - ไม่มีมาตรฐาน' => 0,
                    'วิธีการ - กำหนดไม่เหมาะสม' => 0,
                ],
                'count' => 0,
            ],
            'สิ่งแวดล้อม' => [
                'name' => 'สิ่งแวดล้อม (Environment)',
                'key' => 'environment',
                'color' => '#06b6d4', // cyan-500
                'subs' => [
                    'สิ่งแวดล้อม - ไม่มีมาตรฐาน' => 0,
                    'สิ่งแวดล้อม - กำหนดไม่เหมาะสม' => 0,
                ],
                'count' => 0,
            ],
        ];

        $subCategoryCounts = [];
        $totalRootCauses = 0;

        foreach ($allTickets as $ticket) {
            $cat = trim((string) $ticket->root_cause_category);
            if ($cat === '') {
                continue;
            }

            $totalRootCauses++;

            // Match into 5M
            $matchedMain = null;
            foreach ($known5MCategories as $prefix => $data) {
                if (str_starts_with($cat, $prefix)) {
                    $matchedMain = $prefix;
                    break;
                }
            }

            if ($matchedMain) {
                $known5MCategories[$matchedMain]['count']++;
                if (isset($known5MCategories[$matchedMain]['subs'][$cat])) {
                    $known5MCategories[$matchedMain]['subs'][$cat]++;
                }
            }

            // Global subcategory counter
            $subCategoryCounts[$cat] = ($subCategoryCounts[$cat] ?? 0) + 1;
        }

        // Format 5M Distribution
        $fiveMDistribution = [];
        foreach ($known5MCategories as $prefix => $data) {
            $percentage = $totalRootCauses > 0 ? round(($data['count'] / $totalRootCauses) * 100, 1) : 0;
            $fiveMDistribution[] = [
                'name' => $data['name'],
                'prefix' => $prefix,
                'count' => $data['count'],
                'color' => $data['color'],
                'percentage' => $percentage,
                'subs' => $data['subs'],
            ];
        }

        // Format Top Root Causes sorted by count desc
        arsort($subCategoryCounts);
        $topRootCauses = [];
        foreach ($subCategoryCounts as $causeName => $count) {
            $percentage = $totalRootCauses > 0 ? round(($count / $totalRootCauses) * 100, 1) : 0;
            $topRootCauses[] = [
                'name' => $causeName,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        // 4. Team Distribution
        $teamsMap = [
            'helpdesk' => ['label' => 'Helpdesk (Tier 1)', 'count' => 0, 'color' => '#6366f1'],
            'team_hardware' => ['label' => 'Hardware', 'count' => 0, 'color' => '#3b82f6'],
            'team_network' => ['label' => 'Network', 'count' => 0, 'color' => '#06b6d4'],
            'team_software' => ['label' => 'Software', 'count' => 0, 'color' => '#8b5cf6'],
        ];

        foreach ($allTickets as $t) {
            $team = $t->escalated_to_team;
            if ($team && isset($teamsMap[$team])) {
                $teamsMap[$team]['count']++;
            } elseif ($team) {
                $teamsMap['helpdesk']['count']++;
            }
        }

        $teamDistribution = array_values($teamsMap);

        // 5. Priority Distribution
        $priorityLabels = [
            'urgent' => ['label' => 'ด่วนที่สุด (Urgent)', 'count' => 0, 'color' => '#ef4444'],
            'high' => ['label' => 'สูง (High)', 'count' => 0, 'color' => '#f97316'],
            'medium' => ['label' => 'ปานกลาง (Medium)', 'count' => 0, 'color' => '#f59e0b'],
            'low' => ['label' => 'ทั่วไป (Low)', 'count' => 0, 'color' => '#10b981'],
        ];

        foreach ($allTickets as $t) {
            $p = $t->priority ?: 'low';
            if (isset($priorityLabels[$p])) {
                $priorityLabels[$p]['count']++;
            }
        }

        $priorityDistribution = array_values($priorityLabels);

        // 6. Detailed P-CAR Incidents List for Table
        $pcarIncidentList = $pcarCases->map(function ($t) {
            return [
                'id' => $t->id,
                'ticket_no' => $t->ticket_no,
                'pcar_no' => $t->pcar_no,
                'title' => $t->title,
                'company' => $t->company,
                'department' => $t->department,
                'escalated_to_team' => $t->escalated_to_team,
                'assigned_team_label' => $t->assigned_team_label,
                'root_cause_category' => $t->root_cause_category,
                'root_cause_detail' => $t->root_cause_detail,
                'preventive_measure' => $t->preventive_measure,
                'requires_preventive_measure' => (bool) $t->requires_preventive_measure,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'task2_status_label' => $t->task2_status_label,
                'task2_status_color' => $t->task2_status_color,
                'created_at_formatted' => $t->created_at ? $t->created_at->format('d/m/Y') : '-',
                'pcar_closed_at_formatted' => $t->pcar_closed_at ? $t->pcar_closed_at->format('d/m/Y') : '-',
            ];
        })->values();

        // Dropdown data
        $companies = Company::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
        $thaiMonthNames = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        // Available years (from minimum ticket created year or 2024 to current + 1)
        $minYear = HelpdeskCase::min('created_at');
        $startYear = $minYear ? Carbon::parse($minYear)->year : 2024;
        $endYear = $now->year + 1;
        $years = range(max(2024, $startYear), $endYear);
        rsort($years);

        return Inertia::render('Reports/Index', [
            'filters' => [
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'company' => $selectedCompany,
                'team' => $selectedTeam,
            ],
            'stats' => [
                'total_tickets' => $totalTickets,
                'resolved_tickets' => $resolvedTickets->count(),
                'closed_tickets' => $closedTickets->count(),
                'active_tickets' => $activeTickets->count(),
                'cancelled_tickets' => $cancelledTickets->count(),
                'resolution_rate' => $resolutionRate,
                'sla_compliance_rate' => $slaComplianceRate,
                'sla_met_count' => $slaMetTickets->count(),
                'sla_breached_count' => $totalBreachedCount,
                'avg_resolution_formatted' => $avgResolutionFormatted,
                'avg_resolution_minutes' => $avgResolutionMinutes,
                'avg_csat' => $avgCsat,
                'rated_tickets_count' => $ratedTickets->count(),
                'pcar_total' => $pcarTotal,
                'pcar_done' => $pcarDone,
                'pcar_pending' => $pcarPending,
                'total_root_causes' => $totalRootCauses,
            ],
            'fiveMDistribution' => $fiveMDistribution,
            'topRootCauses' => $topRootCauses,
            'teamDistribution' => $teamDistribution,
            'priorityDistribution' => $priorityDistribution,
            'pcarIncidents' => $pcarIncidentList,
            'companies' => $companies,
            'thaiMonthNames' => $thaiMonthNames,
            'years' => $years,
            'selectedMonthName' => $thaiMonthNames[$selectedMonth] ?? '',
            'buddhistYear' => $selectedYear + 543,
        ]);
    }

    /**
     * Export monthly report to CSV with Excel UTF-8 BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = Auth::user();

        if (! in_array($user?->role, ['administrator', 'manager', 'manager_software', 'manager_network_hardware', 'helpdesk'], true)) {
            abort(403, 'คุณไม่มีสิทธิ์ดาวน์โหลดรายงานสถิตินี้');
        }

        $now = Carbon::now('Asia/Bangkok');
        $selectedMonth = (int) $request->input('month', $now->month);
        $selectedYear = (int) $request->input('year', $now->year);
        $selectedCompany = (string) $request->input('company', 'all');
        $selectedTeam = (string) $request->input('team', 'all');

        $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1, 'Asia/Bangkok')->startOfMonth();
        $endDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1, 'Asia/Bangkok')->endOfMonth();

        $query = HelpdeskCase::whereBetween('created_at', [$startDate, $endDate]);

        if ($selectedCompany !== 'all' && $selectedCompany !== '') {
            $query->where('company', $selectedCompany);
        }

        if ($selectedTeam !== 'all' && $selectedTeam !== '') {
            $query->where('escalated_to_team', $selectedTeam);
        }

        $allTickets = $query->orderBy('created_at', 'desc')->get();

        $thaiMonthNames = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];
        $monthName = $thaiMonthNames[$selectedMonth] ?? (string) $selectedMonth;
        $buddhistYear = $selectedYear + 543;

        // KPI Calculations
        $totalTickets = $allTickets->count();
        $resolvedTickets = $allTickets->filter(fn ($t) => in_array($t->status, ['resolved', 'approved', 'closed'], true));
        $closedTickets = $allTickets->where('status', 'closed');
        $cancelledTickets = $allTickets->where('status', 'cancelled');
        $activeTickets = $allTickets->filter(fn ($t) => ! in_array($t->status, ['resolved', 'approved', 'closed'], true));

        $nonCancelledTotal = $totalTickets - $cancelledTickets->count();
        $resolutionRate = $nonCancelledTotal > 0
            ? round(($resolvedTickets->count() / $nonCancelledTotal) * 100, 1)
            : 0;

        $slaMetTickets = $resolvedTickets->filter(function ($t) {
            if (! $t->sla_due_at || ! $t->resolved_at) {
                return true;
            }

            return $t->resolved_at->lte($t->sla_due_at);
        });

        $slaBreachedTickets = $resolvedTickets->filter(function ($t) {
            if (! $t->sla_due_at || ! $t->resolved_at) {
                return false;
            }

            return $t->resolved_at->gt($t->sla_due_at);
        });

        $activeOverdueCount = $activeTickets->filter(function ($t) {
            return $t->sla_due_at && $t->sla_due_at->isPast();
        })->count();

        $totalBreachedCount = $slaBreachedTickets->count() + $activeOverdueCount;

        $slaComplianceRate = $resolvedTickets->count() > 0
            ? round(($slaMetTickets->count() / $resolvedTickets->count()) * 100, 1)
            : 100.0;

        $resolutionDurations = $resolvedTickets->map(function ($t) {
            if ($t->resolved_at && $t->created_at) {
                return $t->created_at->diffInMinutes($t->resolved_at);
            }

            return null;
        })->filter();

        $avgResolutionMinutes = $resolutionDurations->count() > 0 ? round($resolutionDurations->avg()) : 0;
        $avgHours = floor($avgResolutionMinutes / 60);
        $avgMins = $avgResolutionMinutes % 60;
        $avgResolutionFormatted = $avgResolutionMinutes > 0
            ? ($avgHours > 0 ? "{$avgHours} ชม. {$avgMins} นาที" : "{$avgMins} นาที")
            : '-';

        $ratedTickets = $allTickets->filter(fn ($t) => $t->rating !== null && $t->rating > 0);
        $avgCsat = $ratedTickets->count() > 0 ? round((float) $ratedTickets->avg('rating'), 2) : 0;

        $pcarCases = $allTickets->filter(function ($t) {
            return $t->requires_preventive_measure === true
                || ! empty($t->pcar_no)
                || ! empty($t->root_cause_category);
        });

        $pcarTotal = $pcarCases->count();
        $pcarDone = $pcarCases->filter(fn ($t) => $t->preventive_measure === 'done' || $t->pcar_closed_at !== null)->count();
        $pcarPending = $pcarTotal - $pcarDone;

        $filename = "Monthly_Report_{$selectedYear}_{$selectedMonth}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use (
            $monthName, $buddhistYear, $selectedCompany, $selectedTeam,
            $totalTickets, $activeTickets, $resolvedTickets, $cancelledTickets,
            $resolutionRate, $slaComplianceRate, $slaMetTickets, $totalBreachedCount,
            $avgResolutionFormatted, $avgCsat, $ratedTickets,
            $pcarTotal, $pcarDone, $pcarPending, $pcarCases
        ) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Microsoft Excel

            // Section 1: Executive Summary
            fputcsv($file, ['รายงานสถิติประจำเดือนและการวิเคราะห์ P-CAR Form 4']);
            fputcsv($file, ['ประจำเดือน', "{$monthName} พ.ศ. {$buddhistYear}"]);
            fputcsv($file, ['บริษัท', $selectedCompany === 'all' ? 'ทุกบริษัท' : $selectedCompany]);
            fputcsv($file, ['ทีมที่รับผิดชอบ', $selectedTeam === 'all' ? 'ทุกทีม' : $selectedTeam]);
            fputcsv($file, ['วันที่ส่งออกข้อมูล', Carbon::now('Asia/Bangkok')->translatedFormat('d F Y H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, ['=== สรุปผลตัวชี้วัดหลัก (KPI Overview) ===']);
            fputcsv($file, ['ตัวชี้วัด (KPIs)', 'ค่าสถิติ']);
            fputcsv($file, ['ใบงานทั้งหมด (Total Tickets)', $totalTickets]);
            fputcsv($file, ['กำลังดำเนินการ (Active Tickets)', $activeTickets->count()]);
            fputcsv($file, ['แก้ไขสำเร็จ (Resolved/Closed)', $resolvedTickets->count()]);
            fputcsv($file, ['ยกเลิก (Cancelled)', $cancelledTickets->count()]);
            fputcsv($file, ['อัตราการปิดงานสำเร็จ (Resolution Rate)', "{$resolutionRate}%"]);
            fputcsv($file, ['อัตราการรักษาเวลา SLA (SLA Compliance)', "{$slaComplianceRate}%"]);
            fputcsv($file, ['เคสที่ตรงตามเวลา SLA (SLA Met)', $slaMetTickets->count()]);
            fputcsv($file, ['เคสที่เกินกำหนด SLA (SLA Breached)', $totalBreachedCount]);
            fputcsv($file, ['เวลาซ่อมเฉลี่ย (Average Resolution Time)', $avgResolutionFormatted]);
            fputcsv($file, ['คะแนนความพึงพอใจเฉลี่ย (CSAT)', $avgCsat > 0 ? "{$avgCsat} / 5 ({$ratedTickets->count()} ผู้ประเมิน)" : 'ยังไม่มีการประเมิน']);
            fputcsv($file, ['มาตรการป้องกัน P-CAR ทั้งหมด', $pcarTotal]);
            fputcsv($file, ['มาตรการที่ปิดแล้ว (Completed)', $pcarDone]);
            fputcsv($file, ['มาตรการที่รอดำเนินการ (Pending)', $pcarPending]);
            fputcsv($file, []);

            // Section 2: Detailed P-CAR Incident Table
            fputcsv($file, ['=== รายการใบงานมาตรการป้องกัน (P-CAR Incident List) ===']);
            fputcsv($file, [
                'ลำดับ',
                'Ticket No',
                'PCAR No',
                'หัวข้อปัญหา',
                'ผู้แจ้ง',
                'บริษัท',
                'แผนก',
                'ทีมที่รับผิดชอบ',
                'ความเร่งด่วน',
                'หมวดหมู่ 5M',
                'สาเหตุรากเหง้า (Root Cause)',
                'สถานะใบงาน (Task 1)',
                'สถานะมาตรการ (Task 2)',
                'วันที่แจ้งซ่อม',
                'วันที่ส่งมอบงาน',
                'วันที่ปิดมาตรการ',
                'คะแนน CSAT',
            ]);

            $index = 1;
            foreach ($pcarCases as $ticket) {
                fputcsv($file, [
                    $index++,
                    $ticket->ticket_no,
                    $ticket->pcar_no ?: '-',
                    $ticket->title,
                    $ticket->requester_name,
                    $ticket->company ?: '-',
                    $ticket->department ?: '-',
                    $ticket->assigned_team_label ?: ($ticket->escalated_to_team ?: 'Helpdesk'),
                    $ticket->priority_label ?: $ticket->priority,
                    $ticket->root_cause_category ?: '-',
                    $ticket->root_cause_detail ?: '-',
                    $ticket->status_label ?: $ticket->status,
                    $ticket->task2_status_label ?: '-',
                    $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-',
                    $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : '-',
                    $ticket->pcar_closed_at ? $ticket->pcar_closed_at->format('d/m/Y H:i') : '-',
                    $ticket->rating ? "{$ticket->rating}/5" : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
