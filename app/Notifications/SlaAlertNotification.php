<?php

namespace App\Notifications;

use App\Models\HelpdeskCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SlaAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public HelpdeskCase $ticket;

    public string $type; // 'warning' or 'breached'

    public ?int $remainingMinutes;

    /**
     * Create a new notification instance.
     */
    public function __construct(HelpdeskCase $ticket, string $type = 'warning', ?int $remainingMinutes = null)
    {
        $this->ticket = $ticket;
        $this->type = $type;
        $this->remainingMinutes = $remainingMinutes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $statusText = $this->type === 'breached'
            ? 'เกินกำหนดเวลา SLA แล้ว! (Overdue)'
            : 'ใกล้หมดเวลา SLA ในอีก '.($this->remainingMinutes ?? '60').' นาที (Warning)';

        // Log LINE notification simulation
        Log::info("LINE NOTIFY: แจ้งเตือน SLA [{$this->ticket->ticket_no}] - {$statusText} - {$this->ticket->title}");

        $channels = ['database'];

        // Only send mail if user has email and mail is configured
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isBreached = $this->type === 'breached';

        $subject = $isBreached
            ? "⚠️ ด่วนที่สุด! ใบงานเกินกำหนดเวลา SLA: {$this->ticket->ticket_no}"
            : "⏰ แจ้งเตือน: ใบงานใกล้หมดเวลา SLA: {$this->ticket->ticket_no}";

        $greeting = "สวัสดีคุณ {$notifiable->name},";

        $alertMessage = $isBreached
            ? "ใบงานหมายเลข {$this->ticket->ticket_no} ได้เกินกำหนดเวลาส่งมอบงาน (SLA Overdue) แล้ว กรุณาเร่งดำเนินการตรวจสอบและแก้ไขโดยด่วน"
            : "ใบงานหมายเลข {$this->ticket->ticket_no} กำลังจะถึงกำหนดเวลาส่งมอบงาน (SLA Due Date) ภายในอีกประมาณ ".($this->remainingMinutes ?? 60).' นาที';

        $thaiDueDate = $this->ticket->sla_due_at
            ? $this->ticket->sla_due_at->locale('th')->translatedFormat('d F Y H:i น.')
            : 'ไม่ได้ระบุ';

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($alertMessage)
            ->line("รหัสใบงาน: {$this->ticket->ticket_no}")
            ->line("หัวข้อปัญหา: {$this->ticket->title}")
            ->line("ผู้แจ้ง: {$this->ticket->requester_name} ({$this->ticket->department})")
            ->line('ทีมที่รับผิดชอบ: '.($this->ticket->assigned_team_label ?? 'Helpdesk'))
            ->line("กำหนดส่งมอบงาน (SLA): {$thaiDueDate}")
            ->action('เข้าจัดการใบงานทันที', route('tickets.show', $this->ticket->id))
            ->line('ขอบคุณที่ร่วมรักษาคุณภาพการบริการ ITDeskService');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isBreached = $this->type === 'breached';

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_no' => $this->ticket->ticket_no,
            'type' => $isBreached ? 'sla_breached' : 'sla_warning',
            'title' => $isBreached ? 'ใบงานเกินกำหนดเวลา SLA' : 'ใบงานใกล้หมดเวลา SLA',
            'message' => $isBreached
                ? "ใบงาน {$this->ticket->ticket_no} เกินกำหนดเวลา SLA แล้ว"
                : "ใบงาน {$this->ticket->ticket_no} เหลือเวลาไม่ถึง ".($this->remainingMinutes ?? 60).' นาที',
            'sla_due_at' => $this->ticket->sla_due_at?->toIso8601String(),
            'url' => route('tickets.show', $this->ticket->id),
        ];
    }
}
