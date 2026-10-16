<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Cache::put('scheduler_last_heartbeat', now()->toIso8601String(), 86400);
})->everyMinute()->name('scheduler_heartbeat');

Schedule::command('audit:purge')->daily()->timezone('Asia/Bangkok');
Schedule::command('backup:run --disable-notifications')->dailyAt('00:00')->timezone('Asia/Bangkok')->appendOutputTo(storage_path('logs/backup.log'));
Schedule::command('backup:clean --disable-notifications')->dailyAt('00:30')->timezone('Asia/Bangkok')->appendOutputTo(storage_path('logs/backup.log'));
Schedule::command('sla:check-warnings')->everyMinute();
