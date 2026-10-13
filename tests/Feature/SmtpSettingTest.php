<?php

namespace Tests\Feature;

use App\Http\Controllers\SettingController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmtpSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_test_smtp_settings(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->post(route('settings.test_email'), [
            'smtp_host' => 'mailpro-02.zth.netdesignhost.com',
            'smtp_port' => '465',
            'smtp_username' => 'admin@polymate.co.th',
            'smtp_password' => 'secret123',
            'smtp_encryption' => 'ssl',
            'test_email' => 'admin@polymate.co.th',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSentCount(1);
    }

    public function test_administrator_can_test_smtp_settings_via_ajax(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->postJson(route('settings.test_email'), [
            'smtp_host' => 'mailpro-02.zth.netdesignhost.com',
            'smtp_port' => '465',
            'smtp_username' => 'admin@polymate.co.th',
            'smtp_password' => 'secret123',
            'smtp_encryption' => 'ssl',
            'test_email' => 'admin@polymate.co.th',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('verified_token'));

        Mail::assertSentCount(1);
    }

    public function test_cannot_save_smtp_settings_if_unverified(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'smtp_host' => 'mailpro-02.zth.netdesignhost.com',
            'smtp_port' => '465',
            'smtp_username' => 'sender@polymate.co.th',
            'smtp_password' => 'secret123',
            'smtp_encryption' => 'ssl',
            // No valid smtp_verified_token provided
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('settings', [
            'key' => 'smtp_username',
            'value' => 'sender@polymate.co.th',
        ]);
    }

    public function test_updating_smtp_username_with_verified_token_automatically_syncs_smtp_from_address(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $token = SettingController::generateSmtpToken(
            'mailpro-02.zth.netdesignhost.com',
            '465',
            'sender@polymate.co.th',
            'secret123',
            'ssl'
        );

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'smtp_host' => 'mailpro-02.zth.netdesignhost.com',
            'smtp_port' => '465',
            'smtp_username' => 'sender@polymate.co.th',
            'smtp_password' => 'secret123',
            'smtp_encryption' => 'ssl',
            'smtp_verified_token' => $token,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('settings', [
            'key' => 'smtp_username',
            'value' => 'sender@polymate.co.th',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'smtp_from_address',
            'value' => 'sender@polymate.co.th',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'smtp_verified',
            'value' => '1',
        ]);
    }

    public function test_non_administrator_cannot_test_smtp_settings(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('settings.test_email'), [
            'smtp_host' => 'mailpro-02.zth.netdesignhost.com',
            'smtp_port' => '465',
            'smtp_username' => 'admin@polymate.co.th',
        ]);

        $response->assertStatus(403);
    }
}
