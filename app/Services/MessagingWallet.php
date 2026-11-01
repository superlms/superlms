<?php

namespace App\Services;

use App\Models\Messaging\EmailMessage;
use App\Models\Messaging\MessagingWalletTransaction;
use App\Models\Organization;
use App\Models\WhatsAppMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A school's messaging wallet: the money it has for the messages it is
 * charged for — WhatsApp (absent alert, fee received, Send Credentials, a
 * notice to many) and email.
 *
 *   balance = money put in (approved credits) − money taken out (debits)
 *             − what its messages came to since the wallet started
 *
 * What the messages came to is read live from their own rows (a WhatsApp
 * message not delivered is not charged, so it gives its money back by
 * itself); nothing is copied. Money goes in by the Super Admin (Messaging →
 * Wallets → Add funds) or by the school (More → Messaging → Add funds, a
 * request the Super Admin approves). With Prepaid on, a charged message is
 * not sent when the wallet cannot pay for it.
 */
class MessagingWallet
{
    /** The rates a school pays, until the Super Admin sets them (Messaging → Settings). */
    public const DEFAULT_RATES = ['whatsapp' => 0.66, 'email' => 0.10];

    /** Ways money comes in. */
    public const MODES = ['UPI', 'Bank Transfer', 'Cash', 'Cheque', 'Card', 'Free credit', 'Other'];

    // ─── Set-up ─────────────────────────────────────────────────────────────

    /** Whether the tables are there yet (the migration of 1 Nov 2026) — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'messaging_wallet.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('messaging_wallet_transactions') && Schema::hasTable('messaging_settings');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Every setting, read once a request. */
    private static function settings(): array
    {
        $key = 'messaging_wallet.settings';
        if (!app()->bound($key)) {
            $values = [];
            if (self::tracked()) {
                try {
                    $values = DB::table('messaging_settings')->pluck('value', 'key')->all();
                } catch (\Throwable $e) {
                    $values = [];
                }
            }
            app()->instance($key, $values);
        }

        return (array) app($key);
    }

    public static function setting(string $name, ?string $default = null): ?string
    {
        $value = self::settings()[$name] ?? null;

        return $value === null || $value === '' ? $default : (string) $value;
    }

    public static function setSetting(string $name, ?string $value): void
    {
        if (!self::tracked()) {
            return;
        }

        DB::table('messaging_settings')->updateOrInsert(
            ['key' => $name],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
        );
        app()->forgetInstance('messaging_wallet.settings');
    }

    /** What one charged WhatsApp message costs a school. */
    public static function whatsappRate(): float
    {
        return round(max(0, (float) self::setting('whatsapp_rate', (string) self::DEFAULT_RATES['whatsapp'])), 2);
    }

    /** What one email costs a school. */
    public static function emailRate(): float
    {
        return round(max(0, (float) self::setting('email_rate', (string) self::DEFAULT_RATES['email'])), 2);
    }

    /** Prepaid: a charged message goes only when the wallet can pay for it. Off until turned on. */
    public static function prepaid(): bool
    {
        return self::setting('prepaid', '0') === '1';
    }

    /** What schools see on Add funds: where to pay (UPI id, bank account …). */
    public static function paymentDetails(): string
    {
        return (string) self::setting('payment_details', '');
    }

    /** Messages are paid from the wallet from this moment (the day Messaging came). */
    public static function since(): Carbon
    {
        $since = self::setting('since');

        try {
            return $since ? Carbon::parse($since) : Carbon::create(2026, 11, 1);
        } catch (\Throwable $e) {
            return Carbon::create(2026, 11, 1);
        }
    }

    // ─── What a school has ──────────────────────────────────────────────────

