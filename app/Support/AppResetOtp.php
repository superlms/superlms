<?php

namespace App\Support;

use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\WhatsAppService;

/**
 * Where the app's Forgot Password code goes: by email AND on WhatsApp, for a
 * student, a teacher and a school admin alike (user's ask of 2026-11-27).
 *
 * The WhatsApp number is the one the school's other messages to that person
 * already use:
 *
 *   student            the student's own phone, else their account's mobile (as the welcome)
 *   teacher            their account's mobile, else the phone on their teacher record
 *   admin / sub-admin  their own mobile, else the school's ({@see SchoolAdminOtp})
 *   accounts           their account's mobile
 *
 * The code is the same one OtpMailService mails (one code, two channels), so
 * either message is enough to go on with.
 */
class AppResetOtp
{
    /** The panel name the code's email carries. */
    public static function panel(User $user): string
    {
        return match ($user->role) {
            'teacher'            => 'Teacher App',
            'admin', 'sub-admin' => AdminAppOtp::PANEL,
            'accounts'           => 'Accounts Panel',
            default              => 'Student App',
        };
    }

    /** The registered mobile the code goes to on WhatsApp (91 + ten digits), or null. */
    public static function mobile(User $user): ?string
    {
        $candidates = match ($user->role) {
            'admin', 'sub-admin' => [SchoolAdminOtp::mobile($user)],
            'teacher'            => [$user->mobile_number, self::detailPhone(TeacherDetail::class, $user)],
            'accounts'           => [$user->mobile_number],
            default              => [self::detailPhone(StudentDetail::class, $user), $user->mobile_number],
        };

        foreach ($candidates as $raw) {
            if ($to = WhatsAppService::normalizePhone($raw)) {
                return $to;
            }
        }

        return null;
    }

    /** sendOtp()'s WhatsApp list: the registered mobile, or nothing. */
    public static function whatsAppTo(User $user): array
    {
        return array_filter([self::mobile($user)]);
    }

    /**
     * What the app may show of where the code went, from sendOtp()'s delivery
     * report — only the channels that really delivered, half hidden:
     * ['email' => 'ra****ta@gmail.com'|null, 'mobile' => '+91 ••••••5914'|null].
     */
    public static function sentTo(?array $delivery): array
    {
        $email = !empty($delivery['email']['ok']) ? LoginIdentifier::maskEmail($delivery['email']['to'] ?? null) : null;

        $mobile = null;
        foreach ($delivery['whatsapp'] ?? [] as $wa) {
            if (!empty($wa['ok'])) {
                $mobile = SchoolAdminOtp::maskMobile($wa['to']);
                break;
            }
        }

        return ['email' => $email ?: null, 'mobile' => $mobile];
    }

    /** "OTP sent to your email and WhatsApp." — by what really went. */
    public static function sentMessage(array $sentTo, bool $resent = false): string
    {
        $verb = $resent ? 'resent' : 'sent';

        return match (true) {
            $sentTo['email'] && $sentTo['mobile'] => "OTP {$verb} to your email and WhatsApp.",
            (bool) $sentTo['mobile']              => "OTP {$verb} on WhatsApp.",
            default                               => "OTP {$verb} successfully to your email address.",
        };
    }

    /** The phone on the person's student / teacher record, if that table is there. */
    private static function detailPhone(string $model, User $user): ?string
    {
        try {
            return $model::where('user_id', $user->id)->value('phone');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
