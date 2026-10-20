<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeactivationAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_user_with_history_logs_deactivate_in_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $userWithHistory = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $userWithHistory->id,
            'action' => 'LOGIN',
            'target_type' => 'auth',
            'target_id' => 999,
        ]);

        $this->assertTrue($userWithHistory->hasHistory());

        $response = $this->actingAs($admin)->delete(route('normal_users.destroy', $userWithHistory));

        $response->assertRedirect(route('normal_users.index'));

        // บัญชีต้องไม่ถูกลบถาวร แต่เปลี่ยนเป็น is_active = false
        $this->assertDatabaseHas('users', [
            'id' => $userWithHistory->id,
            'is_active' => false,
        ]);

        // Audit Log ต้องบันทึกว่า DEACTIVATE ไม่ใช่ DELETE
        $log = AuditLog::where('target_id', (string) $userWithHistory->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('DEACTIVATE', $log->action);
        $this->assertEquals(0, $log->changes['is_active']);
    }

    public function test_deleting_user_without_history_logs_delete_in_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $userWithoutHistory = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->assertFalse($userWithoutHistory->hasHistory());

        $userId = $userWithoutHistory->id;

        $response = $this->actingAs($admin)->delete(route('normal_users.destroy', $userWithoutHistory));

        $response->assertRedirect(route('normal_users.index'));

        // บัญชีต้องถูกลบถาวร
        $this->assertDatabaseMissing('users', [
            'id' => $userId,
        ]);

        // Audit Log ต้องบันทึกว่า DELETE
        $log = AuditLog::where('target_id', (string) $userId)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('DELETE', $log->action);
    }
}
