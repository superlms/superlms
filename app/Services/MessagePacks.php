<?php

namespace App\Services;

use App\Models\Messaging\EmailMessage;
use App\Models\Messaging\MessagingPack;
use App\Models\Messaging\MessagingPayment;
use App\Models\Organization;
use App\Models\SmsMessage;
use App\Models\WhatsAppMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * A school's messages: so many WhatsApp, SMS and email messages, put in its
 * account in packs, each message it sends taking one (an SMS one a part).
 *
 *   left = messages given (approved packs) − taken back − sent since packs came
 *
 * The sent ones are read live from their own rows — a WhatsApp or email
 * that failed takes none, a free one (the welcome) neither — so nothing is
 * copied. Packs come two ways:
 *
 *   Give Messages   the Super Admin (Messaging → Give Messages): paid now (the
 *                   payment is kept), to be paid later (the school owes it) or
 *                   free
 *   Buy Messages    the school (More → Messaging → Buy Messages): it pays by
 *                   UPI / bank and says so; the pack is pending until the
 *                   Super Admin approves it, and approving keeps the payment
 *
 * What a school owes = what its packs cost − what it paid (Record Payment
 * for the rest). Payments are the Ledger's Messaging credits. With Prepaid
 * on (Settings), a message goes only while the school has one left on its
 * channel.
 */
