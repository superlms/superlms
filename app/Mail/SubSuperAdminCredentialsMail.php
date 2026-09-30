<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A new sub super-admin's sign-in details, sent through the app's own mailer
 * (SMTP/SES/…) when ZeptoMail cannot deliver the credentials template.
 */
class SubSuperAdminCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $email,
        public string $password,
        public string $loginUrl,
        public string $forgotUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SuperLMS - Your Super Admin login details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sub-super-admin-credentials',
        );
    }
}
