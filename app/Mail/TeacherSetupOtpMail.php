<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherSetupOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $teacherName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Maestro Crown College - Teacher Account Setup Code'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-setup-otp'
        );
    }
}

