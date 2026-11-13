<?php

namespace App\Services;

use App\Models\WebsiteDemo;
use App\Support\Platform;
use Illuminate\Support\Facades\Log;

/**
 * What a website demo request is sent, by email and by WhatsApp:
 *
 *  - thanks():    right after the form — thank you, and the private link where
 *                 they pick a date and time for the demo;
 *  - confirmed(): the moment a time is picked — when it is, the Meet link, and
 *                 the same link to change the time (the email adds a calendar
 *                 invite, .ics).
 *
 * The emails are this app's own (resources/views/emails/demo-*.blade.php, sent
 * through ZeptoMail); the WhatsApp ones are the Meta templates named in
 * services.whatsapp.templates.demo_thanks / demo_confirmed (DEMO_BOOKING.md).
 * Neither ever throws: what went and what did not is noted on the request
 * (WebsiteDemo::logMessage) and shown under Calendar → Queries → View.
 */
class DemoMessages
{
    public static function thanks(WebsiteDemo $demo): array
    {
        $url  = $demo->bookingUrl();
        $data = self::common($demo) + ['bookingUrl' => $url];

        $email = self::email(
            $demo,
            'Thank you for requesting a ' . $data['platform'] . ' demo — pick your time',
            self::html('emails.demo-thanks', $data),
        );

        $whatsApp = self::whatsApp($demo, 'demo_thanks', [
            self::firstName($demo),
            $demo->school_name ?: 'your school',
        ]);

        $result = ['type' => 'thanks', 'email' => $email, 'whatsapp' => $whatsApp];
        $demo->logMessage($result);

        return $result;
    }

    public static function confirmed(WebsiteDemo $demo, bool $moved = false): array
    {
        if (!$demo->meeting_at || !$demo->meet_link) {
            return ['type' => 'confirmed', 'email' => 'No meeting yet', 'whatsapp' => 'No meeting yet'];
        }

        $data = self::common($demo) + [
            'moved'        => $moved,
            'when'         => $demo->meetingLabel() . ' (IST)',
            'meetLink'     => $demo->meet_link,
            'bookingUrl'   => $demo->bookingUrl(),
            'googleCalUrl' => self::googleCalendarUrl($demo),
        ];

        $email = self::email(
            $demo,
            ($moved ? 'Your demo has moved — ' : 'Your demo is booked — ') . $demo->meeting_at->format('D, d M · g:i A'),
            self::html('emails.demo-confirmed', $data),
            [[
                'content'   => base64_encode(self::ics($demo, $data['platform'])),
                'mime_type' => 'text/calendar',
                'name'      => 'superlms-demo.ics',
            ]],
        );

        $whatsApp = self::whatsApp($demo, 'demo_confirmed', [
            self::firstName($demo),
            $demo->meeting_at->format('D, d M Y \a\t g:i A') . ' (IST)',
            $demo->meet_link,
        ]);

        $result = ['type' => $moved ? 'moved' : 'confirmed', 'email' => $email, 'whatsapp' => $whatsApp];
        $demo->logMessage($result);

        return $result;
    }

    /** The email as it is sent (thanks | confirmed), for a sample request — Booking settings → Preview. */
    public static function preview(string $which): string
    {
        $demo = new WebsiteDemo([
            'full_name' => 'Rahul Sharma', 'school_name' => 'Doon Public School', 'phone' => '9876543210',
            'email' => 'rahul@school.in', 'city' => 'Dehradun', 'no_of_students' => '500-1000', 'role' => 'Principal',
            'booking_token' => 'sample0000000000000000000000000000000000',
            'meeting_at' => now()->addDay()->setTime(11, 0), 'meeting_minutes' => 30,
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
        ]);
        $data = self::common($demo) + ['bookingUrl' => $demo->bookingUrl()];

        if ($which === 'confirmed') {
            return self::html('emails.demo-confirmed', $data + [
                'moved' => false, 'when' => $demo->meetingLabel() . ' (IST)', 'meetLink' => $demo->meet_link,
                'googleCalUrl' => self::googleCalendarUrl($demo),
            ]);
        }

        return self::html('emails.demo-thanks', $data);
    }

