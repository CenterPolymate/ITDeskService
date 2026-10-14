<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ticket_page_renders_inertia_component_with_categories(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'company' => 'Polymate',
            'department' => 'IT',
        ]);

        Category::create(['name' => 'Network Issue', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('tickets.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tickets/Create')
            ->has('categories', 1)
            ->where('categories.0.name', 'Network Issue')
        );
    }

    public function test_user_can_create_ticket_with_valid_data(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'user',
            'company' => 'Polymate',
            'department' => 'IT',
            'email' => 'user@example.com',
        ]);

        Company::create([
            'name' => 'Polymate',
            'code' => 'PM',
            'sla_type' => '8x5',
            'is_active' => true,
        ]);

        $payload = [
            'title' => 'ระบบอินเทอร์เน็ตใช้งานไม่ได้',
            'category' => 'Network',
            'description' => 'ต่อสายแลนแล้วไฟไม่ติด ไม่สามารถเชื่อมต่อระบบได้ทั้งแผนก',
            'location' => 'อาคาร B ชั้น 3',
            'requester_phone' => '089-123-4567',
            'is_machine_stopped' => 0,
            'attachments' => [
                UploadedFile::fake()->create('lan_cable.jpg', 100, 'image/jpeg'),
            ],
        ];

        $response = $this->actingAs($user)->post(route('tickets.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('helpdesk_cases', [
            'title' => 'ระบบอินเทอร์เน็ตใช้งานไม่ได้',
            'requester_email' => 'user@example.com',
            'location' => 'อาคาร B ชั้น 3',
            'requester_phone' => '089-123-4567',
        ]);
    }

    public function test_helpdesk_can_triage_and_assign_ticket(): void
    {
        $helpdesk = User::factory()->create(['role' => 'helpdesk']);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0099',
            'title' => 'จอคอมเปิดไม่ติด',
            'description' => 'จอภาพดับมืด',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'pending',
            'requester_name' => 'สมชาย มั่นคง',
            'requester_email' => 'somchai@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($helpdesk)->put(route('tickets.assign', $case->id), [
            'priority' => 'high',
            'escalated_to_team' => 'Hardware',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('high', $case->priority);
        $this->assertEquals('Hardware', $case->escalated_to_team);
        $this->assertEquals('assigned', $case->status);
    }

    public function test_assigned_team_can_update_analysis_and_resolve_ticket(): void
    {
        $technician = User::factory()->create(['role' => 'team_hardware']);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0100',
            'title' => 'แรมเสีย',
            'description' => 'คอมร้องบี๊บ 3 ครั้ง',
            'category' => 'Hardware',
            'priority' => 'high',
            'status' => 'analyzing',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'สมชาย',
            'requester_email' => 'somchai@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        // Submit RCA
        $rcaResponse = $this->actingAs($technician)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'update_analysis',
            'why_1' => 'แรมหลวม',
            'why_2' => 'ขาแรมมีคราบออกไซด์',
            'why_3' => 'ความชื้นสะสม',
            'root_cause_detail' => 'แรมมีฝุ่นและคราบออกไซด์เกาะที่ขาทองแดง',
        ]);

        $rcaResponse->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('in_progress', $case->status);
        $this->assertEquals('แรมมีฝุ่นและคราบออกไซด์เกาะที่ขาทองแดง', $case->root_cause_detail);

        // Submit Resolve
        $resolveResponse = $this->actingAs($technician)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'resolve',
            'resolution_notes' => 'ทำความสะอาดขาทองแดงด้วยยางลบและใส่กลับเข้าที่เดิม ทดสอบเปิดเครื่องใช้งานได้ปกติ',
        ]);

        $resolveResponse->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('resolved', $case->status);
    }

    public function test_requester_can_accept_resolution_with_csat_rating(): void
    {
        $requester = User::factory()->create([
            'role' => 'user',
            'email' => 'requester@example.com',
        ]);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0101',
            'title' => 'แก้เสร็จแล้ว',
            'description' => 'อาการปกติ',
            'category' => 'Hardware',
            'priority' => 'low',
            'status' => 'resolved',
            'resolved_at' => now(),
            'requester_name' => $requester->name,
            'requester_email' => $requester->email,
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 2',
        ]);

        $response = $this->actingAs($requester)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'accept_resolution',
            'rating' => 5,
            'feedback' => 'ช่างบริการรวดเร็วและสุภาพมาก ขอบคุณครับ',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('closed', $case->status);
        $this->assertEquals(5, $case->rating);
        $this->assertEquals('ช่างบริการรวดเร็วและสุภาพมาก ขอบคุณครับ', $case->feedback);
    }

    public function test_user_can_add_comment_to_ticket(): void
    {
        $user = User::factory()->create(['role' => 'user', 'email' => 'user@example.com']);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0102',
            'title' => 'สอบถามเพิ่มเติม',
            'description' => 'อาการเป็นๆ หายๆ',
            'category' => 'Hardware',
            'priority' => 'low',
            'status' => 'pending',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($user)->post(route('tickets.comments.store', $case->id), [
            'message' => 'ช่างสะดวกเข้ามาดูช่วงบ่ายไหมครับ',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_comments', [
            'helpdesk_case_id' => $case->id,
            'user_id' => $user->id,
            'message' => 'ช่างสะดวกเข้ามาดูช่วงบ่ายไหมครับ',
        ]);
    }
}
