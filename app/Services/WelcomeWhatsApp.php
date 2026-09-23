<?php

namespace App\Services;

use App\Models\AccountSetupLink;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * The WhatsApp message a new student or teacher gets, beside the welcome email
 * (which is unchanged). It carries their admission number or username and a
 * button to a page with their password and the app to download.
 *
 *   student  admission_confirmed        {{1}} name  {{2}} class  {{3}} school  {{4}} admission no.
 *   teacher  teacher_joining_confirmed  {{1}} name  {{2}} school  {{3}} username
 *   both     button "View details" → /account-setup/{token}
 *
 * Sent after the response, like the email, so saving never waits on it; and
 * nothing here can make a save fail.
 */
class WelcomeWhatsApp
{
    public static function student(int $userId): void
    {
        if (!WhatsAppService::enabled()) {
            return;
        }

        dispatch(function () use ($userId) {
            try {
                $user   = User::find($userId);
                $detail = StudentDetail::with(['standard', 'section'])->where('user_id', $userId)->first();
                if (!$user || !$detail) {
                    return;
                }

                $class = trim(($detail->standard->name ?? '') . ($detail->section ? ' - ' . $detail->section->name : ''));

                WhatsAppService::sendTemplate(
                    $detail->phone ?: $user->mobile_number,
                    config('services.whatsapp.templates.student', 'admission_confirmed'),
                    [
                        $detail->full_name ?: $user->name,
                        $class !== '' ? $class : '-',
                        self::school($user),
                        $detail->admission_no ?: '-',
                    ],
                    AccountSetupLink::issue($user),
                );
            } catch (\Throwable $e) {
                Log::error('WelcomeWhatsApp student failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    public static function teacher(int $userId): void
    {
        if (!WhatsAppService::enabled()) {
            return;
        }

        dispatch(function () use ($userId) {
            try {
                $user = User::find($userId);
                if (!$user || !$user->username) {
                    return;
                }

                WhatsAppService::sendTemplate(
                    $user->mobile_number,
                    config('services.whatsapp.templates.teacher', 'teacher_joining_confirmed'),
                    [
                        $user->name,
                        self::school($user),
                        $user->username,
                    ],
                    AccountSetupLink::issue($user),
                );
            } catch (\Throwable $e) {
                Log::error('WelcomeWhatsApp teacher failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    /**
     * Whether an edit moved the person to a different mobile — the new one
     * gets the message too. The same number written another way ("98765
     * 43210", "+91…") is not a change.
     */
    public static function numberChanged(?string $old, ?string $new): bool
    {
        $new = WhatsAppService::normalizePhone($new);

        return $new !== null && $new !== WhatsAppService::normalizePhone($old);
    }

    private static function school(User $user): string
    {
        return Organization::find($user->organization_id)?->name ?? 'your school';
    }
}
