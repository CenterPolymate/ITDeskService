<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $host,
        public int $port,
        public ?string $encryption,
        public string $sentAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ITDeskService - ทดสอบการตั้งค่า Email (SMTP) สำเร็จ ('.$this->sentAt.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test_smtp',
        );
    }
}
