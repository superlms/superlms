<?php

namespace App\Services;

use App\Models\Messaging\MessagingWalletTransaction;
use App\Models\Organization;
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use App\Models\SuperAdmin\SuperAdminLedgerTransaction;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use App\Support\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * The Super Admin's Ledger, as the school's (LedgerService):
 *
 *   Credits  = fees received from the schools (SuperAdminFeeReceipt — Fees'
 *              Amount Received, Record Payment, Mark Paid …), money schools
 *              paid into their messaging wallets, and credits made by hand.
 *   Expenses = salaries paid (Payroll) and expenses made by hand. A fee
 *              payment made smaller or cleared on Fees reads as a Fee
 *              correction among the expenses.
 *
 * The automatic rows are read live from their own tables, so the Ledger
 * always agrees with Fees, Payroll and Messaging; only the entries made by
 * hand live in super_admin_ledger_transactions, and every one of them can be
 * edited or deleted.
 *
 * A row: ['date', 'time', 'sort_at', 'type' => credit|expense, 'amount',
 * 'source', 'from', 'to', 'mode', 'party', 'reason', 'manual_id', 'school'].
 */
class SuperAdminLedgerService
{
    /** The platform's own side of a row. */
    public const NAME = 'SuperLMS';

    public static function tracked(): bool
    {
        $key = 'superadmin.ledger.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('super_admin_ledger_transactions');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    private static function has(string $table): bool
    {
        $key = 'superadmin.ledger.has.' . $table;
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable($table);
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    // ─── Sums ───────────────────────────────────────────────────────────────

    public static function netBalance(): float
    {
        return round(self::creditSum(null, null) - self::expenseSum(null, null), 2);
    }

    /** Balance carried into $start (everything before it). */
    public static function openingBalance(?Carbon $start): float
    {
        if (!$start) {
            return 0.0;
        }
        $before = $start->copy()->subDay()->endOfDay();

        return round(self::creditSum(null, $before) - self::expenseSum(null, $before), 2);
    }

    public static function creditSum(?Carbon $start, ?Carbon $end): float
    {
        $total = 0.0;

        if (self::has('super_admin_fee_receipts')) {
            $total += (float) self::receiptsScoped($start, $end)->where('amount', '>', 0)->sum('amount');
        }
        if (MessagingWallet::tracked()) {
            $total += (float) self::dateScoped(self::walletQuery(), 'txn_date', $start, $end)->sum('amount');
        }
        if (self::tracked()) {
            $total += (float) self::dateScoped(SuperAdminLedgerTransaction::where('type', 'credit'), 'txn_date', $start, $end)->sum('amount');
        }

        return round($total, 2);
    }

    public static function expenseSum(?Carbon $start, ?Carbon $end): float
    {
        $total = 0.0;

        if (self::has('super_admin_salary_payments')) {
            $total += (float) self::salaryScoped($start, $end)->sum('amount');
        }
        if (self::has('super_admin_fee_receipts')) {
            $total += abs((float) self::receiptsScoped($start, $end)->where('amount', '<', 0)->sum('amount'));
        }
        if (self::tracked()) {
            $total += (float) self::dateScoped(SuperAdminLedgerTransaction::where('type', 'expense'), 'txn_date', $start, $end)->sum('amount');
        }

        return round($total, 2);
    }

    // ─── Rows ───────────────────────────────────────────────────────────────

    /** The statement's rows in a window, oldest first. */
    public static function entries(?Carbon $start, ?Carbon $end): Collection
    {
        $rows    = collect();
        $schools = Organization::pluck('name', 'id');
        $school  = fn ($id) => $schools[$id] ?? 'School';
        $mode    = fn ($m) => $m ? ucwords(str_replace('_', ' ', (string) $m)) : null;

        // ── Fees received from the schools (credit; a correction, expense) ──
        if (self::has('super_admin_fee_receipts')) {
            self::receiptsScoped($start, $end)
                ->where('amount', '!=', 0)
                ->get()
                ->each(function (SuperAdminFeeReceipt $r) use ($rows, $school, $mode) {
                    $in   = (float) $r->amount > 0;
                    $name = $school($r->organization_id);
                    $when = $r->payment_date ?? $r->created_at;
                    $rows->push([
                        'date'      => Carbon::parse($when),
                        'time'      => optional($r->created_at)->format('g:i A'),
                        'sort_at'   => $r->created_at ?? Carbon::parse($when),
                        'type'      => $in ? 'credit' : 'expense',
                        'amount'    => abs((float) $r->amount),
                        'source'    => $in ? 'School Fee' : 'Fee Correction',
                        'from'      => $in ? $name : self::NAME,
                        'to'        => $in ? self::NAME : $name,
                        'mode'      => $mode($r->payment_mode),
                        'party'     => $name,
                        'reason'    => trim(($in ? 'Platform fee' : 'Platform fee corrected')
                            . ($r->label ? ' · ' . $r->label : '')
                            . ($r->academic_year ? ' · ' . $r->academic_year : '')),
                        'receipt'   => $r->receipt_number,
                        'school'    => $name,
                        'manual_id' => null,
                    ]);
                });
        }

        // ── Messaging wallets paid into by the schools (credit) ──
        if (MessagingWallet::tracked()) {
            self::dateScoped(self::walletQuery(), 'txn_date', $start, $end)
                ->get()
                ->each(function (MessagingWalletTransaction $t) use ($rows, $school) {
                    $name = $school($t->organization_id);
                    $rows->push([
                        'date'      => Carbon::parse($t->txn_date),
                        'time'      => optional($t->created_at)->format('g:i A'),
                        'sort_at'   => $t->created_at ?? Carbon::parse($t->txn_date),
                        'type'      => 'credit',
                        'amount'    => (float) $t->amount,
                        'source'    => 'Messaging Wallet',
                        'from'      => $name,
                        'to'        => self::NAME,
                        'mode'      => $t->mode,
                        'party'     => $name,
                        'reason'    => 'Messaging wallet recharge' . ($t->reference ? ' · Ref. ' . $t->reference : ''),
                        'receipt'   => $t->reference,
                        'school'    => $name,
                        'manual_id' => null,
                    ]);
                });
        }

        // ── Salaries paid (expense) ──
        if (self::has('super_admin_salary_payments')) {
            self::salaryScoped($start, $end)
                ->with('employee:id,name')
                ->get()
                ->each(function (SuperAdminSalaryPayment $s) use ($rows, $mode) {
                    $when     = $s->payment_date ?? $s->created_at;
                    $employee = $s->employee->name ?? 'Staff';
                    $rows->push([
                        'date'      => Carbon::parse($when),
                        'time'      => optional($s->created_at)->format('g:i A'),
                        'sort_at'   => $s->created_at ?? Carbon::parse($when),
                        'type'      => 'expense',
                        'amount'    => (float) $s->amount,
                        'source'    => 'Salary',
                        'from'      => self::NAME,
                        'to'        => $employee,
                        'mode'      => $mode($s->payment_mode),
                        'party'     => $employee,
                        'reason'    => 'Salary' . ($s->month ? ' · ' . self::monthLabel($s->month) : ''),
                        'receipt'   => $s->receipt_number,
                        'school'    => null,
                        'manual_id' => null,
                    ]);
                });
        }

        // ── Entries made by hand (credit or expense) ──
        if (self::tracked()) {
            self::dateScoped(SuperAdminLedgerTransaction::query(), 'txn_date', $start, $end)
                ->get()
                ->each(function (SuperAdminLedgerTransaction $m) use ($rows) {
                    $credit = $m->type === 'credit';
                    $rows->push([
                        'date'         => Carbon::parse($m->txn_date),
                        'time'         => optional($m->created_at)->format('g:i A'),
                        'sort_at'      => $m->created_at ?? Carbon::parse($m->txn_date),
                        'type'         => $credit ? 'credit' : 'expense',
                        'amount'       => (float) $m->amount,
                        'source'       => 'Manual',
                        'from'         => $credit ? ($m->party ?: '—') : ($m->party ?: self::NAME),
                        'to'           => $credit ? self::NAME : ($m->party_to ?: '—'),
                        'mode'         => $m->mode,
                        'party'        => $m->party ?: '—',
                        'reason'       => $m->reason ?: 'Manual entry',
                        'receipt'      => null,
                        'school'       => null,
                        'manual_id'    => $m->id,
                        'collected_by' => $credit ? ($m->party_to ?: null) : null,
                    ]);
                });
        }

        return $rows->sortBy([['date', 'asc'], ['sort_at', 'asc']])->values();
    }

    /** The rows with a running balance from the window's opening one. */
    public static function withBalance(Collection $entries, float $opening): Collection
    {
        $balance = $opening;

        return $entries->map(function ($row) use (&$balance) {
            $balance += $row['type'] === 'credit' ? $row['amount'] : -$row['amount'];
            $row['balance'] = round($balance, 2);

            return $row;
        });
    }

    // ─── Statement PDF (the school Ledger's statement, with SuperLMS on top) ──

    /** @return array{0: string, 1: string} the PDF's bytes and its file name */
    public static function statementPdf(?Carbon $start, ?Carbon $end, bool $overall): array
    {
        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $opening = self::openingBalance($start);
        $rows    = self::withBalance(self::entries($start, $end), $opening);
        $credit  = round($rows->where('type', 'credit')->sum('amount'), 2);
        $expense = round($rows->where('type', 'expense')->sum('amount'), 2);

        $logo = public_path('admin-image/SuperLMS logo.png');
        $data = [
            'org'          => (object) ['name' => self::NAME],
            'logoSrc'      => is_file($logo) ? $logo : null,
            'contact'      => ['address' => null, 'email' => config('mail.from.address'), 'mobile' => null, 'website' => parse_url((string) config('app.url'), PHP_URL_HOST)],
            'rows'         => $rows,
            'opening'      => round($opening, 2),
            'closing'      => round($opening + $credit - $expense, 2),
            'totalCredit'  => $credit,
            'totalExpense' => $expense,
            'start'        => $start,
            'end'          => $end,
            'overall'      => $overall,
            'generatedAt'  => now(),
            'netBalance'   => self::netBalance(),
            'footNote'     => 'Deposits include platform fees received from the schools, messaging wallet recharges and manual credit entries. '
                . 'Withdrawals include paid staff salaries, fee corrections and manual expense entries.',
        ];

        $fontCache = storage_path('fonts');
        if (!is_dir($fontCache)) {
            @mkdir($fontCache, 0775, true);
        }
        $load = fn (string $fontCss) => Pdf::loadView('admin.ledger-statement', $data + compact('fontCss'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 130)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontCache)
            ->setOption('fontCache', $fontCache)
            ->setOption('defaultFont', 'DejaVu Sans');

        try {
            $pdf = $load(PdfFonts::faceCss());
        } catch (\Throwable $e) {
            $pdf = $load('');
        }

        $name = $overall || !$start || !$end
            ? 'superlms_ledger_all_' . now()->format('Ymd') . '.pdf'
            : 'superlms_ledger_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.pdf';

        return [$pdf->output(), $name];
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Fees received, windowed on the day the money came (the day it was saved when none). */
    private static function receiptsScoped(?Carbon $start, ?Carbon $end)
    {
        $q = SuperAdminFeeReceipt::query();
        if ($start) {
            $q->whereRaw('DATE(COALESCE(payment_date, created_at)) >= ?', [$start->toDateString()]);
        }
        if ($end) {
            $q->whereRaw('DATE(COALESCE(payment_date, created_at)) <= ?', [$end->toDateString()]);
        }

        return $q;
    }

    /** Salaries actually paid, windowed on the day paid (the day saved when none). */
    private static function salaryScoped(?Carbon $start, ?Carbon $end)
    {
        $q = SuperAdminSalaryPayment::where('status', 'paid');
        if ($start) {
            $q->whereRaw('DATE(COALESCE(payment_date, created_at)) >= ?', [$start->toDateString()]);
        }
        if ($end) {
            $q->whereRaw('DATE(COALESCE(payment_date, created_at)) <= ?', [$end->toDateString()]);
        }

        return $q;
    }

    /** Money schools paid into their messaging wallets (approved, and received by the platform). */
    private static function walletQuery()
    {
        return MessagingWalletTransaction::where('type', 'credit')
            ->where('status', 'approved')
            ->where('in_ledger', true);
    }

    private static function dateScoped($query, string $column, ?Carbon $start, ?Carbon $end)
    {
        if ($start) {
            $query->whereDate($column, '>=', $start->toDateString());
        }
        if ($end) {
            $query->whereDate($column, '<=', $end->toDateString());
        }

        return $query;
    }

    /** "2026-10" → "Oct 2026"; anything else as it is. */
    private static function monthLabel(string $month): string
    {
        try {
            return preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::createFromFormat('Y-m-d', $month . '-01')->format('M Y') : $month;
        } catch (\Throwable $e) {
            return $month;
        }
    }
}
