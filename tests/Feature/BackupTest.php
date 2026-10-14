<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_backup_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->get(route('backups.index'));
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Backups/Index'));
    }

    public function test_non_administrator_cannot_view_backup_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('backups.index'));
        $response->assertForbidden();
    }

    public function test_backup_history_displays_thai_date_and_correct_timezone(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        Storage::fake('local');
        $backupName = config('backup.backup.name', 'ITDeskService');
        Storage::disk('local')->put("{$backupName}/FULL_2026-10-13-14-56-47.zip", 'fake-content');

        $response = $this->actingAs($admin)->get(route('backups.index'));
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Backups/Index')
            ->where('backups.0.file_name', 'FULL_2026-10-13-14-56-47.zip')
            ->where('backups.0.last_modified', '13 ตุลาคม 2026 14:56:47')
        );
    }

    public function test_administrator_can_trigger_database_backup_with_disabled_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ])
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Backup completed successfully.');

        $response = $this->actingAs($admin)->post(route('backups.store'), [
            'type' => 'db_only',
        ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success', 'การสำรองข้อมูลสำเร็จเรียบร้อยแล้ว');
    }

    public function test_administrator_can_trigger_full_backup_with_disabled_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', [
                '--disable-notifications' => true,
            ])
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Backup completed successfully.');

        $response = $this->actingAs($admin)->post(route('backups.store'), [
            'type' => 'full',
        ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success', 'การสำรองข้อมูลสำเร็จเรียบร้อยแล้ว');
    }
}
