<?php

namespace Tests\Feature;

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
            'smtp_from_address' => 'admin@polymate.co.th',
            'test_email' => 'admin@polymate.co.th',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSentCount(1);
    }

    public function test_non_administrator_cannot_test_smtp_settings(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('settings.test_email'), [
            'smtp_host' => 'mailpro-02.zth.netdesignhost.com',
            'smtp_port' => '465',
            'smtp_from_address' => 'admin@polymate.co.th',
        ]);

        $response->assertStatus(403);
    }
}
