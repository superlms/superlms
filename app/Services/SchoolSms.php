<?php

namespace App\Services;

use App\Models\SmsMessage;
use App\Models\SmsSchoolFeature;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * SMS for a school (Bhash SMS, App\Services\SmsService), and the log of what
 * went.
 *
 *   welcome   the super admin's switch (Super Admin → Schools → the school →
 *             SMS). On: a new student or teacher gets their login by SMS
 *             only — no welcome email, no WhatsApp (App\Services\WelcomeSms).
 *
 * Every SMS is a row in sms_messages (welcome | credentials), without its
 * text, which has the password. Off for every school until turned on.
 */
class SchoolSms
{
    /** feature => label. */
    public const FEATURES = [
        'welcome' => 'Welcome by SMS',
    ];

    /** What a row's feature is called on screen. */
    public const KINDS = [
        'welcome'     => 'Welcome',
        'credentials' => 'Send Credentials',
    ];

    /** Whether the tables are there yet (the migration of 27 Oct 2026) — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'sms.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('sms_messages') && Schema::hasTable('sms_school_features');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Whether a school has the SMS switched on (off until turned on). */
    public static function enabled(?int $orgId, string $feature = 'welcome'): bool
    {
        if (!$orgId || !self::tracked()) {
            return false;
        }

        $key = "sms.enabled.{$orgId}.{$feature}";
        if (!app()->bound($key)) {
            app()->instance($key, (bool) SmsSchoolFeature::where('organization_id', $orgId)->where('feature', $feature)->value('enabled'));
        }

        return (bool) app($key);
    }

    public static function setEnabled(int $orgId, string $feature, bool $on, ?int $userId = null): void
    {
        if (!self::tracked() || !array_key_exists($feature, self::FEATURES)) {
            return;
        }

        SmsSchoolFeature::updateOrCreate(
            ['organization_id' => $orgId, 'feature' => $feature],
            ['enabled' => $on, 'updated_by' => $userId],
        );
        app()->instance("sms.enabled.{$orgId}.{$feature}", $on);
    }

    /**
     * Whether a school's welcome goes by SMS (and only by SMS): its switch is
     * on and Bhash is set up. With Bhash not set up, the email and WhatsApp
     * still go, so nobody is left without their login.
     */
    public static function welcomeOn(?int $orgId): bool
    {
        return SmsService::enabled() && self::enabled($orgId, 'welcome');
    }

    /**
     * Send one SMS and keep its row. Without the tables it still sends,
     * unrecorded. $who: user_id / student_detail_id the message is about.
     */
    public static function send(?int $orgId, string $feature, ?string $phone, string $template, array $vars, array $who = []): bool
    {
        $to = SmsService::normalizePhone($phone);
        if ($to === null) {
            Log::info('SMS: no usable mobile number, skipped', ['feature' => $feature, 'org' => $orgId] + $who);
            return false;
        }

        if (!self::tracked()) {
            return SmsService::send($to, $template, $vars)['ok'];
        }

        $row = SmsMessage::create([
            'organization_id'   => $orgId,
            'feature'           => $feature,
            'template'          => $template,
            'user_id'           => $who['user_id'] ?? null,
            'student_detail_id' => $who['student_detail_id'] ?? null,
            'to_phone'          => $to,
            'status'            => 'sending',
        ]);

        $result = SmsService::send($to, $template, $vars);

        $row->forceFill([
            'status'              => $result['ok'] ? 'sent' : 'failed',
            'provider_message_id' => $result['id'],
            'error'               => $result['error'],
            'parts'               => max(1, (int) $result['parts']),
        ])->save();

        return $result['ok'];
    }

    // ─── Super Admin → Schools → SMS ─────────────────────────────────────────

    /**
     * What the school's SMS tab shows: the switch, whether Bhash is set up,
     * this month's and all-time counts, and the latest messages with whom
     * they were for.
     */
    public static function summary(int $orgId, int $latest = 25): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $count = fn (?string $status, $from = null) => SmsMessage::where('organization_id', $orgId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->count();

        $rows  = SmsMessage::where('organization_id', $orgId)->latest('id')->limit($latest)->get();
        $names = User::whereIn('id', $rows->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        return [
            'enabled'    => self::enabled($orgId, 'welcome'),
            'configured' => SmsService::enabled(),
            'month'      => [
                'sent'   => $count('sent', $monthStart),
                'failed' => $count('failed', $monthStart),
                'parts'  => (int) SmsMessage::where('organization_id', $orgId)->where('status', 'sent')
                    ->where('created_at', '>=', $monthStart)->sum('parts'),
            ],
            'total'      => $count('sent'),
            'messages'   => $rows->map(fn (SmsMessage $m) => [
                'id'     => $m->id,
                'at'     => $m->created_at,
                'name'   => $names[$m->user_id] ?? '—',
                'kind'   => self::KINDS[$m->feature] ?? $m->feature,
                'who'    => str_starts_with($m->template, 'teacher') ? 'Teacher' : 'Student',
                'to'     => $m->to_phone ? '+91 ' . $m->to_phone : '—',
                'status' => $m->status,
                'error'  => $m->error,
            ])->all(),
        ];
    }
}
