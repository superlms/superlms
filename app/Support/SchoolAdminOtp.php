<?php

namespace App\Support;

use App\Models\User;
use App\Services\SmsService;

/**
 * Where a school admin's or sub-admin's sign-in and forgot-password code goes
 * besides their email: on WhatsApp to their registered mobile number, as the
 * Super Admin's goes to its primary number.
 *
 * The registered number is the account's own mobile_number — for the school
 * admin the one the super admin entered with the school, for a sub-admin the
 * one in More → Users. An account with none falls back to the school's own
 * number, as the forgot password always has.
 */
class SchoolAdminOtp
{
    /** The ten digits the code goes to on WhatsApp, or null when there is none. */
    public static function mobile(User $user): ?string
    {
        if ($own = SmsService::normalizePhone($user->mobile_number)) {
            return $own;
        }

        try {
            return SmsService::normalizePhone($user->organization?->mobile_number);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** sendOtp()'s WhatsApp list: the registered mobile, or nothing. */
    public static function whatsAppTo(User $user): array
    {
        return array_filter([self::mobile($user)]);
    }

    /**
     * The code reached no one: why, channel by channel, from sendOtp()'s
     * delivery report — "Email: …; WhatsApp +91 ••••••5914: …".
     */
    public static function whyNotSent(?array $delivery): string
    {
        $reasons = [];

        if (!empty($delivery['email']['error'])) {
            $reasons[] = 'Email: ' . $delivery['email']['error'];
        }
        foreach ($delivery['whatsapp'] ?? [] as $wa) {
            $reasons[] = 'WhatsApp ' . self::maskMobile($wa['to']) . ': ' . $wa['error'];
        }

        return 'The code could not be sent' . ($reasons ? ' — ' . implode('; ', $reasons) : '') . '. Try Resend OTP in 2 minutes.';
    }

    /** "+91 ••••••8563" — enough to tell which phone to look at. */
    public static function maskMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);

        return '+91 ••••••' . substr($digits, -4);
    }
}
