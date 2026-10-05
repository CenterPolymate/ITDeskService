<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('audit:purge')]
#[Description('Purge old audit logs based on settings')]
class PurgeOldAuditLogs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $setting = Setting::where('key', 'audit_log_retention_years')->first();
        $years = $setting ? (int) $setting->value : 1;

        if ($years >= 99) {
            $this->info('Retention is set to 99 years (keep forever). Skipping purge.');

            return;
        }

        $cutoffDate = now()->subYears($years);
        $count = AuditLog::where('created_at', '<', $cutoffDate)->count();

        if ($count > 0) {
            AuditLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("Successfully purged {$count} old audit logs (older than {$years} years).");
        } else {
            $this->info('No old audit logs found to purge.');
        }
    }
}
