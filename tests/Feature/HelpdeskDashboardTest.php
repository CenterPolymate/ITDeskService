<?php

namespace Tests\Feature;

use App\Models\HelpdeskCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HelpdeskDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the Helpdesk dashboard page renders successfully with case listings.
     */
    public function test_dashboard_page_renders_successfully(): void
    {
        $user = User::factory()->create(['role' => 'helpdesk']);
        $this->actingAs($user);

        HelpdeskCase::create([
            'ticket_no' => 'ITD-2026-999',
            'title' => 'ทดสอบเคสแจ้งซ่อมจอภาพ',
            'description' => 'รายละเอียดการทดสอบ',
            'category' => 'Hardware',
            'priority' => 'high',
            'status' => 'pending',
            'requester_name' => 'ทดสอบ สมมติ',
            'department' => 'ฝ่ายทดสอบ',
            'requester_phone' => '080-000-0000',
            'location' => 'Test Location',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/ItStaffDashboard')
            ->has('cases.data', 1)
            ->where('cases.data.0.ticket_no', 'ITD-2026-999')
            ->where('cases.data.0.title', 'ทดสอบเคสแจ้งซ่อมจอภาพ')
            ->has('stats')
        );
    }

    /**
     * Test that Administrator dashboard renders SuperadminDashboard component.
     */
    public function test_superadmin_dashboard_renders_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/SuperadminDashboard')
            ->has('stats')
            ->has('chartLabels')
            ->has('chartData')
        );
    }

    /**
     * Test creating a new helpdesk case via form submission.
     */
    public function test_new_case_can_be_created(): void
    {
        $user = User::factory()->create(['name' => 'สมชาย มั่นคง', 'role' => 'user', 'department' => 'IT']);
        $this->actingAs($user);

        $payload = [
            'title' => 'ปัญหาต่อ VPN ไม่ได้',
            'category' => 'Network',
            'priority' => 'urgent',
            'requester_name' => 'สมชาย มั่นคง',
            'requester_phone' => '081-234-5678',
            'requester_email' => 'somchai@company.co.th',
            'department' => 'ฝ่ายการตลาด',
            'location' => 'อาคาร 2 ชั้น 3',
            'is_machine_stopped' => false,
            'description' => 'VPN ฟ้อง Authentication Failed เมื่อเชื่อมต่อจากที่บ้าน',
        ];

        $response = $this->post(route('tickets.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('helpdesk_cases', [
            'title' => 'ปัญหาต่อ VPN ไม่ได้',
            'requester_name' => 'สมชาย มั่นคง',
            'status' => 'pending',
        ]);
    }

    /**
     * Test updating a case status and assigning a technician.
     */
    public function test_case_status_can_be_updated_to_resolved(): void
    {
        $user = User::factory()->create(['role' => 'helpdesk', 'department' => 'IT']);
        $this->actingAs($user);

        $case = HelpdeskCase::create([
            'ticket_no' => 'ITD-2026-050',
            'title' => 'เมาส์เสีย',
            'description' => 'คลิกซ้ายไม่ติด',
            'category' => 'Hardware',
            'priority' => 'low',
            'status' => 'in_progress',
            'requester_name' => 'กานดา สดใส',
            'department' => 'ฝ่ายบุคคล',
            'requester_phone' => '080-000-0000',
            'location' => 'Test Location',
        ]);

        $response = $this->put(route('tickets.updateStatus', $case->id), [
            'status' => 'resolved',
            'action' => 'resolve',
            'resolution_notes' => 'เปลี่ยนเมาส์สำรองให้เรียบร้อยแล้ว',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();

        $this->assertSame('resolved', $case->status);
        $this->assertSame('เปลี่ยนเมาส์สำรองให้เรียบร้อยแล้ว', $case->resolution_notes);
    }

    /**
     * Test that the case detail page renders successfully.
     */
    public function test_case_detail_page_renders_successfully(): void
    {
        $user = User::factory()->create(['role' => 'helpdesk']);
        $this->actingAs($user);

        $case = HelpdeskCase::create([
            'ticket_no' => 'ITD-2026-100',
            'title' => 'โปรเจกเตอร์ภาพไม่ออก',
            'description' => 'กดเปิดแล้วไฟติดแต่ภาพไม่ขึ้นจอ',
            'category' => 'Hardware',
            'priority' => 'high',
            'status' => 'pending',
            'requester_name' => 'วรรณา ทดสอบ',
            'department' => 'ฝ่ายวิจัย',
            'location' => 'ห้องประชุม 5A',
            'requester_phone' => '080-000-0000',
        ]);

        $response = $this->get(route('tickets.show', $case->id));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tickets/Show')
            ->has('ticket')
            ->where('ticket.ticket_no', 'ITD-2026-100')
            ->where('ticket.title', 'โปรเจกเตอร์ภาพไม่ออก')
            ->where('ticket.requester_name', 'วรรณา ทดสอบ')
            ->where('ticket.location', 'ห้องประชุม 5A')
        );
    }
}
