<?php

namespace Tests\Feature;

use App\Models\HelpdeskCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_dashboard_renders_inertia_user_dashboard_component(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'user@test.com',
        ]);

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0001',
            'title' => 'คอมพิวเตอร์เปิดไม่ติด',
            'description' => 'กดปุ่มเปิดแล้วไม่มีไฟขึ้น',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'pending',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => '081-111-2222',
            'location' => 'ตึก A ชั้น 2',
        ]);

        // เคสของผู้อื่น (ไม่ควรแสดงในหน้าของ user นี้)
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0002',
            'title' => 'เคสของผู้อื่น',
            'description' => 'ทดสอบเคสอื่น',
            'category' => 'Software',
            'priority' => 'low',
            'status' => 'pending',
            'requester_name' => 'Other User',
            'requester_email' => 'other@test.com',
            'requester_phone' => '082-222-3333',
            'location' => 'ตึก B ชั้น 1',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/UserDashboard')
            ->has('cases.data', 1)
            ->where('cases.data.0.ticket_no', 'IT-202610-0001')
            ->has('stats')
            ->where('stats.total', 1)
            ->where('stats.pending', 1)
            ->where('stats.waiting_acceptance', 0)
        );
    }

    public function test_user_can_filter_cases_by_status_and_search(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'user@test.com',
        ]);

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0001',
            'title' => 'จอภาพดับ',
            'description' => 'จอภาพดับขณะใช้งาน',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'in_progress',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => '081-111-2222',
            'location' => 'ตึก A ชั้น 2',
        ]);

        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0002',
            'title' => 'โปรแกรมเปิดไม่ได้',
            'description' => 'เปิดโปรแกรมบัญชีแล้ว Error',
            'category' => 'Software',
            'priority' => 'high',
            'status' => 'resolved',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => '081-111-2222',
            'location' => 'ตึก A ชั้น 2',
        ]);

        // ทดสอบ Filter status = resolved
        $responseFilter = $this->actingAs($user)->get(route('dashboard', ['status' => 'resolved']));
        $responseFilter->assertOk();
        $responseFilter->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/UserDashboard')
            ->has('cases.data', 1)
            ->where('cases.data.0.ticket_no', 'IT-202610-0002')
            ->where('stats.waiting_acceptance', 1)
        );

        // ทดสอบ Search keyword
        $responseSearch = $this->actingAs($user)->get(route('dashboard', ['search' => 'จอภาพดับ']));
        $responseSearch->assertOk();
        $responseSearch->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/UserDashboard')
            ->has('cases.data', 1)
            ->where('cases.data.0.ticket_no', 'IT-202610-0001')
        );
    }
}
