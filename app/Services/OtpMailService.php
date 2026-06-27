<?php

namespace App\Services;

use App\Models\User;

class OtpMailService
{
    /**
     * Generate a 6-digit OTP, save it to the user, and send via ZeptoMail OTP template.
     */
    public static function sendOtp(User $user, string $panelName): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(2),
        ]);

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
