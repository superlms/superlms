<?php

namespace App\Services;

use App\Support\Platform;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The video link of a booked demo, made the moment the time is picked.
 *
 * Google Meet when a Google account is connected (Super Admin → Calendar →
 * Queries → Booking settings → Connect Google, or GOOGLE_CALENDAR_REFRESH_TOKEN
 * in the env): the meeting is put on that account's Google Calendar with a Meet
 * link, the visitor as a guest (Google sends them its own invite too). Without
 * one, or when Google does not answer, a Jitsi Meet room — free, no account
 * needed, made on the spot — so a booking never fails for want of a link.
 */
class MeetLink
{
    private const SCOPES = 'openid email https://www.googleapis.com/auth/calendar.events';

    private const TOKEN_KEY = 'google_calendar_refresh_token';
    private const EMAIL_KEY = 'google_calendar_email';

    /**
     * A new meeting. Returns ['link', 'provider' => google|jitsi, 'event_id' => Google's id or null].
     */
    public static function create(string $title, string $description, Carbon $start, int $minutes, ?string $guestEmail): array
    {
        if (self::googleReady()) {
            try {
                $event = self::google('post', 'calendars/primary/events?conferenceDataVersion=1&sendUpdates=all', [
                    'summary'     => $title,
                    'description' => $description,
                    'start'       => ['dateTime' => $start->toIso8601String(), 'timeZone' => config('app.timezone')],
                    'end'         => ['dateTime' => $start->copy()->addMinutes($minutes)->toIso8601String(), 'timeZone' => config('app.timezone')],
                    'attendees'   => $guestEmail ? [['email' => $guestEmail]] : [],
                    'conferenceData' => [
                        'createRequest' => [
                            'requestId'             => (string) Str::uuid(),
                            'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                        ],
                    ],
                    'reminders' => ['useDefault' => true],
                ]);

                $link = self::meetLinkOf($event);
                // Google sometimes answers before the Meet room is ready: ask again.
                for ($i = 0; !$link && $i < 3 && !empty($event['id']); $i++) {
                    usleep(700_000);
                    $event = self::google('get', 'calendars/primary/events/' . rawurlencode($event['id']));
                    $link  = self::meetLinkOf($event);
                }

                if ($link) {
                    return ['link' => $link, 'provider' => 'google', 'event_id' => $event['id'] ?? null];
                }
                Log::warning('Google Meet: event made without a Meet link', ['event' => $event['id'] ?? null]);
                if (!empty($event['id'])) {
                    self::cancel($event['id']);
                }
            } catch (\Throwable $e) {
                Log::error('Google Meet: could not make the meeting', ['error' => $e->getMessage()]);
            }
        }

        return ['link' => self::jitsiLink(), 'provider' => 'jitsi', 'event_id' => null];
    }

    /** A booked Google meeting moved to a new time; the Meet link stays. False when it could not be moved. */
    public static function move(string $eventId, Carbon $start, int $minutes): bool
    {
        if (!self::googleReady()) {
            return false;
        }

        try {
            self::google('patch', 'calendars/primary/events/' . rawurlencode($eventId) . '?sendUpdates=all', [
                'start' => ['dateTime' => $start->toIso8601String(), 'timeZone' => config('app.timezone')],
                'end'   => ['dateTime' => $start->copy()->addMinutes($minutes)->toIso8601String(), 'timeZone' => config('app.timezone')],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Google Meet: could not move the meeting', ['event' => $eventId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** Take a meeting off the Google Calendar (the guest is told). */
    public static function cancel(string $eventId): void
    {
        if (!self::googleReady()) {
            return;
        }

        try {
            self::google('delete', 'calendars/primary/events/' . rawurlencode($eventId) . '?sendUpdates=all');
        } catch (\Throwable $e) {
            Log::warning('Google Meet: could not cancel the meeting', ['event' => $eventId, 'error' => $e->getMessage()]);
        }
    }

    public static function jitsiLink(): string
    {
        return 'https://meet.jit.si/SuperLMS-Demo-' . Str::random(14);
    }

    // ─── Google account ────────────────────────────────────────────────────

    /** The app's OAuth client is set in the env (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET). */
    public static function googleConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /** A Google account is connected: links are Google Meet. */
    public static function googleReady(): bool
    {
        return self::googleConfigured() && self::refreshToken() !== null;
    }

    /** The connected account's email, when known. */
    public static function googleEmail(): ?string
    {
        return Platform::get(self::EMAIL_KEY) ?: (filled(config('services.google.refresh_token')) ? 'Set in the server env' : null);
    }

    public static function redirectUri(): string
    {
        return route('super-admin.google.callback');
    }

    public static function authUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'include_granted_scopes' => 'true',
            'state'         => $state,
        ]);
    }

    /** Google's code from the consent screen → the account kept for making meetings. Returns its email. */
    public static function connect(string $code): ?string
    {
        $res = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
        ]);

        if ($res->failed() || !$res->json('refresh_token')) {
            throw new \RuntimeException((string) ($res->json('error_description') ?? $res->json('error') ?? 'Google did not give a refresh token.'));
        }

        $email = null;
        if ($idToken = $res->json('id_token')) {
            $parts = explode('.', (string) $idToken);
            $claims = isset($parts[1]) ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
            $email = is_array($claims) ? ($claims['email'] ?? null) : null;
        }

        Platform::set(self::TOKEN_KEY, Crypt::encryptString((string) $res->json('refresh_token')));
        Platform::set(self::EMAIL_KEY, $email);
        Cache::forget('google_calendar_access_token');

        return $email;
    }