class MessagePacks
{
    public const CHANNELS = ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'Email'];

    /** The pack sizes offered first (any number can be typed). */
    public const SIZES = [500, 1000, 2500, 5000, 10000];

    /** Fewer left than this on a channel the school uses is "running low". */
    public const LOW = 100;

    /** Ways a payment comes in. */
    public const MODES = ['UPI', 'Bank Transfer', 'Cash', 'Cheque', 'Card', 'Other'];

    // ─── Set-up ─────────────────────────────────────────────────────────────

    /** Whether the tables are there yet (the migration of 21 Nov 2026) — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'message_packs.tracked';
        if (!app()->bound($key)) {
            try {
                $has = MessagingWallet::tracked() && Schema::hasTable('messaging_packs') && Schema::hasTable('messaging_payments');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Messages are counted against the packs from this moment (the day packs came). */
    public static function since(): Carbon
    {
        $since = MessagingWallet::setting('packs_since');

        try {
            return $since ? Carbon::parse($since) : Carbon::create(2026, 11, 21);
        } catch (\Throwable $e) {
            return Carbon::create(2026, 11, 21);
        }
    }

    public static function label(string $channel): string
    {
        return self::CHANNELS[$channel] ?? ucfirst($channel);
    }

    /** What one message of a channel costs a school (Settings). */
    public static function rate(string $channel): float
    {
        return match ($channel) {
            'whatsapp' => MessagingWallet::whatsappRate(),
            'sms'      => MessagingWallet::smsRate(),
            'email'    => MessagingWallet::emailRate(),
            default    => 0.0,
        };
    }

    public static function rates(): array
    {
        return array_map(fn ($ch) => self::rate($ch), array_combine(array_keys(self::CHANNELS), array_keys(self::CHANNELS)));
    }

    private static function zero(): array
    {
        return array_fill_keys(array_keys(self::CHANNELS), 0);
    }

    // ─── Messages sent ──────────────────────────────────────────────────────

    /** The rows that take a message on a channel, or null before its table is there. */
    private static function sentQuery(string $channel)
    {
        return match ($channel) {
            'whatsapp' => SchoolWhatsApp::tracked() ? WhatsAppMessage::query()->where('billable', true)->where('status', '!=', 'failed') : null,
            'email'    => SchoolEmail::tracked() ? EmailMessage::query()->where('billable', true)->where('status', '!=', 'failed') : null,
            'sms'      => SchoolSms::tracked() ? SmsMessage::query()->where('status', '!=', 'failed') : null,
            default    => null,
        };
    }

    /** The failed ones of a channel (they take nothing). */
    private static function failedQuery(string $channel)
    {
        return match ($channel) {
            'whatsapp' => SchoolWhatsApp::tracked() ? WhatsAppMessage::query()->where('billable', true)->where('status', 'failed') : null,
            'email'    => SchoolEmail::tracked() ? EmailMessage::query()->where('billable', true)->where('status', 'failed') : null,
            'sms'      => SchoolSms::tracked() ? SmsMessage::query()->where('status', 'failed') : null,
            default    => null,
        };
    }

    /** An SMS takes one message a part; the others one a message. */
    private static function countSql(string $channel): string
    {
        return $channel === 'sms' ? 'COALESCE(SUM(CASE WHEN parts > 0 THEN parts ELSE 1 END), 0)' : 'COUNT(*)';
    }

    /**
     * Messages sent on a channel per school between two moments:
     * [org id => n]. $fromPacks: counted only since packs came (what a pack
     * pays for); off for the analytics, which counts every one.
     */
    public static function sentBy(string $channel, ?array $orgIds = null, ?CarbonInterface $from = null, ?CarbonInterface $to = null, bool $fromPacks = true, bool $failed = false): array
    {
        $query = $failed ? self::failedQuery($channel) : self::sentQuery($channel);
        if (!$query) {
            return [];
        }
        if ($fromPacks) {
            $from = $from && $from->gt(self::since()) ? $from : self::since();
        }
        if ($from && $to && $to->lt($from)) {
            return [];
        }

        return $query
            ->whereNotNull('organization_id')
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->groupBy('organization_id')
            ->selectRaw('organization_id, ' . self::countSql($channel) . ' AS n')
            ->pluck('n', 'organization_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** One school's messages sent on each channel: [channel => n]. */
    public static function sent(int $orgId, ?CarbonInterface $from = null, ?CarbonInterface $to = null, bool $fromPacks = true): array
    {
        $out = self::zero();
        foreach (array_keys(self::CHANNELS) as $ch) {
            $out[$ch] = (int) (self::sentBy($ch, [$orgId], $from, $to, $fromPacks)[$orgId] ?? 0);
        }

        return $out;
    }

    // ─── Messages given and left ────────────────────────────────────────────

    /** Messages given less taken back, approved only: [org id => [channel => n]]. */
    public static function givenBy(?array $orgIds = null): array
    {
        if (!self::tracked()) {
            return [];
        }

        $out = [];
        MessagingPack::approved()
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->groupBy('organization_id', 'channel')
            ->selectRaw("organization_id, channel, SUM(CASE WHEN type = 'take' THEN -messages ELSE messages END) AS n")
            ->get()
            ->each(function ($r) use (&$out) {
                $out[(int) $r->organization_id] ??= self::zero();
                if (array_key_exists($r->channel, self::CHANNELS)) {
                    $out[(int) $r->organization_id][$r->channel] = (int) $r->n;
                }
            });

        return $out;
    }

    /** What a school has left on each channel: [channel => n] (below zero when it sent more than it has, Prepaid off). */
    public static function left(int $orgId): array
    {
        $given = self::givenBy([$orgId])[$orgId] ?? self::zero();
        $sent  = self::sent($orgId);

        return array_map(fn ($ch) => $given[$ch] - $sent[$ch], array_combine(array_keys(self::CHANNELS), array_keys(self::CHANNELS)));
    }

    /** Whether $n messages may go on a channel: always, unless Prepaid is on and the school has too few left. */
    public static function canSend(?int $orgId, string $channel, int $n = 1): bool
    {
        if (!$orgId || $n <= 0 || !self::tracked() || !MessagingWallet::prepaid()) {
            return true;
        }

        return (self::left($orgId)[$channel] ?? 0) >= $n;
    }

    // ─── Money ──────────────────────────────────────────────────────────────

    /**
     * What each school's packs cost and what it paid:
     * [org id => ['charged', 'paid', 'due', 'advance']].
     */
    public static function moneyBy(?array $orgIds = null): array
    {
        if (!self::tracked()) {
            return [];
        }

        $blank = ['charged' => 0.0, 'paid' => 0.0, 'due' => 0.0, 'advance' => 0.0];
        $out   = [];
        MessagingPack::approved()->where('type', 'give')
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->groupBy('organization_id')->selectRaw('organization_id, SUM(amount) AS amount')->get()
            ->each(function ($r) use (&$out, $blank) {
                $out[(int) $r->organization_id] = ['charged' => round((float) $r->amount, 2)] + $blank;
            });
        MessagingPayment::query()
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->groupBy('organization_id')->selectRaw('organization_id, SUM(amount) AS amount')->get()
            ->each(function ($r) use (&$out, $blank) {
                $id = (int) $r->organization_id;
                $out[$id] ??= $blank;
                $out[$id]['paid'] = round((float) $r->amount, 2);
            });

        foreach ($out as $id => $m) {
            $out[$id]['due']     = round(max(0, $m['charged'] - $m['paid']), 2);
            $out[$id]['advance'] = round(max(0, $m['paid'] - $m['charged']), 2);
        }

        return $out;
    }

    /** What a school owes for its messages. */
    public static function due(int $orgId): float
    {
        return (float) (self::moneyBy([$orgId])[$orgId]['due'] ?? 0.0);
    }

    // ─── Every school at once ───────────────────────────────────────────────

    /**
     * Every school's messages and money (Super Admin → Messaging → Schools):
     * [org id => ['given', 'sent', 'left', 'month' (each [channel => n]),
     * 'charged', 'paid', 'due', 'pending', 'last_pack']].
     */
    public static function all(?array $orgIds = null): array
    {
        if (!self::tracked()) {
            return [];
        }

        $given      = self::givenBy($orgIds);
        $money      = self::moneyBy($orgIds);
        $monthStart = now()->startOfMonth();
        $sent       = [];
        $month      = [];
        foreach (array_keys(self::CHANNELS) as $ch) {
            $sent[$ch]  = self::sentBy($ch, $orgIds);
            $month[$ch] = self::sentBy($ch, $orgIds, $monthStart, null, false);
        }

        $pending = MessagingPack::pending()
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->groupBy('organization_id')->selectRaw('organization_id, COUNT(*) AS n')->pluck('n', 'organization_id');
        $last = MessagingPack::approved()->where('type', 'give')
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->groupBy('organization_id')->selectRaw('organization_id, MAX(txn_date) AS d')->pluck('d', 'organization_id');

        $ids = collect(array_keys($given))->merge(array_keys($money))->merge($pending->keys())
            ->merge(collect($sent)->flatMap(fn ($r) => array_keys($r)))
            ->merge(collect($month)->flatMap(fn ($r) => array_keys($r)))
            ->map(fn ($id) => (int) $id)->unique();

        $out = [];
        foreach ($ids as $id) {
            $g = $given[$id] ?? self::zero();
            $s = self::zero();
            $m = self::zero();
            foreach (array_keys(self::CHANNELS) as $ch) {
                $s[$ch] = (int) ($sent[$ch][$id] ?? 0);
                $m[$ch] = (int) ($month[$ch][$id] ?? 0);
            }
            $out[$id] = [
                'given'     => $g,
                'sent'      => $s,
                'left'      => array_map(fn ($ch) => $g[$ch] - $s[$ch], array_combine(array_keys(self::CHANNELS), array_keys(self::CHANNELS))),
                'month'     => $m,
                'charged'   => $money[$id]['charged'] ?? 0.0,
                'paid'      => $money[$id]['paid'] ?? 0.0,
                'due'       => $money[$id]['due'] ?? 0.0,
                'pending'   => (int) ($pending[$id] ?? 0),
                'last_pack' => $last[$id] ?? null,
            ];
        }

        return $out;
    }

    /** An empty row for a school with no messages yet. */
    public static function blank(): array
    {
        return ['given' => self::zero(), 'sent' => self::zero(), 'left' => self::zero(), 'month' => self::zero(),
                'charged' => 0.0, 'paid' => 0.0, 'due' => 0.0, 'pending' => 0, 'last_pack' => null];
    }

    // ─── Packs and payments ─────────────────────────────────────────────────

    /**
     * The Super Admin gives a school messages. $payment: 'paid' (the money
     * came — kept as a payment of $amount), 'due' (the school owes $amount) or
     * 'free' (costs nothing).
     */
    public static function give(int $orgId, string $channel, int $messages, float $rate, ?float $amount, string $payment, ?string $mode, ?string $reference, ?string $note, ?int $byUserId, ?string $date = null): MessagingPack
    {
        $free   = $payment === 'free';
        $amount = $free ? 0.0 : round($amount ?? $messages * $rate, 2);
        $date   = $date ?: now()->toDateString();

        $pack = MessagingPack::create([
            'organization_id' => $orgId,
            'channel'         => $channel,
            'type'            => 'give',
            'messages'        => $messages,
            'rate'            => round($rate, 2),
            'amount'          => $amount,
            'status'          => 'approved',
            'source'          => 'super_admin',
            'mode'            => $payment === 'paid' ? ($mode ?: null) : null,
            'reference'       => $payment === 'paid' ? ($reference ?: null) : null,
            'txn_date'        => $date,
            'note'            => $note ?: null,
            'created_by'      => $byUserId,
            'decided_by'      => $byUserId,
            'decided_at'      => now(),
        ]);

        if ($payment === 'paid' && $amount > 0) {
            self::recordPayment($orgId, $amount, $mode, $reference, $date, null, $byUserId, $pack->id);
        }

        return $pack;
    }

    /** The Super Admin takes messages back (a wrong pack, a refund …). */
    public static function take(int $orgId, string $channel, int $messages, ?string $note, ?int $byUserId, ?string $date = null): MessagingPack
    {
        return MessagingPack::create([
            'organization_id' => $orgId,
            'channel'         => $channel,
            'type'            => 'take',
            'messages'        => $messages,
            'rate'            => 0,
            'amount'          => 0,
            'status'          => 'approved',
            'source'          => 'super_admin',
            'txn_date'        => $date ?: now()->toDateString(),
            'note'            => $note ?: null,
            'created_by'      => $byUserId,
            'decided_by'      => $byUserId,
            'decided_at'      => now(),
        ]);
    }

    /** The school buys messages and says it paid: pending until the Super Admin approves it. */
    public static function request(int $orgId, string $channel, int $messages, ?string $mode, ?string $reference, ?string $note, ?int $byUserId, ?string $date = null): MessagingPack
    {
        $rate = self::rate($channel);
        $pack = MessagingPack::create([
            'organization_id' => $orgId,
            'channel'         => $channel,
            'type'            => 'give',
            'messages'        => $messages,
            'rate'            => $rate,
            'amount'          => round($messages * $rate, 2),
            'status'          => 'pending',
            'source'          => 'school',
            'mode'            => $mode ?: null,
            'reference'       => $reference ?: null,
            'txn_date'        => $date ?: now()->toDateString(),
            'note'            => $note ?: null,
            'created_by'      => $byUserId,
        ]);

        try {
            ActivityNotifier::toSuperAdmins(
                'Messaging: messages to approve',
                (Organization::whereKey($orgId)->value('name') ?? 'A school') . ' has bought ' . number_format($messages) . ' '
                    . self::label($channel) . ' messages for ₹' . number_format((float) $pack->amount, 2)
                    . ($mode ? ' by ' . $mode : '') . ($reference ? ' (ref. ' . $reference . ')' : '')
                    . '. Approve it in Messaging → Requests.',
                ['type' => 'messaging_pack', 'id' => $pack->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return $pack;
    }

    /** Approve (its payment is kept) or reject a pack the school bought. */
    public static function decide(MessagingPack $pack, bool $approve, ?int $byUserId, ?string $note = null): void
    {
        if ($pack->status !== 'pending') {
            return;
        }

        $pack->update([
            'status'        => $approve ? 'approved' : 'rejected',
            'decided_by'    => $byUserId,
            'decided_at'    => now(),
            'decision_note' => $note ?: null,
        ]);

        if ($approve && (float) $pack->amount > 0) {
            self::recordPayment((int) $pack->organization_id, (float) $pack->amount, $pack->mode, $pack->reference,
                optional($pack->txn_date)->toDateString(), null, $byUserId, $pack->id);
        }
    }

    public static function recordPayment(int $orgId, float $amount, ?string $mode, ?string $reference, ?string $date, ?string $note, ?int $byUserId, ?int $packId = null): MessagingPayment
    {
        return MessagingPayment::create([
            'organization_id' => $orgId,
            'pack_id'         => $packId,
            'amount'          => round($amount, 2),
            'mode'            => $mode ?: null,
            'reference'       => $reference ?: null,
            'paid_on'         => $date ?: now()->toDateString(),
            'note'            => $note ?: null,
            'created_by'      => $byUserId,
        ]);
    }

    /**
     * How far each charged pack is paid: a school's payments go to its packs
     * oldest first. [pack id => ['paid' => ₹, 'state' => paid|part|due|free]].
     */
    public static function payState(Collection $packs): array
    {
        $orgIds = $packs->pluck('organization_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (!$orgIds) {
            return [];
        }

        $pool = MessagingPayment::whereIn('organization_id', $orgIds)->groupBy('organization_id')
            ->selectRaw('organization_id, SUM(amount) AS amount')->pluck('amount', 'organization_id')
            ->map(fn ($v) => (float) $v)->all();

        $out = [];
        MessagingPack::approved()->where('type', 'give')->whereIn('organization_id', $orgIds)
            ->orderBy('txn_date')->orderBy('id')->get(['id', 'organization_id', 'amount'])
            ->each(function ($p) use (&$pool, &$out) {
                $amount = (float) $p->amount;
                if ($amount <= 0) {
                    $out[$p->id] = ['paid' => 0.0, 'state' => 'free'];
                    return;
                }
                $have = $pool[(int) $p->organization_id] ?? 0.0;
                $paid = round(min($amount, max(0, $have)), 2);
                $pool[(int) $p->organization_id] = $have - $paid;
                $out[$p->id] = ['paid' => $paid, 'state' => $paid + 0.004 >= $amount ? 'paid' : ($paid > 0 ? 'part' : 'due')];
            });

        return $out;
    }

    // ─── Over time ──────────────────────────────────────────────────────────

    /**
     * The last $months months, oldest first: ['labels' => ['Jun', …],
     * 'keys' => ['2026-06', …], 'whatsapp' => [n …], 'sms' => […], 'email' =>
     * […], 'total' => […], 'paid' => [₹ …], 'given' => [messages …]] — every
     * message sent (not only since packs), for the schools in $orgIds.
     */
    public static function monthly(?array $orgIds = null, int $months = 6): array
    {
        $out = ['labels' => [], 'keys' => [], 'total' => [], 'paid' => [], 'given' => []] + array_fill_keys(array_keys(self::CHANNELS), []);
        $start = now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $from = $start->copy()->addMonths($i);
            $to   = $from->copy()->endOfMonth();
            $out['labels'][] = $from->format('M');
            $out['keys'][]   = $from->format('Y-m');
            $total = 0;
            foreach (array_keys(self::CHANNELS) as $ch) {
                $n = array_sum(self::sentBy($ch, $orgIds, $from, $to, false));
                $out[$ch][] = $n;
                $total += $n;
            }
            $out['total'][] = $total;
            $out['paid'][]  = self::tracked()
                ? round((float) MessagingPayment::when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
                    ->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->sum('amount'), 2)
                : 0.0;
            $out['given'][] = self::tracked()
                ? (int) MessagingPack::approved()->where('type', 'give')->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
                    ->whereBetween('txn_date', [$from->toDateString(), $to->toDateString()])->sum('messages')
                : 0;
        }

        return $out;
    }

    /** Failed this month, per channel, for the schools in $orgIds. */
    public static function failedMonth(?array $orgIds = null): array
    {
        $out = self::zero();
        foreach (array_keys(self::CHANNELS) as $ch) {
            $out[$ch] = array_sum(self::sentBy($ch, $orgIds, now()->startOfMonth(), null, false, true));
        }

        return $out;
    }
}
