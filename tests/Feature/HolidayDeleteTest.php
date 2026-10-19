<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_cannot_delete_past_holiday(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $pastHoliday = Holiday::create([
            'name' => 'วันปีใหม่ย้อนหลัง',
            'date' => now()->subDays(5)->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('holidays.destroy', $pastHoliday));

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('error', 'ไม่สามารถลบวันหยุดที่ผ่านมาแล้วได้');
        $this->assertDatabaseHas('holidays', ['id' => $pastHoliday->id]);
    }

    public function test_administrator_can_delete_future_or_today_holiday(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $futureHoliday = Holiday::create([
            'name' => 'วันหยุดในอนาคต',
            'date' => now()->addDays(5)->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('holidays.destroy', $futureHoliday));

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('success', 'ลบวันหยุดนักขัตฤกษ์สำเร็จ');
        $this->assertDatabaseMissing('holidays', ['id' => $futureHoliday->id]);
    }

    public function test_administrator_cannot_create_past_holiday(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $pastDate = now()->subDays(2)->format('Y-m-d');

        $response = $this->actingAs($admin)->post(route('holidays.store'), [
            'name' => 'วันหยุดย้อนหลังที่ทดสอบ',
            'date' => $pastDate,
        ]);

        $response->assertSessionHasErrors(['date']);
        $this->assertDatabaseMissing('holidays', ['date' => $pastDate]);
    }

    public function test_administrator_can_create_future_or_today_holiday(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $futureDate = now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($admin)->post(route('holidays.store'), [
            'name' => 'วันหยุดในอนาคต',
            'date' => $futureDate,
        ]);

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('success', 'เพิ่มวันหยุดนักขัตฤกษ์สำเร็จ');
        $this->assertDatabaseHas('holidays', [
            'name' => 'วันหยุดในอนาคต',
        ]);
        $this->assertTrue(Holiday::whereDate('date', $futureDate)->exists());
    }

    public function test_administrator_bulk_store_skips_past_holidays(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $pastDate = now()->subDays(3)->format('Y-m-d');
        $futureDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($admin)->post(route('holidays.storeBulk'), [
            'holidays' => [
                ['name' => 'วันหยุดในอดีต', 'date' => $pastDate],
                ['name' => 'วันหยุดในอนาคต', 'date' => $futureDate],
            ],
        ]);

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('success', 'เพิ่มวันหยุดสำเร็จจำนวน 1 วัน');
        $this->assertFalse(Holiday::whereDate('date', $pastDate)->exists());
        $this->assertTrue(Holiday::whereDate('date', $futureDate)->exists());
    }

    public function test_manager_can_view_holidays_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->get(route('holidays.index'));

        $response->assertOk();
    }

    public function test_manager_can_create_future_holiday(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $futureDate = now()->addDays(7)->format('Y-m-d');

        $response = $this->actingAs($manager)->post(route('holidays.store'), [
            'name' => 'วันหยุดพิเศษสำหรับ Manager',
            'date' => $futureDate,
        ]);

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('success', 'เพิ่มวันหยุดนักขัตฤกษ์สำเร็จ');
        $this->assertTrue(Holiday::whereDate('date', $futureDate)->exists());
    }

    public function test_manager_can_delete_future_holiday(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $futureHoliday = Holiday::create([
            'name' => 'วันหยุดที่รอการลบ',
            'date' => now()->addDays(12)->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->delete(route('holidays.destroy', $futureHoliday));

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('success', 'ลบวันหยุดนักขัตฤกษ์สำเร็จ');
        $this->assertDatabaseMissing('holidays', ['id' => $futureHoliday->id]);
    }

    public function test_manager_cannot_delete_past_holiday(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $pastHoliday = Holiday::create([
            'name' => 'วันหยุดย้อนหลัง',
            'date' => now()->subDays(4)->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->delete(route('holidays.destroy', $pastHoliday));

        $response->assertRedirect(route('holidays.index'));
        $response->assertSessionHas('error', 'ไม่สามารถลบวันหยุดที่ผ่านมาแล้วได้');
        $this->assertDatabaseHas('holidays', ['id' => $pastHoliday->id]);
    }

    public function test_unauthorized_roles_cannot_access_holidays(): void
    {
        $forbiddenRoles = ['user', 'helpdesk', 'team_hardware', 'team_network', 'team_software'];

        foreach ($forbiddenRoles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get(route('holidays.index'));
            $response->assertForbidden();

            $responseStore = $this->actingAs($user)->post(route('holidays.store'), [
                'name' => 'Unauthorized Holiday ' . $role,
                'date' => now()->addDays(5)->format('Y-m-d'),
            ]);
            $responseStore->assertForbidden();
        }
    }
}
