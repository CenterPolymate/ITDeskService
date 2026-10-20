<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_audit_logs_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'POST',
            'target_type' => 'App\Models\Company',
            'target_id' => '1',
            'changes' => ['name' => 'Test Company'],
        ]);

        $response = $this->actingAs($admin)->get(route('audit_logs.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('AuditLogs/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.changes.name', 'Test Company')
        );
    }

    public function test_non_administrator_cannot_view_audit_logs_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('audit_logs.index'));

        $response->assertForbidden();
    }

    public function test_administrator_can_export_audit_logs_csv(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'POST',
            'target_type' => 'App\Models\Company',
            'target_id' => '1',
            'changes' => ['name' => 'Test Company'],
        ]);

        $response = $this->actingAs($admin)->get(route('audit_logs.index', ['export' => 'csv']));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'attachment; filename=audit_logs_'));
    }

    public function test_settings_update_logs_only_actually_changed_fields_in_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        Setting::updateOrCreate(['key' => 'app_name'], ['value' => 'Old App Name']);
        Setting::updateOrCreate(['key' => 'company_name'], ['value' => 'Old Company']);
        Setting::updateOrCreate(['key' => 'smtp_host'], ['value' => 'smtp.old.com']);

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'app_name' => 'Old App Name',
            'company_name' => 'New Polymate Company',
            'smtp_host' => 'smtp.old.com',
            'remove_app_logo' => '0',
            'smtp_verified_token' => 'dummy-token',
        ]);

        $response->assertRedirect();

        $log = AuditLog::where('target_type', 'settings.update')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('company_name', $log->changes);
        $this->assertSame('New Polymate Company', $log->changes['company_name']);
        $this->assertArrayNotHasKey('app_name', $log->changes);
        $this->assertArrayNotHasKey('smtp_host', $log->changes);
        $this->assertArrayNotHasKey('smtp_verified_token', $log->changes);
        $this->assertArrayNotHasKey('remove_app_logo', $log->changes);
        $this->assertSame('Old Company', $log->old_values['company_name']);
    }
}
