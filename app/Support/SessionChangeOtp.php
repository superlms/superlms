<?php

namespace App\Support;

use App\Models\AcademicSession;
use App\Models\User;
use App\Services\OtpMailService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Making another academic session the current one (Super Admin → Session)
 * takes two codes, both entered on one screen: one sent to the Super Admin's
 * registered email, a different one on WhatsApp to the account's primary
 * number (Profile → OTP Numbers). Neither code alone is enough.
 *
 * The codes go to the main Super Admin: the account itself when it is the one
 * signed in, else (a sub super-admin) the first super-admin account — so a
 * sub super-admin needs the Super Admin to read them out.
 *
 * Each request is bound to who asked and to the session asked for; a wrong
 * pair counts against it, and after MAX_WRONG it is gone and must be asked
 * again. The codes are never logged.
 */
class SessionChangeOtp
{
    /** How long both codes can be entered after they are sent. */
    public const CODE_TTL_SECONDS = 600;

    /** The wait before a channel's code can be sent again. */
    public const RESEND_SECONDS = 60;

    /** Wrong tries before the request is thrown away. */
    public const MAX_WRONG = 5;

    public const CHANNELS = ['email', 'whatsapp'];

    /** What the email's "team" line says the code is for. */
    private const PANEL = 'Super Admin (change current session)';

    /** The account whose email and WhatsApp get the codes. */
    public static function approver(User $actor): ?User
    {
        if ($actor->role === 'super-admin') {
            return $actor;
        }

        return User::where('role', 'super-admin')->orderBy('id')->first();
    }

    /**
     * Where the codes would go, masked — or why they can't go.
     *
     * @return array{email: string, whatsapp: string, error: ?string}
     */
    public static function destinations(User $actor): array
    {
        $approver = self::approver($actor);
        $email    = $approver ? User::cleanEmail((string) $approver->email) : '';
        $phone    = $approver ? OtpNumbers::primary($approver) : null;

        $error = match (true) {
            !$approver                                    => 'There is no Super Admin account to send the codes to.',
            !filter_var($email, FILTER_VALIDATE_EMAIL)    => 'The Super Admin account has no registered email address.',
            !$phone                                       => 'The Super Admin account has no WhatsApp number. Add one in Profile → OTP Numbers.',
            default                                       => null,
        };

        return [
            'email'    => $email !== '' ? self::maskEmail($email) : '',
            'whatsapp' => $phone ? self::maskMobile($phone) : '',
            'error'    => $error,
        ];
    }

    /**
     * Open a request for $session and send both codes. Returns the request's
     * token and what each channel's delivery came to:
     *   [token, ['email' => ['ok' => bool, 'error' => ?string], 'whatsapp' => […]]]
     *
     * @throws \RuntimeException when the codes have nowhere to go
     */
    public static function start(User $actor, AcademicSession $session): array
    {
        $where = self::destinations($actor);
        if ($where['error']) {
            throw new \RuntimeException($where['error']);
        }

        $token   = Str::random(40);
        $request = [
            'actor_id'    => (int) $actor->id,
            'approver_id' => (int) self::approver($actor)->id,
            'session_id'  => (int) $session->id,
            'wrong'       => 0,
            'email'       => null,
            'whatsapp'    => null,
        ];

        $delivery = [];
        foreach (self::CHANNELS as $channel) {
            $delivery[$channel] = self::send($request, $channel);
        }

        self::put($token, $request);

        return [$token, $delivery];
    }

    /**
     * Send one channel's code again (a new code; the old one stops working).
     *
     * @return array{ok: bool, error: ?string, wait: int}
     */
    public static function resend(User $actor, ?string $token, string $channel): array
    {
        $request = self::request($actor, $token);

        if (!$request || !in_array($channel, self::CHANNELS, true)) {
            return ['ok' => false, 'error' => 'This request has expired. Start again.', 'wait' => 0];
        }

        if ($wait = self::waitFor($request, $channel)) {
            return ['ok' => false, 'error' => null, 'wait' => $wait];
        }

        $result = self::send($request, $channel);
        self::put($token, $request);

        return $result + ['wait' => 0];
    }

    /** Unix time each channel's code may be sent again at. */
    public static function resendAt(User $actor, ?string $token): array
    {
        $request = self::request($actor, $token);
        $out     = [];

        foreach (self::CHANNELS as $channel) {
            $sent          = $request[$channel]['sent_at'] ?? 0;
            $out[$channel] = $sent ? $sent + self::RESEND_SECONDS : 0;
        }

        return $out;
    }

