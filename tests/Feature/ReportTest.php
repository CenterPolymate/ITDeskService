<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_reports(): void
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_reports_and_gets_403(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));
        $response->assertForbidden();
    }

    public function test_manager_can_access_reports_and_see_inertia_component(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($manager)->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('filters')
            ->has('stats')
            ->has('fiveMDistribution')
            ->has('topRootCauses')
            ->has('pcarIncidents')
        );
    }

    public function test_administrator_can_access_reports(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('stats')
            ->has('companies')
            ->has('thaiMonthNames')
        );
    }

    public function test_reports_computes_kpi_and_5m_pcar_distribution_correctly(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'email_verified_at' => now(),
        ]);

        $now = Carbon::create(2026, 10, 15, 10, 0, 0, 'Asia/Bangkok');
        Carbon::setTestNow($now);

        // Case 1: Man (คน) - SLA met
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0, 0, 'Asia/Bangkok'));
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0001',
            'pcar_no' => 'PCAR-202610-0001',
            'title' => 'เคสที่ 1 ปัญหาการใช้งาน',
            'description' => 'รายละเอียดปัญหา',
            'category' => 'Hardware',
            'requester_name' => 'Tester 1',
            'requester_phone' => '0812345678',
            'location' => 'Line 1',
            'status' => 'closed',
            'sla_due_at' => Carbon::create(2026, 10, 5, 13, 0, 0, 'Asia/Bangkok'),
            'resolved_at' => Carbon::create(2026, 10, 5, 11, 0, 0, 'Asia/Bangkok'), // SLA met
            'root_cause_category' => 'คน - ไม่รู้มาตรฐาน',
            'root_cause_detail' => 'พนักงานใหม่ยังไม่ได้รับการอบรม',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'done',
            'rating' => 5,
        ]);

        // Case 2: Machine (เครื่องจักร) - SLA breached
        Carbon::setTestNow(Carbon::create(2026, 10, 10, 8, 0, 0, 'Asia/Bangkok'));
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0002',
            'pcar_no' => 'PCAR-202610-0002',
            'title' => 'เคสที่ 2 เซิร์ฟเวอร์ดับ',
            'description' => 'พัดลมระบายความร้อนหยุดทำงาน',
            'category' => 'Hardware',
            'requester_name' => 'Tester 2',
            'requester_phone' => '0812345679',
            'location' => 'Server Room',
            'status' => 'resolved',
            'sla_due_at' => Carbon::create(2026, 10, 10, 12, 0, 0, 'Asia/Bangkok'),
            'resolved_at' => Carbon::create(2026, 10, 10, 15, 0, 0, 'Asia/Bangkok'), // SLA breached
            'root_cause_category' => 'เครื่องจักร - ออกแบบไม่ดี',
            'root_cause_detail' => 'การระบายอากาศไม่เพียงพอ',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'in_progress',
            'rating' => 4,
        ]);

        // Case 3: Cancelled
        Carbon::setTestNow(Carbon::create(2026, 10, 12, 10, 0, 0, 'Asia/Bangkok'));
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0003',
            'title' => 'เคสที่ 3 ยกเลิก',
            'description' => 'แจ้งผิดแผนก',
            'category' => 'Other',
            'requester_name' => 'Tester 3',
            'requester_phone' => '0812345670',
            'location' => 'Office',
            'status' => 'cancelled',
            'cancelled_at' => Carbon::create(2026, 10, 12, 10, 30, 0, 'Asia/Bangkok'),
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 15, 10, 0, 0, 'Asia/Bangkok'));

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('stats.total_tickets', 3)
            ->where('stats.resolved_tickets', 2)
            ->where('stats.cancelled_tickets', 1)
            ->where('stats.resolution_rate', 100) // 2 resolved out of 2 non-cancelled
            ->where('stats.sla_met_count', 1)
            ->where('stats.sla_breached_count', 1)
            ->where('stats.pcar_total', 2)
            ->where('stats.pcar_done', 1)
            ->where('stats.pcar_pending', 1)
            ->where('stats.avg_csat', 4.5) // (5 + 4) / 2
            ->where('fiveMDistribution.0.count', 1) // คน (Man)
            ->where('fiveMDistribution.1.count', 1) // เครื่องจักร (Machine)
            ->has('pcarIncidents', 2)
        );

        Carbon::setTestNow();
    }

    public function test_reports_filter_by_company_and_team_works(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'email_verified_at' => now(),
        ]);

        Company::create(['name' => 'Company A', 'sla_type' => 'Standard', 'is_active' => true]);
        Company::create(['name' => 'Company B', 'sla_type' => 'Standard', 'is_active' => true]);

        // Ticket in Company A
        Carbon::setTestNow(Carbon::create(2026, 10, 2, 10, 0, 0, 'Asia/Bangkok'));
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0010',
            'title' => 'เคส A',
            'description' => 'ปัญหา A',
            'category' => 'Hardware',
            'requester_name' => 'User A',
            'requester_phone' => '0812345671',
            'company' => 'Company A',
            'escalated_to_team' => 'team_hardware',
            'status' => 'closed',
            'root_cause_category' => 'วัสดุ - ไม่ได้กำหนดมาตรฐาน',
        ]);

        // Ticket in Company B
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 10, 0, 0, 'Asia/Bangkok'));
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0011',
            'title' => 'เคส B',
            'description' => 'ปัญหา B',
            'category' => 'Network',
            'requester_name' => 'User B',
            'requester_phone' => '0812345672',
            'company' => 'Company B',
            'escalated_to_team' => 'team_network',
            'status' => 'closed',
            'root_cause_category' => 'วิธีการ - ไม่มีมาตรฐาน',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 15, 10, 0, 0, 'Asia/Bangkok'));

        // Filter by Company A
        $responseA = $this->actingAs($admin)->get(route('reports.index', [
            'month' => 10,
            'year' => 2026,
            'company' => 'Company A',
        ]));

        $responseA->assertOk();
        $responseA->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.total_tickets', 1)
            ->where('pcarIncidents.0.ticket_no', 'IT-202610-0010')
            ->where('filters.company', 'Company A')
        );

        // Filter by team_network
        $responseNet = $this->actingAs($admin)->get(route('reports.index', [
            'month' => 10,
            'year' => 2026,
            'team' => 'team_network',
        ]));

        $responseNet->assertOk();
        $responseNet->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.total_tickets', 1)
            ->where('pcarIncidents.0.ticket_no', 'IT-202610-0011')
            ->where('filters.team', 'team_network')
        );
    }

    public function test_regular_user_cannot_export_reports(): void
    {
        $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('reports.export'));
        $response->assertForbidden();
    }

    public function test_manager_can_export_reports_as_csv_with_bom(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'email_verified_at' => now()]);

        Carbon::setTestNow(Carbon::create(2026, 10, 5, 10, 0, 0, 'Asia/Bangkok'));

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0099',
            'pcar_no' => 'PCAR-202610-0099',
            'title' => 'ทดสอบ Export รายงาน',
            'description' => 'รายละเอียดปัญหาสำหรับทดสอบส่งออกรายงาน',
            'category' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง ทดสอบ',
            'requester_phone' => '0812345678',
            'location' => 'Line 1',
            'department' => 'IT',
            'company' => 'Polymate',
            'priority' => 'high',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'root_cause_category' => 'เครื่องจักร',
            'root_cause_detail' => 'เครื่องจักร - ขาดการบำรุงรักษา',
            'resolved_at' => Carbon::create(2026, 10, 5, 12, 0, 0, 'Asia/Bangkok'),
            'closed_at' => Carbon::create(2026, 10, 5, 13, 0, 0, 'Asia/Bangkok'),
            'rating' => 5,
        ]);

        $response = $this->actingAs($manager)->get(route('reports.export', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Check BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check Content
        $this->assertStringContainsString('รายงานสถิติประจำเดือนและการวิเคราะห์ P-CAR Form 4', $content);
        $this->assertStringContainsString('ตุลาคม พ.ศ. 2569', $content);
        $this->assertStringContainsString('IT-202610-0099', $content);
        $this->assertStringContainsString('PCAR-202610-0099', $content);
        $this->assertStringContainsString('เครื่องจักร - ขาดการบำรุงรักษา', $content);
    }
}
