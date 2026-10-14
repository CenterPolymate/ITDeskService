<?php

namespace Tests\Feature;

use App\Models\HelpdeskCase;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SlaAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SlaAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_warning_command_detects_and_notifies_tickets_near_sla_due_date(): void
    {
        Notification::fake();

        $helpdesk = User::factory()->create([
            'role' => 'helpdesk',
            'is_active' => true,
        ]);

        $ticket = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0001',
            'title' => 'คอมพิวเตอร์เปิดไม่ติด',
            'description' => 'กดปุ่มเปิดแล้วไม่มีไฟขึ้นและพัดลมไม่หมุน',
            'category' => 'Hardware',
            'priority' => 'high',
            'status' => 'in_progress',
            'requester_name' => 'Somchai',
            'department' => 'Accounting',
            'sla_due_at' => now()->addMinutes(35), // within 60 min threshold
            'sla_warning_sent_at' => null,
        ]);

        $this->artisan('sla:check-warnings')
            ->assertSuccessful();

        Notification::assertSentTo(
            $helpdesk,
            SlaAlertNotification::class,
            function ($notification) use ($ticket) {
                return $notification->ticket->id === $ticket->id
                    && $notification->type === 'warning'
                    && $notification->remainingMinutes <= 36;
            }
        );

        $ticket->refresh();
        $this->assertNotNull($ticket->sla_warning_sent_at);
    }

    public function test_sla_warning_command_detects_and_notifies_breached_sla_tickets(): void
    {
        Notification::fake();

        $manager = User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
        ]);

        $ticket = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0002',
            'title' => 'เซิร์ฟเวอร์ฐานข้อมูลดับ',
            'description' => 'ระบบ ERP เข้าใช้งานไม่ได้ทั้งบริษัท',
            'category' => 'Network',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'requester_name' => 'Somsri',
            'department' => 'IT',
            'sla_due_at' => now()->subMinutes(10), // already overdue
            'sla_breached_sent_at' => null,
        ]);

        $this->artisan('sla:check-warnings')
            ->assertSuccessful();

        Notification::assertSentTo(
            $manager,
            SlaAlertNotification::class,
            function ($notification) use ($ticket) {
                return $notification->ticket->id === $ticket->id
                    && $notification->type === 'breached';
            }
        );

        $ticket->refresh();
        $this->assertNotNull($ticket->sla_breached_sent_at);
    }

    public function test_sla_warning_command_does_not_send_duplicate_notifications(): void
    {
        Notification::fake();

        $helpdesk = User::factory()->create([
            'role' => 'helpdesk',
            'is_active' => true,
        ]);

        // Already notified 10 minutes ago
        $ticket = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0003',
            'title' => 'พิมพ์งานไม่ได้',
            'description' => 'เครื่องพิมพ์ขึ้นกระดาษติด',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'in_progress',
            'requester_name' => 'Somsak',
            'department' => 'HR',
            'sla_due_at' => now()->addMinutes(20),
            'sla_warning_sent_at' => now()->subMinutes(10),
        ]);

        $this->artisan('sla:check-warnings')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_sla_alert_can_be_disabled_via_settings(): void
    {
        Notification::fake();

        Setting::updateOrCreate(['key' => 'sla_alert_enabled'], ['value' => '0']);

        User::factory()->create(['role' => 'helpdesk', 'is_active' => true]);

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0004',
            'title' => 'อินเทอร์เน็ตหลุด',
            'description' => 'หลุดเป็นช่วงๆ',
            'category' => 'Network',
            'priority' => 'low',
            'status' => 'in_progress',
            'requester_name' => 'Wichai',
            'department' => 'Sales',
            'sla_due_at' => now()->addMinutes(25),
            'sla_warning_sent_at' => null,
        ]);

        $this->artisan('sla:check-warnings')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_sla_scopes_and_attributes_work_correctly(): void
    {
        $nearOverdueTicket = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0005',
            'title' => 'เคสใกล้หมดเวลา',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'in_progress',
            'requester_name' => 'User1',
            'department' => 'HR',
            'sla_due_at' => now()->addMinutes(40),
        ]);

        $overdueTicket = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0006',
            'title' => 'เคสเกินกำหนด',
            'description' => 'รายละเอียด',
            'category' => 'Network',
            'priority' => 'high',
            'status' => 'in_progress',
            'requester_name' => 'User2',
            'department' => 'Finance',
            'sla_due_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue($nearOverdueTicket->is_near_overdue);
        $this->assertFalse($nearOverdueTicket->is_overdue);

        $this->assertTrue($overdueTicket->is_overdue);
        $this->assertFalse($overdueTicket->is_near_overdue);

        $this->assertEquals(1, HelpdeskCase::nearOverdue(60)->count());
        $this->assertEquals(1, HelpdeskCase::activeOverdue()->count());
    }

    public function test_admin_can_trigger_sla_check_via_controller(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('settings.run_sla_check'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_dashboard_filters_for_sla_warning_and_sla_overdue(): void
    {
        $itStaff = User::factory()->create([
            'role' => 'helpdesk',
            'is_active' => true,
        ]);

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0007',
            'title' => 'เคสแจ้งเตือน SLA',
            'description' => 'ใกล้ถึงกำหนด',
            'category' => 'Hardware',
            'priority' => 'high',
            'status' => 'in_progress',
            'requester_name' => 'Test',
            'department' => 'IT',
            'sla_due_at' => now()->addMinutes(30),
        ]);

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0008',
            'title' => 'เคสปกติ',
            'description' => 'เหลือเวลาอีกนาน',
            'category' => 'Hardware',
            'priority' => 'low',
            'status' => 'in_progress',
            'requester_name' => 'Test',
            'department' => 'IT',
            'sla_due_at' => now()->addHours(3),
        ]);

        $response = $this->actingAs($itStaff)->get(route('dashboard', ['status' => 'sla_warning']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/ItStaffDashboard')
            ->has('cases.data', 1)
            ->where('cases.data.0.ticket_no', 'IT-202610-0007')
        );
    }
}