    /** Money put in less money taken out (approved only). */
    public static function funded(int $orgId): float
    {
        if (!self::tracked()) {
            return 0.0;
        }

        $row = MessagingWalletTransaction::where('organization_id', $orgId)->approved()
            ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) AS cr, SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) AS dr")
            ->first();

        return round((float) ($row->cr ?? 0) - (float) ($row->dr ?? 0), 2);
    }

    /**
     * What a school's messages came to between two moments (from the wallet's
     * start at the earliest): ['whatsapp' => ₹, 'whatsapp_count' => n,
     * 'email' => ₹, 'email_count' => n, 'total' => ₹].
     */
    public static function usage(int $orgId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from = $from && $from->gt(self::since()) ? $from : self::since();
        $out  = ['whatsapp' => 0.0, 'whatsapp_count' => 0, 'email' => 0.0, 'email_count' => 0, 'total' => 0.0];

        if ($to && $to->lt($from)) {
            return $out;
        }

        if (SchoolWhatsApp::tracked()) {
            $wa = WhatsAppMessage::where('organization_id', $orgId)
                ->where('billable', true)
                // A message on its way is counted until it fails, so the wallet is never spent twice.
                ->where('status', '!=', 'failed')
                ->where('created_at', '>=', $from)
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->selectRaw('COUNT(*) AS n, SUM(rate) AS amount')
                ->first();
            $out['whatsapp']       = round((float) ($wa->amount ?? 0), 2);
            $out['whatsapp_count'] = (int) ($wa->n ?? 0);
        }

        if (SchoolEmail::tracked()) {
            $em = EmailMessage::where('organization_id', $orgId)
                ->where('billable', true)
                ->where('status', '!=', 'failed')
                ->where('created_at', '>=', $from)
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->selectRaw('COUNT(*) AS n, SUM(rate) AS amount')
                ->first();
            $out['email']       = round((float) ($em->amount ?? 0), 2);
            $out['email_count'] = (int) ($em->n ?? 0);
        }

        $out['total'] = round($out['whatsapp'] + $out['email'], 2);

        return $out;
    }

    /** What the school has left to send with. */
    public static function balance(int $orgId): float
    {
        return round(self::funded($orgId) - self::usage($orgId)['total'], 2);
    }

    /** Whether a charged message of $amount may go: always, unless Prepaid is on and the wallet is short. */
    public static function canSpend(?int $orgId, float $amount): bool
    {
        if (!$orgId || $amount <= 0 || !self::tracked() || !self::prepaid()) {
            return true;
        }

        return self::balance($orgId) + 0.0001 >= $amount;
    }

    // ─── Money in and out ───────────────────────────────────────────────────

    /**
     * The Super Admin puts money in (or takes it out). $inLedger: money the
     * platform received from the school — a credit in the Super Admin's Ledger.
     */
    public static function adjust(int $orgId, string $type, float $amount, ?string $mode, ?string $reference, ?string $note, ?int $byUserId, bool $inLedger = false, ?string $date = null): MessagingWalletTransaction
    {
        $type = $type === 'debit' ? 'debit' : 'credit';

        return MessagingWalletTransaction::create([
            'organization_id' => $orgId,
            'type'            => $type,
            'amount'          => round($amount, 2),
            'status'          => 'approved',
            'source'          => 'super_admin',
            'mode'            => $mode ?: null,
            'reference'       => $reference ?: null,
            'note'            => $note ?: null,
            'in_ledger'       => $type === 'credit' && $inLedger,
            'txn_date'        => $date ?: now()->toDateString(),
            'created_by'      => $byUserId,
            'decided_by'      => $byUserId,
            'decided_at'      => now(),
        ]);
    }

    /** The school asks for money it paid to be put in: pending until the Super Admin approves it. */
    public static function request(int $orgId, float $amount, ?string $mode, ?string $reference, ?string $note, ?int $byUserId, ?string $date = null): MessagingWalletTransaction
    {
        $txn = MessagingWalletTransaction::create([
            'organization_id' => $orgId,
            'type'            => 'credit',
            'amount'          => round($amount, 2),
            'status'          => 'pending',
            'source'          => 'school',
            'mode'            => $mode ?: null,
            'reference'       => $reference ?: null,
            'note'            => $note ?: null,
            'in_ledger'       => true,
            'txn_date'        => $date ?: now()->toDateString(),
            'created_by'      => $byUserId,
        ]);

        try {
            ActivityNotifier::toSuperAdmins(
                'Messaging wallet: funds to approve',
                (Organization::whereKey($orgId)->value('name') ?? 'A school') . ' has added ₹' . number_format($amount, 2)
                    . ($mode ? ' by ' . $mode : '') . ($reference ? ' (ref. ' . $reference . ')' : '')
                    . ' to its messaging wallet. Approve it in Messaging → Requests.',
                ['type' => 'messaging_wallet', 'id' => $txn->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return $txn;
    }

    /** Approve or reject a school's request; the amount can be corrected on approval. */
    public static function decide(MessagingWalletTransaction $txn, bool $approve, ?int $byUserId, ?string $note = null, ?float $amount = null): void
    {
        if ($txn->status !== 'pending') {
            return;
        }

        $values = [
            'status'        => $approve ? 'approved' : 'rejected',
            'decided_by'    => $byUserId,
            'decided_at'    => now(),
            'decision_note' => $note ?: null,
        ];
        if ($approve && $amount !== null && $amount > 0) {
            $values['amount'] = round($amount, 2);
        }
        $txn->update($values);
    }

    // ─── Lists ──────────────────────────────────────────────────────────────

    /**
     * Every school's wallet at once (Super Admin → Messaging → Wallets):
     * [org id => ['funded', 'used', 'balance', 'used_month', 'topped_month', 'pending', 'last_topup']].
     */
    public static function all(?array $orgIds = null): array
    {
        if (!self::tracked()) {
            return [];
        }

        $since      = self::since();
        $monthStart = now()->startOfMonth();
        $monthFrom  = $monthStart->gt($since) ? $monthStart : $since;
        $out        = [];
        $slot       = function ($id) use (&$out) {
            $out[$id] ??= ['funded' => 0.0, 'used' => 0.0, 'balance' => 0.0, 'used_month' => 0.0,
                           'topped_month' => 0.0, 'pending' => 0, 'last_topup' => null];
        };

        $txns = MessagingWalletTransaction::query()
            ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
            ->selectRaw("organization_id, status, type, SUM(amount) AS amount, COUNT(*) AS n, MAX(txn_date) AS last_on,
                SUM(CASE WHEN txn_date >= ? THEN amount ELSE 0 END) AS month_amount", [$monthStart->toDateString()])
            ->groupBy('organization_id', 'status', 'type')
            ->get();
        foreach ($txns as $t) {
            $id = (int) $t->organization_id;
            $slot($id);
            if ($t->status === 'pending') {
                $out[$id]['pending'] += (int) $t->n;
                continue;
            }
            if ($t->status !== 'approved') {
                continue;
            }
            if ($t->type === 'credit') {
                $out[$id]['funded']       += (float) $t->amount;
                $out[$id]['topped_month'] += (float) $t->month_amount;
                $out[$id]['last_topup']    = max((string) $out[$id]['last_topup'], (string) $t->last_on) ?: null;
            } else {
                $out[$id]['funded'] -= (float) $t->amount;
            }
        }

        $usage = function ($query, string $statusSkip) use ($orgIds, $since, $monthFrom) {
            return $query
                ->when($orgIds !== null, fn ($q) => $q->whereIn('organization_id', $orgIds ?: [0]))
                ->whereNotNull('organization_id')
                ->where('billable', true)
                ->where('status', '!=', $statusSkip)
                ->where('created_at', '>=', $since)
                ->selectRaw('organization_id, SUM(rate) AS amount, SUM(CASE WHEN created_at >= ? THEN rate ELSE 0 END) AS month_amount', [$monthFrom])
                ->groupBy('organization_id')
                ->get();
        };
        $rows = collect();
        if (SchoolWhatsApp::tracked()) {
            $rows = $rows->concat($usage(WhatsAppMessage::query(), 'failed'));
        }
        if (SchoolEmail::tracked()) {
            $rows = $rows->concat($usage(EmailMessage::query(), 'failed'));
        }
        foreach ($rows as $u) {
            $id = (int) $u->organization_id;
            $slot($id);
            $out[$id]['used']       += (float) $u->amount;
            $out[$id]['used_month'] += (float) $u->month_amount;
        }

        foreach ($out as $id => $w) {
            foreach (['funded', 'used', 'used_month', 'topped_month'] as $k) {
                $out[$id][$k] = round($w[$k], 2);
            }
            $out[$id]['balance'] = round($w['funded'] - $w['used'], 2);
        }

        return $out;
    }

    /**
     * A school's wallet as one list, newest first: the money in and out, and
     * each month's messages as one line. Rows: ['date', 'kind', 'label', 'detail',
     * 'amount' (+ in / − out), 'status', 'by', 'id'].
     */
    public static function statement(int $orgId, int $limit = 100): array
    {
        if (!self::tracked()) {
            return [];
        }

        $rows = MessagingWalletTransaction::where('organization_id', $orgId)
            ->with('creator:id,name')
            ->latest('txn_date')->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (MessagingWalletTransaction $t) => [
                'id'     => $t->id,
                'date'   => $t->txn_date ?? $t->created_at,
                'at'     => $t->created_at,
                'kind'   => $t->type,
                'label'  => $t->type === 'debit'
                    ? 'Taken out by SuperLMS'
                    : ($t->source === 'school' ? 'Added by the school' : 'Added by SuperLMS'),
                'detail' => trim(implode(' · ', array_filter([$t->mode, $t->reference ? 'Ref. ' . $t->reference : null, $t->note]))),
                'amount' => $t->type === 'debit' ? -(float) $t->amount : (float) $t->amount,
                'status' => $t->status,
                'by'     => $t->creator?->name,
                'reason' => $t->decision_note,
            ])->all();

        // Each month's messages, since the wallet started.
        $month = self::since()->copy()->startOfMonth();
        while ($month->lte(now())) {
            $from = $month->copy()->max(self::since());
            $to   = $month->copy()->endOfMonth();
            $u    = self::usage($orgId, $from, $to);
            if ($u['whatsapp_count'] + $u['email_count'] > 0) {
                $rows[] = [
                    'id'     => 'usage-' . $month->format('Y-m'),
                    'date'   => $to->lt(now()) ? $to : now(),
                    'at'     => null,
                    'kind'   => 'usage',
                    'label'  => 'Messages · ' . $month->format('M Y'),
                    'detail' => trim(implode(' · ', array_filter([
                        $u['whatsapp_count'] ? number_format($u['whatsapp_count']) . ' WhatsApp' : null,
                        $u['email_count'] ? number_format($u['email_count']) . ' email' : null,
                    ]))),
                    'amount' => -$u['total'],
                    'status' => 'approved',
                    'by'     => null,
                    'reason' => null,
                ];
            }
            $month->addMonth();
        }

        usort($rows, fn ($a, $b) => [Carbon::parse($b['date'])->toDateString(), (string) ($b['at'] ?? '')]
            <=> [Carbon::parse($a['date'])->toDateString(), (string) ($a['at'] ?? '')]);

        return $rows;
    }
}
