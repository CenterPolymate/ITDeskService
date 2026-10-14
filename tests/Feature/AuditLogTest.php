<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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
}
