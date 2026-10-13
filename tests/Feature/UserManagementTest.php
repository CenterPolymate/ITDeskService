<?php

namespace Tests\Feature;

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
}
