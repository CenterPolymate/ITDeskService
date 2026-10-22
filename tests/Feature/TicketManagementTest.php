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

    public function test_user_can_create_ticket_with_exact_10_digit_numeric_phone(): void
    {
        $user = User::factory()->create(['role' => 'user', 'email' => 'user10@example.com']);
        Company::firstOrCreate(['name' => 'Polymate'], [
            'code' => 'PM',
            'sla_type' => '8x5',
            'is_active' => true,
        ]);

        $payload = [
            'title' => 'ทดสอบเบอร์โทร 10 หลัก',
            'category' => 'Hardware',
            'description' => 'รายละเอียดทดสอบ',
            'location' => 'อาคาร 1 ชั้น 2',
            'requester_phone' => '0812345678',
            'is_machine_stopped' => 0,
        ];

        $response = $this->actingAs($user)->post(route('tickets.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('helpdesk_cases', [
            'title' => 'ทดสอบเบอร์โทร 10 หลัก',
            'requester_phone' => '0812345678',
        ]);
    }

    public function test_ticket_creation_rejects_invalid_phone_number_length_or_format(): void
    {
        $user = User::factory()->create(['role' => 'user', 'email' => 'userinv@example.com']);

        // Case 1: 11 digits (เกิน 10 หลัก)
        $response11 = $this->actingAs($user)->post(route('tickets.store'), [
            'title' => 'ทดสอบเบอร์เกิน 10 หลัก',
            'description' => 'รายละเอียดทดสอบ',
            'location' => 'อาคาร 1',
            'requester_phone' => '08123456789',
            'is_machine_stopped' => 0,
        ]);
        $response11->assertSessionHasErrors('requester_phone');

        // Case 2: 9 digits (ไม่ครบ 10 หลัก)
        $response9 = $this->actingAs($user)->post(route('tickets.store'), [
            'title' => 'ทดสอบเบอร์ไม่ครบ 10 หลัก',
            'description' => 'รายละเอียดทดสอบ',
            'location' => 'อาคาร 1',
            'requester_phone' => '081234567',
            'is_machine_stopped' => 0,
        ]);
        $response9->assertSessionHasErrors('requester_phone');

        // Case 3: Letters included (มีตัวอักษร)
        $responseChar = $this->actingAs($user)->post(route('tickets.store'), [
            'title' => 'ทดสอบมีตัวอักษร',
            'description' => 'รายละเอียดทดสอบ',
            'location' => 'อาคาร 1',
            'requester_phone' => '081234567a',
            'is_machine_stopped' => 0,
        ]);
        $responseChar->assertSessionHasErrors('requester_phone');
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
            'category' => 'Hardware',
            'priority' => 'high',
            'escalated_to_team' => 'Hardware',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('Hardware', $case->category);
        $this->assertEquals('high', $case->priority);
        $this->assertEquals('Hardware', $case->escalated_to_team);
        $this->assertEquals('assigned', $case->status);
        $this->assertDatabaseHas('categories', ['name' => 'Hardware']);
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

    public function test_tier2_cannot_see_csat_rating_and_feedback(): void
    {
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0103',
            'title' => 'เคสที่มีการประเมินแล้ว',
            'description' => 'ช่างเข้าซ่อมเสร็จแล้ว',
            'category' => 'Hardware',
            'priority' => 'low',
            'status' => 'closed',
            'rating' => 4,
            'feedback' => 'บริการดีมาก',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $tier2Hardware = User::factory()->create(['role' => 'team_hardware']);
        $tier2Network = User::factory()->create(['role' => 'team_network']);
        $tier2Software = User::factory()->create(['role' => 'team_software']);

        foreach ([$tier2Hardware, $tier2Network, $tier2Software] as $tech) {
            $response = $this->actingAs($tech)->get(route('tickets.show', $case->id));
            $response->assertOk();
            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tickets/Show')
                ->missing('ticket.rating')
                ->missing('ticket.feedback')
            );
        }

        // Helpdesk and Manager CAN see rating and feedback
        $helpdesk = User::factory()->create(['role' => 'helpdesk']);
        $manager = User::factory()->create(['role' => 'manager']);

        foreach ([$helpdesk, $manager] as $staff) {
            $response = $this->actingAs($staff)->get(route('tickets.show', $case->id));
            $response->assertOk();
            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tickets/Show')
                ->where('ticket.rating', 4)
                ->where('ticket.feedback', 'บริการดีมาก')
            );
        }
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

    public function test_technician_can_save_resolution_notes_without_changing_in_progress_status(): void
    {
        $technician = User::factory()->create(['role' => 'team_hardware', 'email' => 'tech@example.com']);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0103',
            'title' => 'กำลังดำเนินการซ่อม',
            'description' => 'เครื่องเปิดไม่ติด',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'in_progress',
            'escalated_to_team' => 'Hardware',
            'in_progress_at' => now(),
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($technician)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'save_notes',
            'resolution_notes' => 'ถอดเช็คบอร์ดเบื้องต้น กำลังรออะไหล่ทดแทน',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('in_progress', $case->status);
        $this->assertEquals('ถอดเช็คบอร์ดเบื้องต้น กำลังรออะไหล่ทดแทน', $case->resolution_notes);
        $this->assertNull($case->resolved_at);
    }

    public function test_technician_can_handover_work_to_requester_transitioning_to_resolved(): void
    {
        $technician = User::factory()->create(['role' => 'team_hardware', 'email' => 'tech2@example.com']);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0104',
            'title' => 'เปลี่ยนชิ้นส่วนเสร็จแล้ว',
            'description' => 'เครื่องเปิดไม่ติด',
            'category' => 'Hardware',
            'priority' => 'medium',
            'status' => 'in_progress',
            'escalated_to_team' => 'Hardware',
            'in_progress_at' => now(),
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($technician)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'resolve',
            'resolution_notes' => 'เปลี่ยน Power Supply ใหม่ และนำไปติดตั้งส่งมอบให้ผู้แจ้งแล้ว',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('resolved', $case->status);
        $this->assertEquals('รอผู้แจ้งกดรับงาน', $case->status_label);
        $this->assertNotNull($case->resolved_at);
        $this->assertEquals($technician->id, $case->resolved_by);
        $this->assertEquals('เปลี่ยน Power Supply ใหม่ และนำไปติดตั้งส่งมอบให้ผู้แจ้งแล้ว', $case->resolution_notes);
    }

    public function test_non_tier2_cannot_start_task2_preventive_measure(): void
    {
        $helpdesk = User::factory()->create(['role' => 'helpdesk', 'email' => 'helpdesk@example.com']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0105',
            'title' => 'เคสที่ต้องการ Task 2',
            'description' => 'รายละเอียดเคส',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'assigned',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($helpdesk)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'start_preventive_measure',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('error');
        $case->refresh();
        $this->assertEquals('assigned', $case->preventive_measure);
    }

    public function test_different_tier2_team_cannot_start_task2_assigned_to_another_team(): void
    {
        $networkTech = User::factory()->create(['role' => 'team_network', 'email' => 'net@example.com']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0106',
            'title' => 'เคสส่งต่อ Hardware',
            'description' => 'รายละเอียดเคส',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'assigned',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($networkTech)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'start_preventive_measure',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('error');
        $case->refresh();
        $this->assertEquals('assigned', $case->preventive_measure);
    }

    public function test_assigned_tier2_specialist_can_start_task2_preventive_measure(): void
    {
        $hardwareTech = User::factory()->create(['role' => 'team_hardware', 'email' => 'hw@example.com']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0107',
            'title' => 'เคสส่งต่อ Hardware ที่จะเริ่ม Task 2',
            'description' => 'รายละเอียดเคส',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'assigned',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        $response = $this->actingAs($hardwareTech)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'start_preventive_measure',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('success');
        $case->refresh();
        $this->assertEquals('in_progress', $case->preventive_measure);
    }

    public function test_assigned_tier2_specialist_can_update_notes_during_task2_preventive_measure(): void
    {
        $hardwareTech = User::factory()->create(['role' => 'team_hardware', 'email' => 'hw_notes@example.com']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0107B',
            'title' => 'เคสแก้ไข Root Cause ระหว่าง Task 2',
            'description' => 'รายละเอียดเคส',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'in_progress',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user_notes@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
            'why_1' => 'เก่า 1',
            'why_2' => 'เก่า 2',
            'why_3' => 'เก่า 3',
            'root_cause_detail' => 'สาเหตุเก่า',
            'resolution_notes' => 'วิธีแก้เก่า',
        ]);

        $response = $this->actingAs($hardwareTech)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'update_notes',
            'why_1' => 'ใหม่ 1: Windows VM เกิดปัญหา',
            'why_2' => 'ใหม่ 2: Event ID 1074',
            'why_3' => 'ใหม่ 3: Power state error',
            'root_cause_detail' => 'สาเหตุรากเหง้าที่ผ่านการวิเคราะห์เชิงลึก',
            'resolution_notes' => 'เปิด VM 101 Host 2 Auto ขึ้นมาใหม่และตรวจสอบ Driver',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('success', 'อัปเดตข้อมูลสำเร็จแล้ว');

        $case->refresh();
        $this->assertEquals('ใหม่ 1: Windows VM เกิดปัญหา', $case->why_1);
        $this->assertEquals('ใหม่ 2: Event ID 1074', $case->why_2);
        $this->assertEquals('ใหม่ 3: Power state error', $case->why_3);
        $this->assertEquals('สาเหตุรากเหง้าที่ผ่านการวิเคราะห์เชิงลึก', $case->root_cause_detail);
        $this->assertEquals('เปิด VM 101 Host 2 Auto ขึ้นมาใหม่และตรวจสอบ Driver', $case->resolution_notes);
    }

    public function test_technician_a_can_start_analyzing_and_technician_b_cannot_overwrite(): void
    {
        $techA = User::factory()->create(['name' => 'ช่าง A', 'role' => 'team_hardware', 'email' => 'techA@example.com']);
        $techB = User::factory()->create(['name' => 'ช่าง B', 'role' => 'team_hardware', 'email' => 'techB@example.com']);

        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0108',
            'title' => 'เคสทดสอบการเริ่มสืบสภาพพร้อมกัน',
            'description' => 'รายละเอียดเคส',
            'category' => 'Hardware',
            'status' => 'assigned',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-000-1111',
            'location' => 'ชั้น 1',
        ]);

        // Tech A starts analyzing first
        $responseA = $this->actingAs($techA)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'start_analyzing',
        ]);

        $responseA->assertRedirect(route('tickets.show', $case->id));
        $responseA->assertSessionHas('success');

        $case->refresh();
        $this->assertEquals('analyzing', $case->status);
        $this->assertEquals($techA->id, $case->analyzing_by);
        $this->assertNotNull($case->analyzing_at);

        // Tech B tries to start analyzing the same ticket afterwards
        $responseB = $this->actingAs($techB)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'start_analyzing',
        ]);

        $responseB->assertRedirect(route('tickets.show', $case->id));
        $responseB->assertSessionHas('error');

        // Confirm Tech A remains the investigator and was NOT overwritten
        $case->refresh();
        $this->assertEquals('analyzing', $case->status);
        $this->assertEquals($techA->id, $case->analyzing_by);

        // If Tech B submits RCA, analyzing_by remains Tech A
        $responseRca = $this->actingAs($techB)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'update_analysis',
            'why_1' => 'ตรวจพบปัญหา',
            'root_cause_detail' => 'สายไฟหลวม',
        ]);

        $responseRca->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();
        $this->assertEquals('in_progress', $case->status);
        $this->assertEquals($techA->id, $case->analyzing_by, 'Initial investigator Tech A must be preserved');
        $this->assertEquals($techB->id, $case->in_progress_by);
    }

    public function test_non_user_roles_cannot_access_create_ticket_page(): void
    {
        $roles = ['technician', 'helpdesk', 'manager', 'administrator'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get(route('tickets.create'));

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error', 'เฉพาะผู้ใช้งานทั่วไป (User) เท่านั้นที่มีสิทธิ์เปิดใบงานแจ้งซ่อม');
        }
    }

    public function test_non_user_roles_cannot_store_ticket(): void
    {
        $technician = User::factory()->create(['role' => 'technician']);

        $response = $this->actingAs($technician)->post(route('tickets.store'), [
            'title' => 'ทดสอบเปิดเคสโดยช่าง',
            'description' => 'อาการเสียเบื้องต้น',
            'location' => 'ตึก IT',
            'requester_phone' => '0812345678',
            'is_machine_stopped' => false,
        ]);

        $response->assertForbidden();
        $this->assertEquals(0, HelpdeskCase::count());
    }

    public function test_manager_review_requires_preventive_measure_defers_pcar_no_generation(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0201',
            'title' => 'เคสปิดรอหัวหน้าตรวจ',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        $response = $this->actingAs($manager)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'close',
            'requires_preventive_measure' => '1',
            'escalated_to_team' => 'Hardware',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('success');
        $case->refresh();

        $this->assertTrue((bool) $case->requires_preventive_measure);
        $this->assertEquals('assigned', $case->preventive_measure);
        $this->assertEquals('Hardware', $case->escalated_to_team);
        $this->assertNull($case->pcar_no); // Deferred to start Task 2
    }

    public function test_tier2_specialist_starting_task2_generates_pcar_number(): void
    {
        $hardwareTech = User::factory()->create(['role' => 'team_hardware']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0202',
            'title' => 'เคสมอบหมาย Task 2 ให้ Hardware',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'assigned',
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        $response = $this->actingAs($hardwareTech)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'start_preventive_measure',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('success');
        $case->refresh();

        $this->assertEquals('in_progress', $case->preventive_measure);
        $this->assertNotNull($case->pcar_no);
        $this->assertStringStartsWith('PCAR-', $case->pcar_no);
        $this->assertNotNull($case->pcar_opened_at);
        $this->assertEquals($hardwareTech->id, $case->pcar_opened_by);
    }

    public function test_manager_reverting_task2_requires_cancellation_reason(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0203',
            'title' => 'เคสเคยมี Task 2 กำลังทำ',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'in_progress',
            'pcar_no' => 'PCAR-202610-9999',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        // Attempt without cancellation reason -> should fail validation
        $response = $this->actingAs($manager)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'close',
            'requires_preventive_measure' => '0',
            'pcar_cancellation_reason' => '',
        ]);

        $response->assertSessionHasErrors(['pcar_cancellation_reason']);
        $case->refresh();
        $this->assertTrue((bool) $case->requires_preventive_measure);
        $this->assertEquals('in_progress', $case->preventive_measure);

        // Attempt with cancellation reason -> succeeds
        $responseSuccess = $this->actingAs($manager)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'close',
            'requires_preventive_measure' => '0',
            'pcar_cancellation_reason' => 'สาเหตุเกิดจาก Human Error และได้อบรมพนักงานแล้ว',
        ]);

        $responseSuccess->assertRedirect(route('tickets.show', $case->id));
        $responseSuccess->assertSessionHas('success');
        $case->refresh();

        $this->assertFalse((bool) $case->requires_preventive_measure);
        $this->assertEquals('cancelled', $case->preventive_measure);
        $this->assertEquals('PCAR-202610-9999', $case->pcar_no); // preserved for audit
        $this->assertEquals('สาเหตุเกิดจาก Human Error และได้อบรมพนักงานแล้ว', $case->pcar_cancellation_reason);
        $this->assertNotNull($case->pcar_cancelled_at);
        $this->assertEquals($manager->id, $case->pcar_cancelled_by);
    }

    public function test_first_time_manager_review_does_not_require_cancellation_reason_when_declining(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0204',
            'title' => 'เคสเพิ่งปิดยังไม่เคยระบุมาตรการ',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => null,
            'preventive_measure' => null,
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        $response = $this->actingAs($manager)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'close',
            'requires_preventive_measure' => '0',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('success');
        $case->refresh();

        $this->assertFalse((bool) $case->requires_preventive_measure);
        $this->assertNull($case->preventive_measure);
        $this->assertNull($case->pcar_cancellation_reason);
    }

    public function test_manager_can_reactivate_cancelled_task2_preserving_pcar_number(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0205',
            'title' => 'เคสเคยถูกยกเลิก Task 2 จะนำกลับมาทำใหม่',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => false,
            'preventive_measure' => 'cancelled',
            'pcar_no' => 'PCAR-202610-0088',
            'pcar_cancellation_reason' => 'ยกเลิกชั่วคราว',
            'pcar_cancelled_at' => now(),
            'pcar_cancelled_by' => $manager->id,
            'escalated_to_team' => 'Hardware',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        $response = $this->actingAs($manager)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'close',
            'requires_preventive_measure' => '1',
            'escalated_to_team' => 'Hardware',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('success');
        $case->refresh();

        $this->assertTrue((bool) $case->requires_preventive_measure);
        $this->assertEquals('in_progress', $case->preventive_measure);
        $this->assertEquals('PCAR-202610-0088', $case->pcar_no);
        $this->assertNull($case->pcar_cancellation_reason);
        $this->assertNull($case->pcar_cancelled_at);
        $this->assertNull($case->pcar_cancelled_by);

        $this->assertDatabaseHas('ticket_comments', [
            'helpdesk_case_id' => $case->id,
            'user_id' => $manager->id,
        ]);
    }

    public function test_manager_cannot_change_requires_preventive_measure_if_task2_is_done(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0206',
            'title' => 'เคสปิด Task 2 เสร็จแล้ว',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => true,
            'preventive_measure' => 'done',
            'pcar_no' => 'PCAR-202610-0099',
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        $response = $this->actingAs($manager)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'close',
            'requires_preventive_measure' => '0',
            'pcar_cancellation_reason' => 'จะขอยกเลิกหลังจากปิดแล้ว',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $response->assertSessionHas('error');
        $case->refresh();

        $this->assertTrue((bool) $case->requires_preventive_measure);
        $this->assertEquals('done', $case->preventive_measure);
    }

    public function test_print_page_displays_watermark_when_pcar_is_cancelled(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'Manager Somchai']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0207',
            'title' => 'เคสปริ้นรายงานที่มีลายน้ำ',
            'description' => 'รายละเอียด',
            'category' => 'Hardware',
            'status' => 'closed',
            'requires_preventive_measure' => false,
            'preventive_measure' => 'cancelled',
            'pcar_no' => 'PCAR-202610-0055',
            'pcar_cancellation_reason' => 'เป็นปัญหาจากการทำงานของผู้ใช้โดยตรง',
            'pcar_cancelled_at' => now(),
            'pcar_cancelled_by' => $manager->id,
            'requester_name' => 'ผู้แจ้ง',
            'requester_email' => 'user@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร A',
        ]);

        $response = $this->actingAs($manager)->get(route('tickets.print', $case->id));
        $response->assertOk();
        $response->assertSee('watermark-cancelled');
        $response->assertSee('P-CAR ถูกยกเลิก / CANCELLED');
        $response->assertSee('เป็นปัญหาจากการทำงานของผู้ใช้โดยตรง');
        $response->assertSee('Manager Somchai');
    }

    public function test_cancelling_ticket_records_cancellation_reason_and_system_comment(): void
    {
        $user = User::factory()->create(['name' => 'User Somsak', 'email' => 'somsak@example.com', 'role' => 'user']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0999',
            'title' => 'ต้องการยกเลิกเคสทดสอบ',
            'description' => 'อาการทดสอบ',
            'category' => 'อื่นๆ',
            'status' => 'pending',
            'requester_name' => 'User Somsak',
            'requester_email' => 'somsak@example.com',
            'requester_phone' => '081-111-2222',
            'location' => 'อาคาร B',
        ]);

        $response = $this->actingAs($user)->put(route('tickets.updateStatus', $case->id), [
            'action' => 'cancel',
            'cancellation_reason' => 'แก้ไขปัญหาได้เองแล้ว ไม่ต้องการการช่วยเหลือ',
        ]);

        $response->assertRedirect(route('tickets.show', $case->id));
        $case->refresh();

        $this->assertEquals('cancelled', $case->status);
        $this->assertEquals('แก้ไขปัญหาได้เองแล้ว ไม่ต้องการการช่วยเหลือ', $case->cancellation_reason);
        $this->assertEquals($user->id, $case->cancelled_by);
        $this->assertNotNull($case->cancelled_at);

        $this->assertDatabaseHas('ticket_comments', [
            'helpdesk_case_id' => $case->id,
            'user_id' => $user->id,
            'message' => '⛔ [ระบบ] ใบงานนี้ถูกยกเลิกโดย User Somsak เนื่องจาก: แก้ไขปัญหาได้เองแล้ว ไม่ต้องการการช่วยเหลือ',
        ]);
    }
}
