<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_manager_can_view_it_users_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $manager = User::factory()->create(['role' => 'manager']);
        User::factory()->create(['role' => 'helpdesk']);

        $resAdmin = $this->actingAs($admin)->get(route('users.index'));
        $resAdmin->assertOk();
        $resAdmin->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Users/Index')
            ->has('users.data')
        );

        $resManager = $this->actingAs($manager)->get(route('users.index'));
        $resManager->assertOk();
    }

    public function test_normal_user_cannot_view_it_users_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_create_it_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create(['name' => 'Acme Corp', 'sla_type' => '8x5', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Tech Specialist',
            'email' => 'tech@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'team_hardware',
            'company' => $company->name,
            'department' => 'IT',
            'phone' => '0812345678',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Tech Specialist',
            'email' => 'tech@acme.com',
            'role' => 'team_hardware',
            'company' => 'Acme Corp',
        ]);
    }

    public function test_admin_cannot_change_administrator_name(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'name' => 'Original Admin']);
        $company = Company::create(['name' => 'Acme Corp', 'sla_type' => '8x5', 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('users.update', $admin->id), [
            'name' => 'Hacked Admin Name',
            'email' => $admin->email,
            'role' => 'administrator',
            'company' => $company->name,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Original Admin', // Name must remain unchanged per AGENTS.md rule
        ]);
    }

    public function test_user_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin->id));
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error', 'ไม่สามารถระงับบัญชีของตัวเองได้');
    }

    public function test_manager_modifying_user_is_recorded_in_audit_log(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::create(['name' => 'Acme Corp', 'sla_type' => '8x5', 'is_active' => true]);
        $targetUser = User::factory()->create([
            'name' => 'Initial Tech',
            'role' => 'team_hardware',
            'company' => $company->name,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->put(route('users.update', $targetUser->id), [
            'name' => 'Updated Tech Name',
            'email' => $targetUser->email,
            'role' => 'team_network',
            'company' => $company->name,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));

        // Verify Audit Log was created with manager's ID
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'target_id' => (string) $targetUser->id,
        ]);

        $log = AuditLog::where('user_id', $manager->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('users.update', $log->action);
    }

    public function test_admin_and_manager_can_force_reset_password_for_it_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $manager = User::factory()->create(['role' => 'manager']);
        $regularUser = User::factory()->create(['role' => 'user']);
        $itUser1 = User::factory()->create(['role' => 'technician']);
        $itUser2 = User::factory()->create(['role' => 'helpdesk']);

        // Admin can send reset link
        $responseAdmin = $this->actingAs($admin)->post(route('users.force_reset_password', $itUser1->id));
        $responseAdmin->assertSessionHas('success');

        // Manager can send reset link
        $responseManager = $this->actingAs($manager)->post(route('users.force_reset_password', $itUser2->id));
        $responseManager->assertSessionHas('success');

        // Regular user cannot
        $this->actingAs($regularUser)
            ->post(route('users.force_reset_password', $itUser1->id))
            ->assertForbidden();
    }
}