    /** A calendar invite for the booked demo. */
    public static function ics(WebsiteDemo $demo, string $platform = 'SuperLMS'): string
    {
        $utc  = fn ($t) => $t->copy()->utc()->format('Ymd\THis\Z');
        $esc  = fn (string $s) => str_replace(["\\", ";", ",", "\r\n", "\n"], ["\\\\", "\\;", "\\,", "\\n", "\\n"], $s);
        $desc = "Your {$platform} live demo.\nJoin: {$demo->meet_link}\nChange the time: {$demo->bookingUrl()}";

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SuperLMS//Demo booking//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:demo-' . $demo->id . '-' . substr((string) $demo->booking_token, 0, 8) . '@' . (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'superlms.in'),
            'DTSTAMP:' . $utc(now()),
            'DTSTART:' . $utc($demo->meeting_at),
            'DTEND:' . $utc($demo->meetingEnd()),
            'SUMMARY:' . $esc($platform . ' demo'),
            'DESCRIPTION:' . $esc($desc),
            'LOCATION:' . $esc((string) $demo->meet_link),
            'URL:' . $demo->meet_link,
            'BEGIN:VALARM',
            'TRIGGER:-PT15M',
            'ACTION:DISPLAY',
            'DESCRIPTION:' . $esc($platform . ' demo in 15 minutes'),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    /** "Add to Google Calendar" for the booked demo. */
    public static function googleCalendarUrl(WebsiteDemo $demo): string
    {
        $fmt = fn ($t) => $t->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action'   => 'TEMPLATE',
            'text'     => 'SuperLMS demo',
            'dates'    => $fmt($demo->meeting_at) . '/' . $fmt($demo->meetingEnd()),
            'details'  => 'Join: ' . $demo->meet_link,
            'location' => $demo->meet_link,
        ]);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private static function common(WebsiteDemo $demo): array
    {
        $details = Platform::details();

        return [
            'demo'     => $demo,
            'name'     => self::firstName($demo),
            'platform' => $details['platform_name'] ?: 'SuperLMS',
            'logoUrl'  => Platform::logoUrl(),
            'minutes'  => (int) ($demo->meeting_minutes ?: \App\Support\DemoBooking::settings()['minutes']),
            'phone'    => $details['company_mobile'] ?? null,
            'website'  => rtrim((string) config('app.url'), '/'),
        ];
    }

    /** The email's body set in its frame (logo, card, footer). */
    private static function html(string $view, array $data): string
    {
        return view('emails.demo-layout', $data + ['content' => view($view, $data)->render()])->render();
    }

    private static function firstName(WebsiteDemo $demo): string
    {
        $name = trim((string) $demo->full_name);

        return $name !== '' ? (string) preg_split('/\s+/', $name)[0] : 'there';
    }

    /** 'ok', or why it did not go. */
    private static function email(WebsiteDemo $demo, string $subject, string $html, array $attachments = []): string
    {
        if (!filter_var((string) $demo->email, FILTER_VALIDATE_EMAIL)) {
            return 'No usable email';
        }

        try {
            ZeptoMailService::sendRaw($subject, $html, (string) $demo->email, (string) $demo->full_name, null, null, $attachments);

            return 'ok';
        } catch (\Throwable $e) {
            Log::warning('Demo email not sent', ['demo' => $demo->id, 'error' => $e->getMessage()]);

            return mb_strimwidth($e->getMessage(), 0, 160, '…');
        }
    }

    /** 'ok', or why it did not go. The template's URL button carries the booking token. */
    private static function whatsApp(WebsiteDemo $demo, string $template, array $body): string
    {
        $name = (string) config('services.whatsapp.templates.' . $template);
        if ($name === '') {
            return 'No template set';
        }

        $res = WhatsAppService::sendTemplateResult((string) $demo->phone, $name, $body, $demo->ensureToken());

        return $res['ok'] ? 'ok' : mb_strimwidth((string) $res['error'], 0, 160, '…');
    }
}
