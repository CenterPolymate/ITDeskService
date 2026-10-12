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
}
