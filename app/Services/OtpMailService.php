<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpMailService
{
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
     * Generate a 6-digit OTP, save it to the user, and deliver it.
     *
     * Primary channel is the ZeptoMail OTP template. If that fails (e.g. the
     * ZeptoMail account is out of credit / unreachable), we fall back to the
     * app's own mailer (SMTP/SES/…) so a single provider outage doesn't lock
     * everyone out of their panel. If neither channel can deliver, a clean
     * exception is thrown for the caller to surface — 2FA is never skipped.
     */
    public static function sendOtp(User $user, string $panelName): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        // ── Primary: ZeptoMail template ──
        try {
            self::sendViaZeptoMail($user, $panelName, $otp);
            return;
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
        if (!self::hasRealMailer()) {
            throw new \RuntimeException("Couldn't send the OTP email right now. Please try again in a moment.");
        }

        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name ?? '', $panelName));
        } catch (\Throwable $e) {
            Log::error('OTP SMTP fallback send failed', [
                'email' => $user->email,
                'panel' => $panelName,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Couldn't send the OTP email right now. Please try again in a moment.");
        }
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
     * Verify the OTP entered by user.
     */
    public static function verifyOtp(User $user, string $enteredOtp): bool
    {
        if (empty($user->otp)) {
            throw new \Exception('No OTP was requested.');
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            self::clearOtp($user);
            throw new \Exception('OTP has expired. Please request a new one.');
        }

        if ($user->otp !== $enteredOtp) {
            throw new \Exception('Invalid OTP. Please try again.');
        }

        self::clearOtp($user);

        return true;
    }

    /**
     * Clear OTP from user after successful verification.
     */
    public static function clearOtp(User $user): void
    {
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
        ]);
    }

    /**
     * Seconds left in the 120 second resend cooldown (0 = resend allowed).
     */
    public static function resendAvailableIn(User $user): int
    {
        if (empty($user->otp_expires_at)) {
            return 0;
        }

        // Carbon 3's diffInSeconds is signed, so diff from the older date
        // to now() to get positive elapsed seconds.
        $otpCreatedAt = \Carbon\Carbon::parse($user->otp_expires_at)->subMinutes(2);
        $elapsed      = (int) $otpCreatedAt->diffInSeconds(now());

        return max(0, 120 - $elapsed);
    }

    /**
     * Check if resend is allowed (120 second cooldown).
     */
    public static function canResend(User $user): bool
    {
        return self::resendAvailableIn($user) === 0;
    }
}