    public static function disconnect(): void
    {
        $token = Platform::get(self::TOKEN_KEY);
        if ($token) {
            try {
                Http::asForm()->timeout(5)->post('https://oauth2.googleapis.com/revoke', ['token' => Crypt::decryptString($token)]);
            } catch (\Throwable) {
                // Forgetting it here is what matters.
            }
        }

        Platform::set(self::TOKEN_KEY, null);
        Platform::set(self::EMAIL_KEY, null);
        Cache::forget('google_calendar_access_token');
    }

    private static function refreshToken(): ?string
    {
        $saved = Platform::get(self::TOKEN_KEY);
        if ($saved) {
            try {
                return Crypt::decryptString($saved);
            } catch (\Throwable) {
                return null;
            }
        }

        return filled(config('services.google.refresh_token')) ? (string) config('services.google.refresh_token') : null;
    }

    private static function accessToken(): string
    {
        return Cache::remember('google_calendar_access_token', now()->addMinutes(50), function () {
            $res = Http::asForm()->connectTimeout(3)->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => self::refreshToken(),
                'grant_type'    => 'refresh_token',
            ]);

            if ($res->failed() || !$res->json('access_token')) {
                throw new \RuntimeException('Google token refresh failed: ' . ($res->json('error_description') ?? $res->json('error') ?? $res->status()));
            }

            return (string) $res->json('access_token');
        });
    }

    private static function google(string $method, string $path, array $body = []): array
    {
        $req = Http::withToken(self::accessToken())->acceptJson()->connectTimeout(3)->timeout(10);
        $url = 'https://www.googleapis.com/calendar/v3/' . $path;
        $res = $method === 'get' || $method === 'delete' ? $req->{$method}($url) : $req->{$method}($url, $body);

        if ($res->status() === 401) {
            Cache::forget('google_calendar_access_token');
        }
        if ($res->failed() && !($method === 'delete' && in_array($res->status(), [404, 410], true))) {
            throw new \RuntimeException('Google Calendar ' . strtoupper($method) . ' failed (' . $res->status() . '): ' . ($res->json('error.message') ?? $res->body()));
        }

        return (array) $res->json();
    }

    private static function meetLinkOf(array $event): ?string
    {
        if (!empty($event['hangoutLink'])) {
            return (string) $event['hangoutLink'];
        }
        foreach ((array) ($event['conferenceData']['entryPoints'] ?? []) as $entry) {
            if (($entry['entryPointType'] ?? null) === 'video' && !empty($entry['uri'])) {
                return (string) $entry['uri'];
            }
        }

        return null;
    }
}
