<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            Login::class,
            function ($event) {
                // อย่าบันทึก last_login_at หากเป็นการสลับสิทธิ์ (Impersonate / Login As)
                if (session()->has('impersonated_by')) {
                    return;
                }

                if ($event->user) {
                    $event->user->update(['last_login_at' => now()]);
                }
            }
        );

        // นำการตั้งค่า SMTP จากฐานข้อมูล (หากมีกำหนดไว้) มาใช้ในระบบส่งอีเมลอัตโนมัติ
        try {
            if (Schema::hasTable('settings')) {
                $smtpHost = Setting::where('key', 'smtp_host')->value('value');
                if (! empty($smtpHost) && $smtpHost !== 'smtp.mailtrap.io') {
                    $smtpPort = Setting::where('key', 'smtp_port')->value('value') ?: 587;
                    $smtpUsername = Setting::where('key', 'smtp_username')->value('value');
                    $smtpPassword = Setting::where('key', 'smtp_password')->value('value');
                    $smtpEncryption = Setting::where('key', 'smtp_encryption')->value('value');
                    $smtpFromAddress = Setting::where('key', 'smtp_from_address')->value('value');

                    $encryption = strtolower(trim((string) $smtpEncryption));
                    if ($encryption === 'none' || $encryption === 'null' || $encryption === '') {
                        $encryption = null;
                    }

                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.transport' => 'smtp',
                        'mail.mailers.smtp.host' => $smtpHost,
                        'mail.mailers.smtp.port' => (int) $smtpPort,
                        'mail.mailers.smtp.encryption' => $encryption,
                        'mail.mailers.smtp.username' => $smtpUsername,
                        'mail.mailers.smtp.password' => $smtpPassword,
                    ]);

                    $fromAddress = ! empty($smtpFromAddress) ? $smtpFromAddress : $smtpUsername;
                    if (! empty($fromAddress)) {
                        config(['mail.from.address' => $fromAddress]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ป้องกันข้อผิดพลาดระหว่างรันคำสั่ง CLI หรือ Migration
        }
    }
}
