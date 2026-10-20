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
                    'support_contact' => '',
                    'app_logo' => null,
                    'app_logo_login' => null,
                    'app_favicon' => null,
                ];
            }

            $settings = self::whereIn('key', [
                'app_name',
                'support_contact',
                'app_logo',
                'app_logo_login',
                'app_favicon',
            ])->pluck('value', 'key');

            return [
                'app_name' => ! empty($settings['app_name']) ? $settings['app_name'] : config('app.name', 'ITDeskService'),
                'support_contact' => $settings['support_contact'] ?? '',
                'app_logo' => ! empty($settings['app_logo']) ? asset('storage/'.$settings['app_logo']).'?v='.substr(md5($settings['app_logo']), 0, 8) : null,
                'app_logo_login' => ! empty($settings['app_logo_login']) ? asset('storage/'.$settings['app_logo_login']).'?v='.substr(md5($settings['app_logo_login']), 0, 8) : null,
                'app_favicon' => ! empty($settings['app_favicon']) ? asset('storage/'.$settings['app_favicon']).'?v='.substr(md5($settings['app_favicon']), 0, 8) : null,
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
