<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HelpdeskCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NormalUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_normal_users_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        User::factory()->count(3)->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get(route('normal_users.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('NormalUsers/Index')
            ->has('users.data', 3)
        );
    }

    public function test_non_admin_cannot_view_normal_users_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $helpdesk = User::factory()->create(['role' => 'helpdesk']);

        $this->actingAs($user)->get(route('normal_users.index'))->assertForbidden();
        $this->actingAs($helpdesk)->get(route('normal_users.index'))->assertForbidden();
    }

    public function test_admin_can_create_normal_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create(['name' => 'Acme Corp', 'sla_type' => '8x5', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('normal_users.store'), [
            'name' => 'John Requester',
            'email' => 'john@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company' => $company->name,
            'department' => 'Marketing',
            'phone' => '0812345678',
        ]);

        $response->assertRedirect(route('normal_users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'John Requester',
            'email' => 'john@acme.com',
            'role' => 'user',
            'company' => 'Acme Corp',
            'department' => 'Marketing',
        ]);
    }

    public function test_admin_can_update_normal_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create(['name' => 'Acme Corp', 'sla_type' => '8x5', 'is_active' => true]);
        $target = User::factory()->create([
            'role' => 'user',
            'company' => $company->name,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('normal_users.update', $target->id), [
            'name' => 'John Updated',
            'email' => 'john_upd@acme.com',
            'role' => 'user',
            'company' => $company->name,
            'department' => 'Sales',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('normal_users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'John Updated',
            'department' => 'Sales',
        ]);
    }

    public function test_smart_delete_hard_deletes_when_no_history(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $target = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->delete(route('normal_users.destroy', $target->id));

        $response->assertRedirect(route('normal_users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_smart_delete_deactivates_when_has_history(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $target = User::factory()->create(['role' => 'user', 'is_active' => true]);

        // Create ticket linked to user
        HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0001',
            'title' => 'Sample Issue',
            'description' => 'Test description with 5W2H details',
            'category' => 'Hardware',
            'priority' => 'low',
            'status' => 'pending',
            'requester_name' => $target->name,
            'requester_email' => $target->email,
            'user_id' => $target->id,
            'sla_due_at' => now()->addHours(4),
        ]);

        $response = $this->actingAs($admin)->delete(route('normal_users.destroy', $target->id));

        $response->assertRedirect(route('normal_users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    }
}
