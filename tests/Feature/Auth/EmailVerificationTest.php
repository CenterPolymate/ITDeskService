<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_cannot_access_dashboard_and_is_redirected(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_admin_created_it_user_is_automatically_verified(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        Company::create(['name' => 'Polymate Corp', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New IT Tech',
            'email' => 'tech@polymate.co.th',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'helpdesk',
            'company' => 'Polymate Corp',
            'department' => 'IT',
        ]);

        $response->assertRedirect(route('users.index'));
        $createdUser = User::where('email', 'tech@polymate.co.th')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasVerifiedEmail());
    }

    public function test_admin_created_normal_user_is_automatically_verified(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        Company::create(['name' => 'Polymate Corp', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('normal_users.store'), [
            'name' => 'New User',
            'email' => 'normal@polymate.co.th',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company' => 'Polymate Corp',
            'department' => 'HR',
        ]);

        $response->assertRedirect(route('normal_users.index'));
        $createdUser = User::where('email', 'normal@polymate.co.th')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasVerifiedEmail());
    }

    public function test_unverified_user_logging_in_does_not_update_last_login_at(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => bcrypt('password123'),
            'last_login_at' => null,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertNull($user->fresh()->last_login_at, 'Unverified user should not have last_login_at updated on login');
    }

    public function test_verifying_email_updates_last_login_at(): void
    {
        $user = User::factory()->unverified()->create([
            'last_login_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($user->fresh()->last_login_at, 'User should have last_login_at set after verifying email');
    }

    public function test_unverified_user_displays_pending_verification_badge_on_user_list(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $unverifiedUser = User::factory()->unverified()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('normal_users.index'));

        $response->assertStatus(200);
        $response->assertSee('รอการยืนยันอีเมล');
    }
}
