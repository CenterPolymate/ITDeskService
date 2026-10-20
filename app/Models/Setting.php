<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Retrieve global branding settings with caching.
     *
     * @return array<string, mixed>
     */
    public static function getBranding(): array
    {
        return Cache::remember('system_branding', 86400, function () {
            if (! Schema::hasTable('settings')) {
                return [
                    'app_name' => config('app.name', 'ITDeskService'),
                    'company_name' => 'IT Service Desk',
                    'support_contact' => '',
                    'app_logo' => null,
                    'app_logo_login' => null,
                    'app_favicon' => null,
                ];
            }

            $settings = self::whereIn('key', [
                'app_name',
                'company_name',
                'support_contact',
                'app_logo',
                'app_logo_login',
                'app_favicon',
            ])->pluck('value', 'key');

            return [
                'app_name' => ! empty($settings['app_name']) ? $settings['app_name'] : config('app.name', 'ITDeskService'),
                'company_name' => ! empty($settings['company_name']) ? $settings['company_name'] : 'IT Service Desk',
                'support_contact' => $settings['support_contact'] ?? '',
                'app_logo' => ! empty($settings['app_logo']) ? asset('storage/'.$settings['app_logo']) : null,
                'app_logo_login' => ! empty($settings['app_logo_login']) ? asset('storage/'.$settings['app_logo_login']) : null,
                'app_favicon' => ! empty($settings['app_favicon']) ? asset('storage/'.$settings['app_favicon']) : null,
            ];
        });
    }

    /**
     * Clear cached branding settings.
     */
    public static function clearBrandingCache(): void
    {
        Cache::forget('system_branding');
    }
}
