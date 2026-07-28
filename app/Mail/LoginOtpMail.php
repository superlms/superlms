<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic login OTP email used as the SMTP fallback when the primary provider
 * (ZeptoMail) is unavailable. The panel name keeps the subject/body relevant
 * to whichever login flow triggered it (Accounts / School Admin / …).
 */
class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $userName;
    public string $panelName;

    public function __construct(string $otp, string $userName, string $panelName = 'SuperLMS')
    {
        $this->otp = $otp;
        $this->userName = $userName;
        $this->panelName = $panelName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->panelName . ' - OTP Verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }
}
