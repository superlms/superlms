<?php

namespace App\Services;

use App\Exceptions\OtpDeliveryException;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Email OTPs, one per request. Every sendOtp() opens (or, on resend, renews) a
 * request with its own code and returns a token for it; a code only verifies
 * against the token it was sent for. So when the same account asks for codes
 * from two devices at once, each device signs in with its own code — the other
 * device's code is simply wrong there — and neither request cancels the other.
 */
class OtpMailService
{
    /** Wrong OTP entries allowed before the user is made to wait. */
    public const MAX_ATTEMPTS = 3;

    /** How long that wait lasts. */
    public const LOCKOUT_MINUTES = 5;

    /** How long a code can be entered after it is sent. */
    public const CODE_TTL_SECONDS = 120;

    /** Minimum gap between two codes for the same request. */
    public const RESEND_COOLDOWN_SECONDS = 120;

    /** How long a verified request can still be used to set a new password. */
    public const VERIFIED_TTL_SECONDS = 600;

    /** How long a request is remembered after its last code was sent. */
    private const REQUEST_TTL_SECONDS = 900;

    /**
     * Whether login 2-step (OTP) verification is currently enabled.
     *
     * Temporarily false while ZeptoMail is out of credit. When false, the
     * login flows sign the user in straight after the password check and skip
     * the OTP send/verify steps — the OTP code stays in place, just unused.
     */
    public static function loginOtpEnabled(): bool
    {
        return (bool) config('services.otp.login_enabled', true);
    }

