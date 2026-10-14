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
 * Every message is a row in whatsapp_messages with its rate (the school's
 * price at the time, config services.whatsapp.rate_per_message — ₹0.55);
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

    /** The school's price for one charged message. */
    public static function rate(): float
    {
        return round((float) config('services.whatsapp.rate_per_message', 0.55), 2);
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
     * One month of a school's messages: how many of each kind went (a failed
     * one is not counted) and what the charged ones come to.
     * ['counts' => [feature => n], 'charged' => n, 'amount' => ₹, 'failed' => n]
     */
    public static function usage(int $orgId, string $month): array
    {
        $counts = array_fill_keys(array_keys(self::FEATURES), 0);
        $out    = ['counts' => $counts, 'charged' => 0, 'amount' => 0.0, 'failed' => 0];
        if (!self::tracked() || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $out;
        }

        $from = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $rows = WhatsAppMessage::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $from->copy()->endOfMonth()])
            ->selectRaw('feature, status, billable, COUNT(*) as n, SUM(rate) as amount')
            ->groupBy('feature', 'status', 'billable')
            ->get();

        foreach ($rows as $r) {
            if ($r->status === 'sending') {
                continue;   // not gone yet (or cut short): never charged
            }
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

    /**
     * What the WhatsApp card shows for a school (Super Admin → Fees → the
     * school, with its switches; the school admin's Fee page, to read): the
     * months to pick from, the month picked (this month when $month is not
     * one of them), that month's usage, which paid messages are on, the rate.
     */
    public static function summary(int $orgId, ?string $month = null): array
    {
        $months = self::months($orgId);
        $month  = $month !== null && isset($months[$month]) ? $month : array_key_first($months);

        $switches = [];
        foreach (self::FEATURES as $feature => [, , $switched]) {
            if ($switched) {
                $switches[$feature] = self::enabled($orgId, $feature);
            }
        }

        return [
            'months'   => $months,
            'month'    => $month,
            'usage'    => self::usage($orgId, $month),
            'switches' => $switches,
            'rate'     => self::rate(),
        ];
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
