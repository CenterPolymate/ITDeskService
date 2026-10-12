<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonateLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_impersonating_user_does_not_update_target_user_last_login_at(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'last_login_at' => null,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'last_login_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('impersonate', $user));

        $response->assertRedirect(route('dashboard'));
        $this->assertNull($user->fresh()->last_login_at, 'Target user last_login_at should not be updated when impersonated.');
    }

    public function test_leaving_impersonation_does_not_update_admin_last_login_at(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'last_login_at' => null,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'last_login_at' => null,
        ]);

        $this->actingAs($admin)->post(route('impersonate', $user));

        $response = $this->post(route('impersonate.leave'));
        $response->assertRedirect(route('dashboard'));

        $this->assertNull($admin->fresh()->last_login_at, 'Admin last_login_at should not be updated when leaving impersonation.');
    }
}
