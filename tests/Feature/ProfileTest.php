<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Profile/Edit'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $company = Company::create(['name' => 'Test Company', 'sla_type' => '8x5', 'is_active' => true]);
        $user = User::factory()->create([
            'company' => $company->name,
            'department' => 'IT',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'company' => $company->name,
                'department' => 'IT Support',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('IT Support', $user->department);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $company = Company::create(['name' => 'Test Company', 'sla_type' => '8x5', 'is_active' => true]);
        $user = User::factory()->create([
            'company' => $company->name,
            'department' => 'IT',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'company' => $company->name,
                'department' => 'IT',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
