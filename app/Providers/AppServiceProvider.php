<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
    }
}
