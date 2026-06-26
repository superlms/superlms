<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailSubject;
    public string $htmlBody;

    /**
     * Create a new message instance.
     *
     * @param string $mailSubject The rendered email subject
     * @param string $htmlBody    The rendered HTML body (placeholders already replaced)
     */
    public function __construct(string $mailSubject, string $htmlBody)
    {
        $this->mailSubject = $mailSubject;
        $this->htmlBody = $htmlBody;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
