<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\SmsMessage;
use App\Models\SmsSchoolFeature;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * SMS for a school (Bhash SMS, App\Services\SmsService), and the log of what
 * went.
 *
 *   welcome   the super admin's switch (Super Admin → Messaging → SMS). On:
 *             a new student or teacher gets their login by SMS only — no
 *             welcome email, no WhatsApp (App\Services\WelcomeSms).
 *   notice    the school's own message to many by SMS (More → Messaging →
 *             Send), on the DLT template "notice".
 *
 * Every SMS takes one of the school's SMS messages a part (MessagePacks);
 * with Prepaid on and none left it is not sent (the welcome then goes by
 * email and WhatsApp as before).
 *
 * Every SMS is a row in sms_messages (welcome | credentials), without its
 * text, which has the password. Off for every school until turned on.
 */
class SchoolSms
{
    /** feature => label. */
    public const FEATURES = [
        'welcome' => 'Welcome by SMS',
        'notice'  => 'SMS notices',
    ];

    /** What a row's feature is called on screen. */
    public const KINDS = [
        'welcome'     => 'Welcome',
        'credentials' => 'Send Credentials',
        'notice'      => 'Notice',
    ];

    /** The longest SMS notice: the DLT template's message parts, 30 characters each. */
    public const NOTICE_MAX = 150;

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
        // Prepaid with no SMS left: the welcome goes by email and WhatsApp, so it still reaches them.
        return SmsService::enabled() && self::enabled($orgId, 'welcome') && MessagePacks::canSend($orgId, 'sms');
    }

    /** Whether SMS notices can go for a school: switched on, Bhash set up and the notice template there. */
    public static function noticeOn(?int $orgId): bool
    {
        return SmsService::enabled() && self::noticeReady() && self::enabled($orgId, 'notice');
    }

    /** Whether the DLT notice template is set up (config services.bhash_sms.templates.notice). */
    public static function noticeReady(): bool
    {
        return trim((string) config('services.bhash_sms.templates.notice.text')) !== '';
    }

    /**
     * A school's notice to many by SMS (More → Messaging → Send, the job
     * App\Jobs\SendMessagingCampaign): the DLT template
     * "{school}: {part} {part} … - SuperLMS", the message cut at word ends
     * into its 30-character parts. Returns 'sent', 'failed' or 'skipped'.
     */
    public static function notice(int $orgId, ?string $phone, string $message, array $who, int $campaignId, string $dedupeKey): string
    {
        if (!self::noticeOn($orgId) || SmsService::normalizePhone($phone) === null) {
            return 'skipped';
        }
        if (self::tracked() && SmsMessage::where('dedupe_key', $dedupeKey)->exists()) {
            return 'skipped';   // gone in an earlier run
        }
        if (!MessagePacks::canSend($orgId, 'sms')) {
            return 'skipped';
        }

        $slots = max(1, substr_count(strtolower((string) config('services.bhash_sms.templates.notice.text')), '{#var#}') - 1);
        $vars  = array_merge(
            [Organization::whereKey($orgId)->value('name') ?? 'School'],
            self::noticeParts($message, $slots),
        );

        return self::send($orgId, 'notice', $phone, 'notice', $vars, $who, ['campaign_id' => $campaignId, 'dedupe_key' => $dedupeKey, 'blank' => '']) ? 'sent' : 'failed';
    }

    /** A message cut at word ends into at most $slots parts of 30 characters or fewer (DLT's {#var#}). */
    public static function noticeParts(string $message, int $slots): array
    {
        $words = preg_split('/\s+/u', trim(Str::ascii($message))) ?: [];
        $parts = [];
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            while (strlen($word) > SmsService::VAR_MAX) {
                $parts[] = substr($word, 0, SmsService::VAR_MAX);
                $word    = substr($word, SmsService::VAR_MAX);
            }
            $last = $parts ? $parts[count($parts) - 1] : null;
            if ($last !== null && strlen($last . ' ' . $word) <= SmsService::VAR_MAX) {
                $parts[count($parts) - 1] = $last . ' ' . $word;
            } elseif ($word !== '') {
                $parts[] = $word;
            }
        }

        return array_slice($parts, 0, max(1, $slots));
    }

    /**
     * Send one SMS and keep its row. Without the tables it still sends,
     * unrecorded. $who: user_id / student_detail_id the message is about.
     */
    public static function send(?int $orgId, string $feature, ?string $phone, string $template, array $vars, array $who = [], array $extra = []): bool
    {
        $to = SmsService::normalizePhone($phone);
        if ($to === null) {
            Log::info('SMS: no usable mobile number, skipped', ['feature' => $feature, 'org' => $orgId] + $who);
            return false;
        }

        // What an unused {#var#} becomes: '-' (the welcome), '' (a notice's spare parts).
        $blank = (string) ($extra['blank'] ?? '-');
        unset($extra['blank']);

        if (!self::tracked()) {
            return SmsService::send($to, $template, $vars, $blank)['ok'];
        }

        // Prepaid: an SMS goes only while the school has SMS messages left.
        if (!MessagePacks::canSend($orgId, 'sms')) {
            Log::info('SMS: no SMS messages left, not sent', ['feature' => $feature, 'org' => $orgId] + $who);
            return false;
        }

        try {
            $row = SmsMessage::create([
                'organization_id'   => $orgId,
                'feature'           => $feature,
                'template'          => $template,
                'user_id'           => $who['user_id'] ?? null,
                'student_detail_id' => $who['student_detail_id'] ?? null,
                'to_phone'          => $to,
                'status'            => 'sending',
            ] + array_filter($extra, fn ($v) => $v !== null));
        } catch (\Illuminate\Database\QueryException $e) {
            if (!empty($extra['dedupe_key']) && SmsMessage::where('dedupe_key', $extra['dedupe_key'])->exists()) {
                return false;   // the same message to the same person is going already
            }
            throw $e;
        }

        $result = SmsService::send($to, $template, $vars, $blank);

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
            'enabled'     => self::enabled($orgId, 'welcome'),
            'notice'      => self::enabled($orgId, 'notice'),
            'noticeReady' => self::noticeReady(),
            'configured'  => SmsService::enabled(),
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
                'who'    => $m->feature === 'notice' ? 'Notice' : (str_starts_with($m->template, 'teacher') ? 'Teacher' : 'Student'),
                'to'     => $m->to_phone ? '+91 ' . $m->to_phone : '—',
                'status' => $m->status,
                'error'  => $m->error,
            ])->all(),
        ];
    }
}
