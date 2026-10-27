<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * The welcome by SMS, for a school the super admin has turned SMS on for
 * (App\Services\SchoolSms). It takes the place of the welcome email and the
 * WhatsApp: everything is in the SMS itself.
 *
 *   student  student_welcome  name, school, class, section, admission no., password, app link
 *   teacher  teacher_welcome  name, school, username, password, app link
 *
 * The same times the WhatsApp went: on adding someone, on a changed mobile,
 * and on Send Credentials (WelcomeWhatsApp hands over to this). Sent after
 * the response, so saving never waits on it; nothing here can make a save
 * fail. The password is the one the account has now (users.password_plain);
 * it is never changed for an SMS.
 */
class WelcomeSms
{
    /** What the SMS says when the password cannot be read back (an old account). */
    public const PASSWORD_UNKNOWN = 'your current one';

    /** Whether this school's welcome goes by SMS — and so not by email or WhatsApp. */
    public static function on(?int $orgId): bool
    {
        return SchoolSms::welcomeOn($orgId);
    }

    /** on(), for the school of that account. */
    public static function forUser(int $userId): bool
    {
        if (!SmsService::enabled() || !SchoolSms::tracked()) {
            return false;   // asked before the query: most schools never get this far
        }

        return self::on((int) User::whereKey($userId)->value('organization_id') ?: null);
    }

    /** $feature: 'welcome' on adding someone (or a new mobile), 'credentials' for Send Credentials. */
    public static function student(int $userId, string $feature = 'welcome'): void
    {
        dispatch(function () use ($userId, $feature) {
            try {
                $user   = User::find($userId);
                $detail = StudentDetail::with(['standard', 'section'])->where('user_id', $userId)->first();
                if (!$user || !$detail) {
                    return;
                }

                SchoolSms::send(
                    (int) $user->organization_id ?: null,
                    $feature,
                    $detail->phone ?: $user->mobile_number,
                    'student_welcome',
                    self::studentVars($user, $detail),
                    ['user_id' => $user->id, 'student_detail_id' => $detail->id],
                );
            } catch (\Throwable $e) {
                Log::error('WelcomeSms student failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    public static function teacher(int $userId, string $feature = 'welcome'): void
    {
        dispatch(function () use ($userId, $feature) {
            try {
                $user = User::find($userId);
                if (!$user || !$user->username) {
                    return;
                }

                SchoolSms::send(
                    (int) $user->organization_id ?: null,
                    $feature,
                    $user->mobile_number,
                    'teacher_welcome',
                    self::teacherVars($user),
                    ['user_id' => $user->id],
                );
            } catch (\Throwable $e) {
                Log::error('WelcomeSms teacher failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    /** The student template's {#var#}, in order. */
    public static function studentVars(User $user, StudentDetail $detail): array
    {
        return [
            $detail->full_name ?: $user->name,
            self::school($user),
            $detail->standard?->name ?: '-',
            $detail->section?->name ?: '-',
            $detail->admission_no ?: '-',
            self::password($user),
        ];
    }

    /** The teacher template's {#var#}, in order. */
    public static function teacherVars(User $user): array
    {
        return [
            $user->name,
            self::school($user),
            $user->username,
            self::password($user),
        ];
    }

    /**
     * Send Credentials by SMS (WelcomeWhatsApp::resend hands over to this when
     * the school has SMS on). Returns ['ok' => bool, 'message' => string].
     */
    public static function resend(User $user, string $kind): array
    {
        if ($kind === 'student') {
            $detail = StudentDetail::where('user_id', $user->id)->first();
            if (!$detail) {
                return ['ok' => false, 'message' => 'Student details not found.'];
            }
            $phone = $detail->phone ?: $user->mobile_number;   // as student() picks it
        } else {
            if (!$user->username) {
                return ['ok' => false, 'message' => 'This teacher has no username yet, so there is nothing to send.'];
            }
            $phone = $user->mobile_number;
        }

        $to = SmsService::normalizePhone($phone);
        if ($to === null) {
            return ['ok' => false, 'message' => 'No valid mobile number to send the credentials to.'];
        }

        $kind === 'student' ? self::student((int) $user->id, 'credentials') : self::teacher((int) $user->id, 'credentials');

        return ['ok' => true, 'message' => 'Credentials sent by SMS to +91' . $to . '.'];
    }

    private static function password(User $user): string
    {
        $plain = $user->plainPassword();

        return $plain !== null && $plain !== '' ? $plain : self::PASSWORD_UNKNOWN;
    }

    private static function school(User $user): string
    {
        return Organization::find($user->organization_id)?->name ?? 'your school';
    }
}
