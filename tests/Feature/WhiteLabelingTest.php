<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WhiteLabelingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_branding_settings_in_settings_index(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        Setting::updateOrCreate(['key' => 'app_name'], ['value' => 'Helpdesk Enterprise', 'group' => 'branding']);
        Setting::updateOrCreate(['key' => 'support_contact'], ['value' => 'support@acme.com', 'group' => 'branding']);
        Setting::clearBrandingCache();

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Index')
            ->where('branding.app_name', 'Helpdesk Enterprise')
            ->where('branding.support_contact', 'support@acme.com')
        );
    }

    public function test_administrator_can_update_branding_text_settings(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'app_name' => 'Custom Service Desk',
            'support_contact' => '02-999-8888 ext 100',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'Custom Service Desk',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'support_contact',
            'value' => '02-999-8888 ext 100',
        ]);

        $branding = Setting::getBranding();
        $this->assertSame('Custom Service Desk', $branding['app_name']);
        $this->assertSame('02-999-8888 ext 100', $branding['support_contact']);
    }

    public function test_administrator_can_upload_custom_branding_logos_and_favicon(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'administrator']);

        $logoFile = UploadedFile::fake()->create('custom_header_logo.png', 50, 'image/png');
        $loginLogoFile = UploadedFile::fake()->create('custom_login_logo.png', 60, 'image/png');
        $faviconFile = UploadedFile::fake()->create('custom_favicon.ico', 10, 'image/x-icon');

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'app_name' => 'Branded Portal',
            'app_logo' => $logoFile,
            'app_logo_login' => $loginLogoFile,
            'app_favicon' => $faviconFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $logoPath = Setting::where('key', 'app_logo')->value('value');
        $loginLogoPath = Setting::where('key', 'app_logo_login')->value('value');
        $faviconPath = Setting::where('key', 'app_favicon')->value('value');

        $this->assertNotNull($logoPath);
        $this->assertNotNull($loginLogoPath);
        $this->assertNotNull($faviconPath);

        Storage::disk('public')->assertExists($logoPath);
        Storage::disk('public')->assertExists($loginLogoPath);
        Storage::disk('public')->assertExists($faviconPath);

        $branding = Setting::getBranding();
        $this->assertStringContainsString($logoPath, $branding['app_logo']);
        $this->assertStringContainsString($loginLogoPath, $branding['app_logo_login']);
        $this->assertStringContainsString($faviconPath, $branding['app_favicon']);
    }

    public function test_administrator_can_remove_custom_branding_logos(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'administrator']);

        $oldLogoPath = 'branding/old_logo.png';
        Storage::disk('public')->put($oldLogoPath, 'dummy content');
        Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $oldLogoPath]);
        Setting::clearBrandingCache();

        Storage::disk('public')->assertExists($oldLogoPath);

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'remove_app_logo' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Storage::disk('public')->assertMissing($oldLogoPath);
        $this->assertNull(Setting::where('key', 'app_logo')->value('value'));

        $branding = Setting::getBranding();
        $this->assertNull($branding['app_logo']);
    }

    public function test_branding_is_shared_globally_via_inertia_props(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Setting::updateOrCreate(['key' => 'app_name'], ['value' => 'Global Desk Portal']);
        Setting::clearBrandingCache();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('branding')
            ->where('branding.app_name', 'Global Desk Portal')
        );
    }

    public function test_non_administrator_cannot_update_branding_settings(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('settings.update'), [
            'app_name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('settings', [
            'key' => 'app_name',
            'value' => 'Hacked Name',
        ]);
    }
}
