<?php

namespace App\Services;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSchoolFeature;
use App\Support\FeeDues;
use App\Support\StudentFeeTotals;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp messages sent for a school, and what the school pays for them.
 *
 *   absent_alert   a student marked absent today       student_absent_alert   charged, when the school has it on
 *   fee_received   a fee booked for a student          fee_received           charged, when the school has it on
 *   credentials    Send Credentials on a student's or teacher's view            charged
 *   welcome        the welcome on adding a student or teacher                   free (the platform's)
 *
 * Every message is a row in whatsapp_messages with its rate (RATE, a fixed
 * ₹0.66 a message; a row keeps the rate it went at);
 * Meta's status webhook (WhatsAppWebhookController) marks one failed, and a
 * failed message is not charged. The paid messages are off for every school
 * until the super admin turns them on (Super Admin → Fees → the school).
 */
class SchoolWhatsApp
{
    /** feature => [label, charged, has an on/off switch]. */
    public const FEATURES = [
        'absent_alert' => ['Absent alert', true, true],
        'fee_received' => ['Fee received', true, true],
        'credentials'  => ['Credentials', true, false],
        'welcome'      => ['Welcome (free)', false, false],
    ];

    /** What a school pays for one charged message: 66 paise, fixed. */
    public const RATE = 0.66;

    public static function rate(): float
    {
        return self::RATE;
    }

    /** Whether the tables are there yet (the migration of 14 Oct 2026) — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'whatsapp_messages.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('whatsapp_messages') && Schema::hasTable('whatsapp_school_features');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Whether a school has one of the switched messages on (off until turned on). */
    public static function enabled(int $orgId, string $feature): bool
    {
        if (!self::tracked()) {
            return false;
        }

        return (bool) WhatsAppSchoolFeature::where('organization_id', $orgId)->where('feature', $feature)->value('enabled');
    }

    public static function setEnabled(int $orgId, string $feature, bool $on): void
    {
        if (!self::tracked() || !(self::FEATURES[$feature][2] ?? false)) {
            return;
        }

        WhatsAppSchoolFeature::updateOrCreate(
            ['organization_id' => $orgId, 'feature' => $feature],
            ['enabled' => $on],
        );
    }

    /**
     * Send one template and keep its row. A dedupe key already used means the
     * message has gone (or is going) — nothing is sent twice. Without the
     * tables (before the migration) it still sends, unrecorded.
     *
     * $who: user_id / student_detail_id the message is about.
     */
    public static function send(?int $orgId, string $feature, ?string $phone, string $template, array $body, string|array|null $buttons = null, array $who = [], ?string $dedupeKey = null): bool
    {
        if (!WhatsAppService::enabled()) {
            return false;
        }
        $to = WhatsAppService::normalizePhone($phone);
        if ($to === null) {
            Log::info('WhatsApp: no usable mobile number, skipped', ['feature' => $feature, 'org' => $orgId] + $who);
            return false;
        }

        if (!self::tracked()) {
            return WhatsAppService::sendTemplate($to, $template, $body, $buttons);
        }

        $charged = (bool) (self::FEATURES[$feature][1] ?? false);

        // Claim the row first: the unique dedupe key keeps a second run out.
        try {
            $row = WhatsAppMessage::create([
                'organization_id'   => $orgId,
                'feature'           => $feature,
                'template'          => $template,
                'user_id'           => $who['user_id'] ?? null,
                'student_detail_id' => $who['student_detail_id'] ?? null,
                'to_phone'          => $to,
                'dedupe_key'        => $dedupeKey,
                'status'            => 'sending',
                'billable'          => $charged,
                'rate'              => $charged ? self::rate() : 0,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($dedupeKey !== null && WhatsAppMessage::where('dedupe_key', $dedupeKey)->exists()) {
                return false;   // already sent
            }
            report($e);
            return WhatsAppService::sendTemplate($to, $template, $body, $buttons);
        }

        $result = WhatsAppService::sendTemplateResult($to, $template, $body, $buttons);

        $row->forceFill([
            'status'        => $result['ok'] ? 'sent' : 'failed',
            'wa_message_id' => $result['id'],
            'error'         => $result['error'],
        ])->save();

        return $result['ok'];
    }

    /** How far a message has got; a later status never takes one back. */
    private const STATUS_ORDER = ['sending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3];

    /**
     * A status from Meta's webhook (WhatsAppWebhookController) for the message
     * with that id: sent → delivered → read, never backwards (Meta's posts can
     * come out of order); failed — not delivered, so not charged — unless it
     * was already delivered or read. Returns whether the row changed; a
     * message that is not ours (or a status we do not keep) changes nothing.
     */
    public static function applyStatus(string $waMessageId, string $status, ?string $error = null): bool
    {
        if (!self::tracked() || $waMessageId === '') {
            return false;
        }
        $row = WhatsAppMessage::where('wa_message_id', $waMessageId)->first();
        if (!$row || $row->status === $status) {
            return false;
        }

        $now = self::STATUS_ORDER[$row->status] ?? null;   // null: failed
        if ($status === 'failed') {
            if (($now ?? 0) >= self::STATUS_ORDER['delivered']) {
                return false;
            }
            $row->forceFill(['status' => 'failed', 'error' => $error ? mb_substr($error, 0, 1000) : $row->error])->save();

            return true;
        }

        $next = self::STATUS_ORDER[$status] ?? null;
        if ($next === null || $next <= ($now ?? self::STATUS_ORDER['sent'])) {
            return false;
        }
        // From failed only to delivered or read — it reached the phone after
        // all, so it is charged again; a late "sent" leaves it failed.
        $row->forceFill(['status' => $status, 'error' => $now === null ? null : $row->error])->save();

        return true;
    }

    /** Whether a dedupe key has been used — a message for it went (or is going). */
    public static function sentFor(string $dedupeKey): bool
    {
        return self::tracked() && WhatsAppMessage::where('dedupe_key', $dedupeKey)->exists();
    }

    // ─── The two school messages ───────────────────────────────────────────────

    /** "CLASS 9 - A", as the templates' samples have it. */
    public static function classLine(StudentDetail $student): string
    {
        $class = trim((string) ($student->standard?->name ?? ''));
        $sec   = trim((string) ($student->section?->name ?? ''));

        return $class === '' ? '-' : ($sec !== '' ? $class . ' - ' . $sec : $class);
    }

    /** The number a student's messages go to — the student's own, else their account's (as the welcome). */
    public static function studentPhone(StudentDetail $student): ?string
    {
        return $student->phone ?: $student->user?->mobile_number;
    }

    /**
     * student_absent_alert: {{1}} student {{2}} class {{3}} date {{4}} school.
     * One a student a day (the dedupe key), only for a school that has it on.
     */
    public static function absentAlert(StudentDetail $student, string $date): bool
    {
        $orgId = (int) $student->organization_id;
        if (!self::enabled($orgId, 'absent_alert')) {
            return false;
        }

        return self::send(
            $orgId,
            'absent_alert',
            self::studentPhone($student),
            (string) config('services.whatsapp.templates.absent', 'student_absent_alert'),
            [
                $student->full_name ?: ($student->user?->name ?? 'Student'),
                self::classLine($student),
                \Illuminate\Support\Carbon::parse($date)->format('d M Y'),
                Organization::whereKey($orgId)->value('name') ?? 'School',
            ],
            null,
            ['user_id' => $student->user_id, 'student_detail_id' => $student->id],
            'absent_alert:' . $student->id . ':' . \Illuminate\Support\Carbon::parse($date)->toDateString(),
        );
    }

    /**
     * fee_received: {{1}} school {{2}} student {{3}} class {{4}} fee type
     * {{5}} amount {{6}} date {{7}} total fee {{8}} fee remaining — the year's
     * whole fee and what is left after this payment, as the receipt's Overall.
     * Sent after the request (and after its transaction), so the payment and
     * the totals are saved; only for a school that has it on.
     */
    public static function feeReceived(FeePayment|TransportFeePayment $payment): void
    {
        if (!WhatsAppService::enabled() || !self::tracked()) {
            return;
        }
        if (strtolower((string) $payment->payment_mode) === 'concession' || (float) $payment->amount <= 0) {
            return;
        }
        if (!self::enabled((int) $payment->organization_id, 'fee_received')) {
            return;
        }

        $kind = $payment instanceof TransportFeePayment ? 'transport' : 'fee';
        $id   = (int) $payment->id;

        DB::afterCommit(function () use ($kind, $id) {
            dispatch(function () use ($kind, $id) {
                try {
                    self::sendFeeReceived($kind, $id);
                } catch (\Throwable $e) {
                    Log::error('WhatsApp fee_received failed', ['kind' => $kind, 'id' => $id, 'error' => $e->getMessage()]);
                }
            })->afterResponse();
        });
    }

    /** The fee message for one saved payment ('fee' = fee_payments, 'transport' = transport_fee_payments). */
    public static function sendFeeReceived(string $kind, int $id): bool
    {
        $payment = $kind === 'transport' ? TransportFeePayment::find($id) : FeePayment::find($id);
        $student = $payment ? StudentDetail::with(['user', 'standard', 'section'])->find($payment->student_detail_id) : null;
        if (!$payment || !$student) {
            return false;
        }

        $orgId  = (int) $payment->organization_id;
        $totals = StudentFeeTotals::overall($orgId, $student);
        $money  = fn ($v) => number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);

        $type = match (true) {
            $kind === 'transport'                                   => 'Transport',
            $payment->fee_type === 'transport'                      => 'Transport',
            $payment->fee_type === 'penalty'                        => 'Late Fee',
            FeeDues::isDues($payment)                               => 'Last Year Dues',
            default                                                 => 'Academic',
        };

        return self::send(
            $orgId,
            'fee_received',
            self::studentPhone($student),
            (string) config('services.whatsapp.templates.fee', 'fee_received'),
            [
                Organization::whereKey($orgId)->value('name') ?? 'School',
                $student->full_name ?: ($student->user?->name ?? 'Student'),
                self::classLine($student),
                $type,
                $money($payment->amount),
                \Illuminate\Support\Carbon::parse($payment->payment_date ?? now())->format('d M Y'),
                $money($totals['total']),
                $money($totals['balance']),
            ],
            null,
            ['user_id' => $student->user_id, 'student_detail_id' => $student->id],
            'fee_received:' . $kind . ':' . $id,
        );
    }

    // ─── What a school has been charged ────────────────────────────────────────

    /** The months a school has messages in, newest first ('2026-10' => 'Oct 2026'); this month always. */
    public static function months(int $orgId): array
    {
        $months = [now()->format('Y-m') => now()->format('M Y')];
        if (!self::tracked()) {
            return $months;
        }

        $first = WhatsAppMessage::where('organization_id', $orgId)->min('created_at');
        if ($first) {
            $m = \Illuminate\Support\Carbon::parse($first)->startOfMonth();
            while ($m->lte(now())) {
                $months[$m->format('Y-m')] = $m->format('M Y');
                $m->addMonth();
            }
        }
        krsort($months);

        return $months;
    }

    /**
     * One month of a school's messages ('2026-10'), as usageBetween() reads it.
     */
    public static function usage(int $orgId, string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return self::usageBetween($orgId, now()->addDay(), now());   // nothing
        }
        $from = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();

        return self::usageBetween($orgId, $from, $from->copy()->endOfMonth());
    }

    /**
     * A school's messages between two moments: how many of each kind went (a
     * failed one is not counted), what the charged ones come to, and how far
     * they got (sent / delivered / read, and failed — not delivered).
     * ['counts' => [feature => n], 'charged' => n, 'amount' => ₹, 'failed' => n,
     *  'statuses' => ['sent' => n, 'delivered' => n, 'read' => n, 'failed' => n]]
     */
    public static function usageBetween(int $orgId, CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = array_fill_keys(array_keys(self::FEATURES), 0);
        $out    = ['counts' => $counts, 'charged' => 0, 'amount' => 0.0, 'failed' => 0,
                   'statuses' => ['sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0]];
        if (!self::tracked() || $to->lt($from)) {
            return $out;
        }

        $rows = WhatsAppMessage::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('feature, status, billable, COUNT(*) as n, SUM(rate) as amount')
            ->groupBy('feature', 'status', 'billable')
            ->get();

        foreach ($rows as $r) {
            if (!isset($out['statuses'][$r->status])) {
                continue;   // sending: not gone yet (or cut short), never charged
            }
            $out['statuses'][$r->status] += (int) $r->n;
            if ($r->status === 'failed') {
                $out['failed'] += (int) $r->n;
                continue;
            }
            $feature = array_key_exists($r->feature, $counts) ? $r->feature : 'welcome';
            $out['counts'][$feature] += (int) $r->n;
            if ($r->billable) {
                $out['charged'] += (int) $r->n;
                $out['amount']  += (float) $r->amount;
            }
        }
        $out['amount'] = round($out['amount'], 2);

        return $out;
    }

    // ─── The WhatsApp analytics (Super Admin → Schools → a school → WhatsApp;
    //     the school admin's Analytics) ──────────────────────────────────────────

    /**
     * The periods the analytics can show: '' — the last 30 days — then each
     * month with messages, newest first ('2026-10' => 'Oct 2026').
     */
    public static function periods(int $orgId): array
    {
        return ['' => 'Last 30 days'] + self::months($orgId);
    }

    /**
     * [from, to, label] of a period: a month ('2026-10'), up to today at most;
     * '' (or anything else) — the last 30 days, today among them.
     */
    public static function periodRange(?string $period): array
    {
        if ($period !== null && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $from = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfDay();
            $to   = $from->copy()->endOfMonth();

            return [$from, $to->gt(now()) ? now()->endOfDay() : $to, $from->format('M Y')];
        }

        return [now()->subDays(29)->startOfDay(), now()->endOfDay(), 'Last 30 days'];
    }

    /** Which of the switched messages a school has on: [feature => bool]. */
    public static function switches(int $orgId): array
    {
        $out = [];
        foreach (self::FEATURES as $feature => [, , $switched]) {
            if ($switched) {
                $out[$feature] = self::enabled($orgId, $feature);
            }
        }

        return $out;
    }

    /**
     * Day by day between two moments: how many of each kind went, how many
     * failed and what the charged ones came to.
     * ['days' => ['2026-10-15', …], 'labels' => ['15 Oct', …], 'counts' => [feature => [n, …]],
     *  'total' => [n, …], 'failed' => [n, …], 'amount' => [₹, …]]
     */
    public static function daily(int $orgId, CarbonInterface $from, CarbonInterface $to): array
    {
        $days = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $days[$d->toDateString()] = ['failed' => 0, 'amount' => 0.0] + array_fill_keys(array_keys(self::FEATURES), 0);
        }

        if ($days && self::tracked()) {
            $rows = WhatsAppMessage::where('organization_id', $orgId)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as day, feature, status, billable, COUNT(*) as n, SUM(rate) as amount')
                ->groupBy('day', 'feature', 'status', 'billable')
                ->get();

            foreach ($rows as $r) {
                $day = substr((string) $r->day, 0, 10);
                if (!isset($days[$day]) || $r->status === 'sending') {
                    continue;
                }
                if ($r->status === 'failed') {
                    $days[$day]['failed'] += (int) $r->n;
                    continue;
                }
                $feature = array_key_exists($r->feature, self::FEATURES) ? $r->feature : 'welcome';
                $days[$day][$feature] += (int) $r->n;
                if ($r->billable) {
                    $days[$day]['amount'] += (float) $r->amount;
                }
            }
        }

        $counts = [];
        foreach (array_keys(self::FEATURES) as $feature) {
            $counts[$feature] = array_values(array_column($days, $feature));
        }

        return [
            'days'   => array_keys($days),
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('d M'), array_keys($days)),
            'counts' => $counts,
            'total'  => array_values(array_map(fn ($d) => array_sum(array_intersect_key($d, self::FEATURES)), $days)),
            'failed' => array_values(array_column($days, 'failed')),
            'amount' => array_values(array_map(fn ($d) => round($d['amount'], 2), $days)),
        ];
    }

    /**
     * The messages themselves between two moments, newest first — the first
     * $limit and how many there are — with whom each went to and what it cost.
     * $feature: one kind or null; $status: sent | delivered | read | failed or null.
     */
    public static function messages(int $orgId, CarbonInterface $from, CarbonInterface $to, ?string $feature = null, ?string $status = null, int $limit = 25): array
    {
        if (!self::tracked() || $to->lt($from)) {
            return ['rows' => [], 'count' => 0];
        }

        $query = WhatsAppMessage::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'sending')
            ->when($feature && isset(self::FEATURES[$feature]), fn ($q) => $q->where('feature', $feature))
            ->when(in_array($status, ['sent', 'delivered', 'read', 'failed'], true), fn ($q) => $q->where('status', $status));

        $rows     = (clone $query)->orderByDesc('created_at')->orderByDesc('id')->limit(max(1, $limit))->get();
        $students = StudentDetail::whereIn('id', $rows->pluck('student_detail_id')->filter()->unique())->pluck('full_name', 'id');
        $users    = \App\Models\User::whereIn('id', $rows->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        return [
            'count' => (clone $query)->count(),
            'rows'  => $rows->map(fn (WhatsAppMessage $m) => [
                'id'      => $m->id,
                'at'      => $m->created_at,
                'feature' => $m->feature,
                'label'   => self::FEATURES[$m->feature][0] ?? $m->feature,
                'name'    => ($m->student_detail_id ? ($students[$m->student_detail_id] ?? null) : null)
                             ?: ($m->user_id ? ($users[$m->user_id] ?? null) : null),
                'phone'   => self::showPhone($m->to_phone),
                'status'  => $m->status,
                'error'   => $m->error,
                // What it cost: free, not charged (it failed), or its rate.
                'charge'  => !$m->billable ? 'free' : ($m->status === 'failed' ? 'none' : (float) $m->rate),
            ])->all(),
        ];
    }

    /** '919876543210' as '+91 98765 43210'. */
    public static function showPhone(?string $phone): string
    {
        $phone = (string) $phone;

        return preg_match('/^91(\d{5})(\d{5})$/', $phone, $m) ? "+91 {$m[1]} {$m[2]}" : ($phone !== '' ? '+' . $phone : '—');
    }

    /** Whether a school has anything to show: a message sent, or a paid message turned on. */
    public static function inUse(int $orgId): bool
    {
        return self::tracked() && (
            WhatsAppMessage::where('organization_id', $orgId)->exists()
            || WhatsAppSchoolFeature::where('organization_id', $orgId)->where('enabled', true)->exists()
        );
    }
}
