<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\HelpdeskCase;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_hard_delete_company_without_history(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create([
            'name' => 'Mistake Corp',
            'short_name' => 'MC',
            'is_active' => true,
            'email_domains' => '@mistake.com',
            'sla_type' => '8x5x4',
        ]);

        Department::create(['company_id' => $company->id, 'name' => 'QA']);
        Sla::create(['company' => $company->name, 'priority' => 'high', 'hours' => 4, 'name_th' => 'สูง']);

        $response = $this->actingAs($admin)->delete(route('companies.destroy', $company->id));

        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('departments', ['company_id' => $company->id]);
        $this->assertDatabaseMissing('slas', ['company' => 'Mistake Corp']);
    }

    public function test_administrator_deleting_company_with_history_deactivates_it_instead(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create([
            'name' => 'Polymate Active',
            'short_name' => 'PA',
            'is_active' => true,
            'email_domains' => '@polymate.com',
            'sla_type' => '8x5x4',
        ]);

        User::factory()->create([
            'company' => $company->name,
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)->delete(route('companies.destroy', $company->id));

        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => false,
        ]);

        $log = AuditLog::where('target_id', (string) $company->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('DEACTIVATE', $log->action);
    }

    public function test_administrator_can_toggle_company_status(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create([
            'name' => 'Toggle Corp',
            'short_name' => 'TC',
            'is_active' => true,
            'email_domains' => '@toggle.com',
            'sla_type' => '8x5x4',
        ]);

        // Toggle from active to inactive
        $response = $this->actingAs($admin)->patch(route('companies.toggle_status', $company->id));
        $response->assertRedirect(route('companies.index'));
        $this->assertFalse($company->fresh()->is_active);

        // Toggle from inactive to active
        $response = $this->actingAs($admin)->patch(route('companies.toggle_status', $company->id));
        $response->assertRedirect(route('companies.index'));
        $this->assertTrue($company->fresh()->is_active);
    }

    public function test_updating_company_name_cascades_to_users_and_slas_and_cases(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $company = Company::create([
            'name' => 'Old Name Corp',
            'short_name' => 'ONC',
            'is_active' => true,
            'email_domains' => '@old.com',
            'sla_type' => '8x5x4',
        ]);

        $user = User::factory()->create(['company' => 'Old Name Corp']);
        $sla = Sla::create(['company' => 'Old Name Corp', 'priority' => 'urgent', 'hours' => 2, 'name_th' => 'ด่วนที่สุด']);
        $case = HelpdeskCase::create([
            'ticket_no' => 'IT-202610-0099',
            'title' => 'Case 1',
            'category' => 'อื่นๆ',
            'description' => 'Test case',
            'user_id' => $user->id,
            'company' => 'Old Name Corp',
            'requester_name' => 'Tester',
            'requester_phone' => '0812345678',
            'location' => 'Building A',
            'status' => 'open',
            'sla_hours' => 4,
            'sla_miss' => false,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('companies.update', $company->id), [
            'name' => 'New Name Corp',
            'short_name' => 'NNC',
            'email_domains' => '@new.com',
            'sla_type' => '8x5x4',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('companies.index'));

        $this->assertEquals('New Name Corp', $company->fresh()->name);
        $this->assertEquals('NNC', $company->fresh()->short_name);
        $this->assertEquals('New Name Corp', $user->fresh()->company);
        $this->assertEquals('New Name Corp', $sla->fresh()->company);
        $this->assertEquals('New Name Corp', $case->fresh()->company);
    }
}
