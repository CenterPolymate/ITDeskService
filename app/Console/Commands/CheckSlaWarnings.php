<?php

namespace App\Console\Commands;

use App\Models\HelpdeskCase;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SlaAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckSlaWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check-warnings {--dry-run : Run without updating tickets or sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check active tickets nearing or breaching SLA and dispatch alert notifications to IT staff';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Cache::put('scheduler_last_heartbeat', now()->toIso8601String(), 86400);

        $this->info('Starting SLA alert check...');

        $alertEnabled = Setting::where('key', 'sla_alert_enabled')->value('value');
        if ($alertEnabled === '0') {
            $this->warn('SLA alerts are currently disabled in System Settings.');

            return self::SUCCESS;
        }

        $thresholdMinutes = (int) (Setting::where('key', 'sla_warning_threshold_minutes')->value('value') ?: 60);
        $isDryRun = (bool) $this->option('dry-run');

        $this->line("Threshold: {$thresholdMinutes} minutes before SLA due date.");
        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No notifications will be sent and no database records will be modified.');
        }

        $now = now();
        $warningWindow = $now->copy()->addMinutes($thresholdMinutes);

        // 1. Process Warning Tickets (SLA nearing expiration)
        $warningTickets = HelpdeskCase::query()
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '>', $now)
            ->where('sla_due_at', '<=', $warningWindow)
            ->whereNull('sla_warning_sent_at')
            ->get();

        $this->info("Found {$warningTickets->count()} ticket(s) nearing SLA expiration.");

        foreach ($warningTickets as $ticket) {
            $remainingMinutes = max(1, (int) round($now->diffInMinutes($ticket->sla_due_at, false)));
            $this->line("  -> [WARNING] {$ticket->ticket_no}: '{$ticket->title}' - Remaining: {$remainingMinutes} min (Due: {$ticket->sla_due_at->format('d/m/Y H:i')})");

            $recipients = $this->resolveRecipients($ticket);

            if (! $isDryRun) {
                foreach ($recipients as $recipient) {
                    try {
                        $recipient->notify(new SlaAlertNotification($ticket, 'warning', $remainingMinutes));
                    } catch (\Throwable $e) {
                        Log::error("Failed to send SLA warning notification for ticket {$ticket->ticket_no} to {$recipient->email}: {$e->getMessage()}");
                    }
                }

                $ticket->update(['sla_warning_sent_at' => now()]);
            }
        }

        // 2. Process Breached Tickets (SLA already expired while still active)
        $breachedTickets = HelpdeskCase::query()
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<=', $now)
            ->whereNull('sla_breached_sent_at')
            ->get();

        $this->info("Found {$breachedTickets->count()} ticket(s) currently breaching SLA.");

        foreach ($breachedTickets as $ticket) {
            $overdueMinutes = (int) round($ticket->sla_due_at->diffInMinutes($now, false));
            $this->warn("  -> [BREACHED] {$ticket->ticket_no}: '{$ticket->title}' - Overdue by: {$overdueMinutes} min (Due was: {$ticket->sla_due_at->format('d/m/Y H:i')})");

            $recipients = $this->resolveRecipients($ticket);

            if (! $isDryRun) {
                foreach ($recipients as $recipient) {
                    try {
                        $recipient->notify(new SlaAlertNotification($ticket, 'breached', null));
                    } catch (\Throwable $e) {
                        Log::error("Failed to send SLA breach notification for ticket {$ticket->ticket_no} to {$recipient->email}: {$e->getMessage()}");
                    }
                }

                $ticket->update(['sla_breached_sent_at' => now()]);
            }
        }

        $this->info('SLA check completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Resolve the target IT staff recipients for a given ticket.
     *
     * @return Collection<int, User>
     */
    protected function resolveRecipients(HelpdeskCase $ticket): Collection
    {
        $recipients = collect();

        // 1. If assigned to a specific person
        if (! empty($ticket->assigned_to)) {
            $assignedUser = User::where('name', $ticket->assigned_to)
                ->where('is_active', true)
                ->first();

            if ($assignedUser) {
                $recipients->push($assignedUser);
            }
        }

        // 2. If escalated to a team, notify active members of that team
        if (! empty($ticket->escalated_to_team)) {
            $roleKey = 'team_'.strtolower(trim($ticket->escalated_to_team));
            $teamMembers = User::where('role', $roleKey)
                ->where('is_active', true)
                ->get();

            $recipients = $recipients->merge($teamMembers);
        }

        // 3. Always include Helpdesk and Managers for oversight
        $managersAndHelpdesk = User::whereIn('role', ['helpdesk', 'manager', 'manager_software', 'manager_network_hardware'])
            ->where('is_active', true)
            ->get();

        $recipients = $recipients->merge($managersAndHelpdesk);

        // If no IT users found at all, fallback to active Administrator
        if ($recipients->isEmpty()) {
            $recipients = User::where('role', 'administrator')
                ->where('is_active', true)
                ->get();
        }

        return $recipients->unique('id');
    }
}
