<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_teams_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'IT Supervisor']);

        $team = Team::create([
            'name' => 'Core Development',
            'code' => 'DEV',
            'description' => 'Development team',
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('teams.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Teams/Index')
            ->has('teams')
            ->where('teams.0.code', 'DEV')
            ->has('stats')
        );
    }

    public function test_manager_can_view_teams_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->get(route('teams.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Teams/Index'));
    }

    public function test_normal_user_cannot_access_teams(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('teams.index'));
        $response->assertForbidden();

        $responseCreate = $this->actingAs($user)->get(route('teams.create'));
        $responseCreate->assertForbidden();
    }

    public function test_helpdesk_cannot_access_teams(): void
    {
        $helpdesk = User::factory()->create(['role' => 'helpdesk']);

        $response = $this->actingAs($helpdesk)->get(route('teams.index'));
        $response->assertForbidden();
    }

    public function test_administrator_can_create_team_with_manager_and_members(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $manager = User::factory()->create(['role' => 'manager']);
        $tech1 = User::factory()->create(['role' => 'technician']);
        $tech2 = User::factory()->create(['role' => 'team_software']);

        $response = $this->actingAs($admin)->post(route('teams.store'), [
            'name' => 'Infrastructure & Cloud',
            'code' => 'INFRA',
            'description' => 'Servers and networks',
            'manager_id' => $manager->id,
            'member_ids' => [$tech1->id, $tech2->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('teams', [
            'name' => 'Infrastructure & Cloud',
            'code' => 'INFRA',
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);

        $team = Team::where('code', 'INFRA')->first();
        $this->assertCount(2, $team->members);
        $this->assertTrue($team->members->contains($tech1->id));
        $this->assertTrue($team->members->contains($tech2->id));
    }

    public function test_team_creation_requires_unique_name_and_code(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        Team::create([
            'name' => 'Unique Team',
            'code' => 'UQ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('teams.store'), [
            'name' => 'Unique Team',
            'code' => 'UQ',
        ]);

        $response->assertSessionHasErrors(['name', 'code']);
    }

    public function test_administrator_can_update_team_and_sync_members(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $manager1 = User::factory()->create(['role' => 'manager']);
        $manager2 = User::factory()->create(['role' => 'manager']);
        $tech1 = User::factory()->create(['role' => 'technician']);
        $tech2 = User::factory()->create(['role' => 'technician']);

        $team = Team::create([
            'name' => 'Original Name',
            'code' => 'ORIG',
            'manager_id' => $manager1->id,
            'is_active' => true,
        ]);
        $team->members()->attach($tech1->id);

        $response = $this->actingAs($admin)->put(route('teams.update', $team->id), [
            'name' => 'Updated Name',
            'code' => 'UPDT',
            'description' => 'Updated description',
            'manager_id' => $manager2->id,
            'member_ids' => [$tech2->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success');

        $team->refresh();
        $this->assertEquals('Updated Name', $team->name);
        $this->assertEquals('UPDT', $team->code);
        $this->assertEquals($manager2->id, $team->manager_id);
        $this->assertCount(1, $team->members);
        $this->assertTrue($team->members->contains($tech2->id));
        $this->assertFalse($team->members->contains($tech1->id));
    }

    public function test_administrator_can_toggle_team_status(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $team = Team::create([
            'name' => 'Status Test Team',
            'code' => 'STT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('teams.toggle_status', $team->id));

        $response->assertRedirect();
        $this->assertFalse($team->fresh()->is_active);

        $this->actingAs($admin)->patch(route('teams.toggle_status', $team->id));
        $this->assertTrue($team->fresh()->is_active);
    }

    public function test_administrator_can_delete_team(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $tech = User::factory()->create(['role' => 'technician']);

        $team = Team::create([
            'name' => 'Delete Me',
            'code' => 'DEL',
            'is_active' => true,
        ]);
        $team->members()->attach($tech->id);

        $response = $this->actingAs($admin)->delete(route('teams.destroy', $team->id));

        $response->assertRedirect(route('teams.index'));
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('team_user', ['team_id' => $team->id]);
    }
}
