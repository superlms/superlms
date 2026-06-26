<?php

namespace App\Services;

use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\TransportFeePayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for the school Ledger.
 *
 * Credits  = fee payments (academic + transport) + manual credit entries.
 * Expenses = paid staff salaries + manual expense entries.
 *
 * Automatic rows are read LIVE from their own tables (fee_payments,
 * transport_fee_payments, admin_salary_payments) and only manual adjustments
 * live in ledger_transactions — so nothing is ever double counted and the
 * numbers always reconcile with the Fee and Payroll modules.
 */
class LedgerService
{
    /** Net balance across all time = total credits − total expenses. */
    public static function netBalance(int $orgId): float
    {
        return round(self::creditSum($orgId, null, null) - self::expenseSum($orgId, null, null), 2);
    }

    /** Balance carried forward into $start (everything strictly before it). */
    public static function openingBalance(int $orgId, ?Carbon $start): float
    {
        if (!$start) return 0.0;
        $before = $start->copy()->subDay()->endOfDay();
        return round(self::creditSum($orgId, null, $before) - self::expenseSum($orgId, null, $before), 2);
    }

    /** Sum of all credits within an (optional) inclusive date window. */
    public static function creditSum(int $orgId, ?Carbon $start, ?Carbon $end): float
    {
        $total = 0.0;

        $total += (float) self::dateScoped(
            FeePayment::where('organization_id', $orgId), 'payment_date', $start, $end
        )->sum('amount');

        if (Schema::hasTable('transport_fee_payments')) {
            $total += (float) self::dateScoped(
                TransportFeePayment::where('organization_id', $orgId), 'payment_date', $start, $end
            )->sum('amount');
        }

        $total += (float) self::dateScoped(
            LedgerTransaction::where('organization_id', $orgId)->where('type', 'credit'), 'txn_date', $start, $end
        )->sum('amount');

        return round($total, 2);
    }

    /** Sum of all expenses within an (optional) inclusive date window. */
    public static function expenseSum(int $orgId, ?Carbon $start, ?Carbon $end): float
    {
        $total = 0.0;

        // Salaries: only those actually paid. payment_date can be null on older
        // rows, so fall back to the created date for windowing.
        $salary = AdminSalaryPayment::where('organization_id', $orgId)->where('status', 'paid');
        $salary = self::salaryDateScoped($salary, $start, $end);
        $total += (float) $salary->sum('amount');

        $total += (float) self::dateScoped(
            LedgerTransaction::where('organization_id', $orgId)->where('type', 'expense'), 'txn_date', $start, $end
        )->sum('amount');

        return round($total, 2);
    }

    /**
     * Detailed, date-ordered statement rows for a window. Each row:
     *   ['date' => Carbon, 'type' => 'credit'|'expense', 'amount' => float,
     *    'source' => string, 'party' => string, 'reason' => string]
     */
    public static function entries(int $orgId, ?Carbon $start, ?Carbon $end): Collection
    {
        $rows = collect();

        // ── Fee payments (credit) ──────────────────────────────────────────
        self::dateScoped(FeePayment::where('organization_id', $orgId), 'payment_date', $start, $end)
            ->orderBy('payment_date')
            ->get(['amount', 'payment_date', 'fee_type', 'payment_mode', 'submitted_by', 'receipt_number'])
            ->each(function ($p) use ($rows) {
                $rows->push([
                    'date'   => Carbon::parse($p->payment_date),
                    'type'   => 'credit',
                    'amount' => (float) $p->amount,
                    'source' => ucfirst((string) $p->fee_type) . ' Fee',
                    'party'  => $p->submitted_by ?: '—',
                    'reason' => trim(ucfirst((string) $p->fee_type) . ' fee collection'
                        . ($p->receipt_number ? ' · ' . $p->receipt_number : '')),
                ]);
            });

        // ── Transport fee payments (credit) ────────────────────────────────
        if (Schema::hasTable('transport_fee_payments')) {
            self::dateScoped(TransportFeePayment::where('organization_id', $orgId), 'payment_date', $start, $end)
                ->orderBy('payment_date')
                ->get(['amount', 'payment_date', 'payment_mode', 'receipt_number'])
                ->each(function ($p) use ($rows) {
                    $rows->push([
                        'date'   => Carbon::parse($p->payment_date),
                        'type'   => 'credit',
                        'amount' => (float) $p->amount,
                        'source' => 'Transport Fee',
                        'party'  => '—',
                        'reason' => 'Transport fee collection'
                            . ($p->receipt_number ? ' · ' . $p->receipt_number : ''),
                    ]);
                });
        }

        // ── Salaries (expense) ─────────────────────────────────────────────
        $salaryQ = AdminSalaryPayment::with('employee:id,name')
            ->where('organization_id', $orgId)
            ->where('status', 'paid');
        self::salaryDateScoped($salaryQ, $start, $end)
            ->get()
            ->each(function ($s) use ($rows) {
                $when = $s->payment_date ?: $s->created_at;
                $rows->push([
                    'date'   => Carbon::parse($when),
                    'type'   => 'expense',
                    'amount' => (float) $s->amount,
                    'source' => 'Salary',
                    'party'  => $s->employee->name ?? 'Staff',
                    'reason' => 'Salary' . ($s->month ? ' · ' . $s->month : ''),
                ]);
            });

        // ── Manual entries (credit or expense) ─────────────────────────────
        self::dateScoped(LedgerTransaction::where('organization_id', $orgId), 'txn_date', $start, $end)
            ->orderBy('txn_date')
            ->get()
            ->each(function ($m) use ($rows) {
                $rows->push([
                    'date'   => Carbon::parse($m->txn_date),
                    'type'   => $m->type,
                    'amount' => (float) $m->amount,
                    'source' => 'Manual',
                    'party'  => $m->party ?: '—',
                    'reason' => $m->reason ?: 'Manual entry',
                    'manual_id' => $m->id,
                ]);
            });

        // Oldest first so a running balance reads top-to-bottom like a bank
        // statement. Stable tie-break keeps same-day rows in a sensible order.
        return $rows->sortBy([
            ['date', 'asc'],
        ])->values();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Apply an inclusive date window on a plain date column. */
    protected static function dateScoped($query, string $column, ?Carbon $start, ?Carbon $end)
    {
        if ($start) $query->whereDate($column, '>=', $start->toDateString());
        if ($end)   $query->whereDate($column, '<=', $end->toDateString());
        return $query;
    }

    /** Salary window using COALESCE(payment_date, created_at). */
    protected static function salaryDateScoped($query, ?Carbon $start, ?Carbon $end)
    {
        if ($start) {
            $query->whereRaw('DATE(COALESCE(payment_date, created_at)) >= ?', [$start->toDateString()]);
        }
        if ($end) {
            $query->whereRaw('DATE(COALESCE(payment_date, created_at)) <= ?', [$end->toDateString()]);
        }
        return $query;
    }
}