    /**
     * Generate a 6-digit OTP for a request of this user's, and deliver it.
     * Returns the request's token, which the caller keeps and hands back to
     * verifyOtp(). Pass the token of the caller's current request to resend:
     * the request keeps its token and only its old code stops working.
     *
     * Primary channel is the ZeptoMail OTP template. If that fails (e.g. the
     * ZeptoMail account is out of credit / unreachable), we fall back to the
     * app's own mailer (SMTP/SES/…) so a single provider outage doesn't lock
     * everyone out of their panel. If neither channel can deliver, an
     * OtpDeliveryException is thrown for the caller to surface — 2FA is never
     * skipped.
     */
    public static function sendOtp(User $user, string $panelName, ?string $challenge = null): string
    {
        // Resending must not be a way around the lockout.
        if ($remaining = self::lockoutSecondsRemaining($user)) {
            throw new \RuntimeException(self::lockoutMessage($remaining));
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // A token that isn't this user's starts a request of its own.
        if (!$challenge || !self::request($user, $challenge)) {
            $challenge = Str::random(40);
        }

        $ttl = now()->addSeconds(self::REQUEST_TTL_SECONDS);
        Cache::put(self::requestKey($challenge), [
            'user_id'     => $user->id,
            'code'        => self::hashCode($otp),
            'sent_at'     => now()->timestamp,
            'expires_at'  => now()->addSeconds(self::CODE_TTL_SECONDS)->timestamp,
            'verified_at' => null,
        ], $ttl);
        Cache::put(self::latestKey($user), $challenge, $ttl);

        // ── Primary: ZeptoMail template ──
        try {
            self::sendViaZeptoMail($user, $panelName, $otp);
            return $challenge;
        } catch (\Throwable $e) {
            Log::warning('OTP primary (ZeptoMail) send failed — trying SMTP fallback', [
                'email' => $user->email,
                'panel' => $panelName,
                'error' => $e->getMessage(),
            ]);
        }

        // ── Fallback: app mailer (SMTP/SES/…) ──
        // Only meaningful when a real delivery transport is configured. The
        // 'log'/'array' mailers "succeed" without delivering anything, which
        // would strand the user on the verify screen with no code — so treat
        // those as no fallback and surface a clean error instead.
        $failed = "Couldn't send the OTP email right now. Please try again in a moment.";

        if (!self::hasRealMailer()) {
            throw new OtpDeliveryException($failed, $challenge, $otp);
        }

        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name ?? '', $panelName));
        } catch (\Throwable $e) {
            Log::error('OTP SMTP fallback send failed', [
                'email' => $user->email,
                'panel' => $panelName,
                'error' => $e->getMessage(),
            ]);
            throw new OtpDeliveryException($failed, $challenge, $otp);
        }

        return $challenge;
    }

    /**
     * Deliver the OTP through the ZeptoMail transactional template.
     */
    private static function sendViaZeptoMail(User $user, string $panelName, string $otp): void
    {
        $templateKey = config('services.zeptomail.otp_template_key');

        if (!$templateKey) {
            throw new \RuntimeException('ZEPTOMAIL_OTP_TEMPLATE_KEY is not configured.');
        }

        ZeptoMailService::sendTemplate($templateKey, $user->email, $user->name, [
            'OTP' => $otp,
            'name' => $user->name,
            'organization_name' => 'SuperLMS',
            'team' => $panelName,
            'product_name' => 'SuperLMS',
        ]);
    }

    /**
     * True when the default mailer is a real delivery transport (not log/array).
     */
    private static function hasRealMailer(): bool
    {
        $mailer = config('mail.default');

        return !in_array($mailer, ['log', 'array', null, ''], true);
    }

    /**
     * Verify the OTP entered by the user against the request it was entered
     * for — a code sent for any other request, even the same user's, fails.
     * A verified request is used up, unless $forReset keeps it (marked
     * verified) for consumeVerified() to let a new password through.
     */
    public static function verifyOtp(User $user, string $enteredOtp, ?string $challenge, bool $forReset = false): bool
    {
        if ($remaining = self::lockoutSecondsRemaining($user)) {
            throw new \Exception(self::lockoutMessage($remaining));
        }

        if (!$challenge) {
            throw new \Exception('No OTP was requested.');
        }

        $request = self::request($user, $challenge);

        if (!$request) {
            throw new \Exception('OTP has expired. Please request a new one.');
        }

        if ($request['verified_at']) {
            throw new \Exception('This OTP has already been used. Please request a new one.');
        }

        if (now()->timestamp > $request['expires_at']) {
            Cache::forget(self::requestKey($challenge));
            throw new \Exception('OTP has expired. Please request a new one.');
        }

        if (!hash_equals($request['code'], self::hashCode($enteredOtp))) {
            $attempts = self::registerFailedAttempt($user);

            if ($attempts >= self::MAX_ATTEMPTS) {
                // Burn the code too, so sitting out the wait still requires a
                // freshly mailed OTP rather than another go at this one.
                Cache::forget(self::requestKey($challenge));
                throw new \Exception(self::lockoutMessage(self::LOCKOUT_MINUTES * 60));
            }

            $left = self::MAX_ATTEMPTS - $attempts;
            throw new \Exception('Invalid OTP. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.');
        }

        if ($forReset) {
            $request['verified_at'] = now()->timestamp;
            Cache::put(self::requestKey($challenge), $request, now()->addSeconds(self::VERIFIED_TTL_SECONDS));
        } else {
            Cache::forget(self::requestKey($challenge));
        }
        self::clearAttempts($user);

        return true;
    }

    /**
     * True — once — when this user's request was verified within the last
     * VERIFIED_TTL_SECONDS. A verified request is used up whether or not it
     * was still fresh.
     */
    public static function consumeVerified(User $user, ?string $challenge): bool
    {
        $request = $challenge ? self::request($user, $challenge) : null;

        if (!$request || !$request['verified_at']) {
            return false;
        }

        Cache::forget(self::requestKey($challenge));

        return now()->timestamp - $request['verified_at'] <= self::VERIFIED_TTL_SECONDS;
    }

    /**
     * Token of the user's most recent request. Only for API clients that
     * predate tokens — with it, the latest code is the one that counts.
     */
    public static function latestChallenge(User $user): ?string
    {
        return Cache::get(self::latestKey($user));
    }

    /** The request behind a token, when it exists and belongs to the user. */
    private static function request(User $user, string $challenge): ?array
    {
        $request = Cache::get(self::requestKey($challenge));

        return is_array($request) && (int) $request['user_id'] === (int) $user->id
            ? $request
            : null;
    }

    private static function requestKey(string $challenge): string
    {
        return 'otp_request:' . $challenge;
    }

    private static function latestKey(User $user): string
    {
        return 'otp_latest_request:' . $user->id;
    }

    private static function hashCode(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key'));
    }

    /**
     * Record a wrong entry and return the running count. Reaching the limit
     * starts the lockout window.
     */
    private static function registerFailedAttempt(User $user): int
    {
        $attempts = (int) Cache::get(self::attemptsKey($user), 0) + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put(
                self::lockoutKey($user),
                now()->addMinutes(self::LOCKOUT_MINUTES)->timestamp,
                now()->addMinutes(self::LOCKOUT_MINUTES),
            );
            Cache::forget(self::attemptsKey($user));
        } else {
            // Outlive the lockout window so attempts can't be reset by waiting
            // slightly less than it.
            Cache::put(self::attemptsKey($user), $attempts, now()->addMinutes(self::LOCKOUT_MINUTES * 2));
        }

        return $attempts;
    }

    /**
     * Unix timestamp the lockout lifts at, or 0 when the user isn't locked out.
     * Absolute rather than a countdown so the browser can tick it down itself.
     */
    public static function lockedUntil(User $user): int
    {
        $until = (int) Cache::get(self::lockoutKey($user), 0);

        return $until > now()->timestamp ? $until : 0;
    }

    public static function lockoutSecondsRemaining(User $user): int
    {
        $until = self::lockedUntil($user);

        return $until > 0 ? $until - now()->timestamp : 0;
    }

    /** Wipe the attempt counter and any active lockout. */
    public static function clearAttempts(User $user): void
    {
        Cache::forget(self::attemptsKey($user));
        Cache::forget(self::lockoutKey($user));
    }

    private static function attemptsKey(User $user): string
    {
        return 'otp_attempts:' . $user->id;
    }

    private static function lockoutKey(User $user): string
    {
        return 'otp_lockout:' . $user->id;
    }

    private static function lockoutMessage(int $seconds): string
    {
        return sprintf(
            'Too many incorrect attempts. Try again after %d:%02d.',
            intdiv($seconds, 60),
            $seconds % 60,
        );
    }

    /**
     * Seconds left in a request's resend cooldown (0 = resend allowed). Another
     * request of the same user's doesn't hold this one back.
     */
    public static function resendAvailableIn(User $user, ?string $challenge): int
    {
        $request = $challenge ? self::request($user, $challenge) : null;

        if (!$request || $request['verified_at']) {
            return 0;
        }

        return max(0, $request['sent_at'] + self::RESEND_COOLDOWN_SECONDS - now()->timestamp);
    }

    /**
     * Check if resend is allowed for a request.
     */
    public static function canResend(User $user, ?string $challenge): bool
    {
        return self::resendAvailableIn($user, $challenge) === 0;
    }
}