    /**
     * Check both codes for $sessionId. Returns [] when both are right — the
     * request is used up — else the errors by channel ('email', 'whatsapp')
     * or 'request' when it is gone.
     */
    public static function verify(User $actor, ?string $token, int $sessionId, string $emailCode, string $whatsAppCode): array
    {
        $request = self::request($actor, $token);

        if (!$request || (int) $request['session_id'] !== $sessionId) {
            return ['request' => 'These codes have expired. Close this and click Make current again.'];
        }

        $entered = ['email' => $emailCode, 'whatsapp' => $whatsAppCode];
        $errors  = [];

        foreach (self::CHANNELS as $channel) {
            $code = $request[$channel] ?? null;

            if (!$code) {
                $errors[$channel] = 'No code has gone yet — click Resend.';
            } elseif (now()->timestamp > $code['expires_at']) {
                $errors[$channel] = 'This code has expired — click Resend for a new one.';
            } elseif (!hash_equals($code['hash'], self::hash($entered[$channel]))) {
                $errors[$channel] = 'Wrong code.';
            }
        }

        if (!$errors) {
            Cache::forget(self::key($token));
            return [];
        }

        $request['wrong']++;

        if ($request['wrong'] >= self::MAX_WRONG) {
            Cache::forget(self::key($token));
            return ['request' => 'Too many wrong codes. Close this and click Make current again for new codes.'];
        }

        self::put($token, $request);
        $left = self::MAX_WRONG - $request['wrong'];

        return $errors + ['tries' => $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.'];
    }

    public static function forget(?string $token): void
    {
        if ($token) {
            Cache::forget(self::key($token));
        }
    }

    // ─── Inside ──────────────────────────────────────────────────────────────

    /** A new code for one channel, kept (hashed) on the request and sent. */
    private static function send(array &$request, string $channel): array
    {
        $approver = User::find($request['approver_id']);

        if (!$approver) {
            return ['ok' => false, 'error' => 'The Super Admin account is no longer there.'];
        }

        // The two codes are never the same.
        $other = $request[$channel === 'email' ? 'whatsapp' : 'email']['hash'] ?? null;
        do {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($other !== null && hash_equals($other, self::hash($otp)));

        $request[$channel] = [
            'hash'       => self::hash($otp),
            'sent_at'    => now()->timestamp,
            'expires_at' => now()->addSeconds(self::CODE_TTL_SECONDS)->timestamp,
        ];

        if ($channel === 'email') {
            // The address as it should read — an old save can carry invisible characters. Not saved.
            $approver->email = User::cleanEmail((string) $approver->email);

            return OtpMailService::deliverByEmail($approver, self::PANEL, $otp);
        }

        $phone = OtpNumbers::primary($approver);

        return $phone
            ? OtpMailService::deliverByWhatsApp($phone, $otp)
            : ['ok' => false, 'error' => 'The Super Admin account has no WhatsApp number.'];
    }

    private static function waitFor(array $request, string $channel): int
    {
        $sent = $request[$channel]['sent_at'] ?? 0;

        return max(0, $sent + self::RESEND_SECONDS - now()->timestamp);
    }

    /** The request behind a token, when it is the asker's own. */
    private static function request(User $actor, ?string $token): ?array
    {
        $request = $token ? Cache::get(self::key($token)) : null;

        return is_array($request) && (int) $request['actor_id'] === (int) $actor->id ? $request : null;
    }

    private static function put(string $token, array $request): void
    {
        Cache::put(self::key($token), $request, now()->addSeconds(self::CODE_TTL_SECONDS + self::RESEND_SECONDS));
    }

    private static function key(string $token): string
    {
        return 'session_change_otp:' . $token;
    }

    private static function hash(string $otp): string
    {
        return hash_hmac('sha256', 'session-change|' . trim($otp), (string) config('app.key'));
    }

    /** "su•••••••@gmail.com" */
    public static function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 2) . str_repeat('•', max(3, mb_strlen($name) - 2)) . ($domain !== '' ? '@' . $domain : '');
    }

    /** "+91 ••••••5914" */
    public static function maskMobile(string $mobile): string
    {
        $digits = WhatsAppService::normalizePhone($mobile) ?? preg_replace('/\D+/', '', $mobile);

        return '+91 ••••••' . substr((string) $digits, -4);
    }
}
