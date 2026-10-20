<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_categories_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        Category::create([
            'name' => 'Network Issue',
            'description' => 'LAN and WiFi problems',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('categories.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Categories/Index')
            ->has('categories', 1)
            ->where('categories.0.name', 'Network Issue')
        );
    }

    public function test_administrator_can_view_create_category_page(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->get(route('categories.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Categories/Create'));
    }

    public function test_administrator_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Hardware Failure',
            'description' => 'Broken monitors or PCs',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'name' => 'Hardware Failure',
            'is_active' => true,
        ]);
    }

    public function test_administrator_can_view_edit_category_page(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $cat = Category::create([
            'name' => 'Printer Issue',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('categories.edit', $cat));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Categories/Edit')
            ->where('category.name', 'Printer Issue')
        );
    }

    public function test_administrator_can_update_category(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $cat = Category::create([
            'name' => 'Printer Issue',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('categories.update', $cat), [
            'name' => 'Printer & Scanner Issue',
            'description' => 'All printing devices',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Printer & Scanner Issue',
        ]);
    }

    public function test_administrator_can_delete_category(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $cat = Category::create([
            'name' => 'Old Category',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $cat));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', [
            'id' => $cat->id,
        ]);
    }

    public function test_manager_can_view_categories_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Category::create([
            'name' => 'Database Issue',
            'description' => 'SQL and database connection errors',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get(route('categories.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Categories/Index')
            ->has('categories', 1)
            ->where('categories.0.name', 'Database Issue')
        );
    }

    public function test_manager_can_create_category(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->post(route('categories.store'), [
            'name' => 'VPN Connection Failure',
            'description' => 'Cannot connect to company VPN',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'name' => 'VPN Connection Failure',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_update_category(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $cat = Category::create([
            'name' => 'Email Sync Problem',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->put(route('categories.update', $cat), [
            'name' => 'Email & Outlook Issue',
            'description' => 'Mail delivery and sync issues',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Email & Outlook Issue',
        ]);
    }

    public function test_manager_can_delete_category(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $cat = Category::create([
            'name' => 'Temporary Category',
            'is_active' => false,
        ]);

        $response = $this->actingAs($manager)->delete(route('categories.destroy', $cat));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', [
            'id' => $cat->id,
        ]);
    }

    public function test_unauthorized_roles_cannot_access_categories(): void
    {
        $forbiddenRoles = ['user', 'helpdesk', 'team_hardware', 'team_network', 'team_software'];

        foreach ($forbiddenRoles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get(route('categories.index'));
            $response->assertForbidden();

            $responseStore = $this->actingAs($user)->post(route('categories.store'), [
                'name' => 'Unauthorized Category '.$role,
            ]);
            $responseStore->assertForbidden();
        }
    }
}
