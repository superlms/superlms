<?php

namespace App\Services;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\TransportFeePayment;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for the school Ledger.
 *
 * Credits  = fee payments (academic + transport + admission) + manual credits.
 * Expenses = paid staff salaries + manual expense entries.
 *
 * Automatic rows are read LIVE from their own tables (fee_payments,
 * transport_fee_payments, admission_enquiries, admin_salary_payments) and only
 * manual adjustments live in ledger_transactions — so nothing is ever double
 * counted and the numbers always reconcile with the Fee and Payroll modules.
 *
 * Each statement row is shaped like a bank line:
 *   ['date'=>Carbon, 'time'=>?string, 'sort_at'=>Carbon, 'type'=>'credit'|'expense',
 *    'amount'=>float, 'source'=>string, 'from'=>string, 'to'=>string,
 *    'mode'=>?string, 'party'=>string, 'reason'=>string, 'manual_id'=>?int]
 */
class LedgerService
{
    /** @var array<int,string> cache of org name by id, for From/To labels. */
    protected static array $orgNames = [];

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

        if (self::hasAdmissionFees()) {
            $total += (float) self::admissionQuery($orgId, $start, $end)->sum('collected_amount');
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
     * Detailed, date-ordered statement rows for a window (oldest first).
     */
    public static function entries(int $orgId, ?Carbon $start, ?Carbon $end): Collection
    {
        $rows   = collect();
        $school = self::orgName($orgId);

        // ── Fee payments (credit) ──────────────────────────────────────────
        self::dateScoped(FeePayment::where('organization_id', $orgId), 'payment_date', $start, $end)
            ->with('studentDetail:id,full_name')
            ->orderBy('payment_date')
            ->get()
            ->each(function ($p) use ($rows, $school) {
                $penalty = (float) ($p->penalty_amount ?? 0);
                $label   = ucfirst((string) $p->fee_type) . ' Fee';
                $student = $p->studentDetail->full_name ?? ($p->submitted_by ?: 'Student');
                $rows->push([
                    'date'    => Carbon::parse($p->payment_date),
                    'time'    => optional($p->created_at)->format('g:i A'),
                    'sort_at' => $p->created_at ?? Carbon::parse($p->payment_date),
                    'type'    => 'credit',
                    'amount'  => (float) $p->amount,
                    'source'  => $label,
                    'from'    => $student,
                    'to'      => $school,
                    'mode'    => $p->payment_mode,
                    'party'   => $student,
                    'reason'  => trim($label . ' collection'
                        . ($penalty > 0 ? ' (incl. penalty Rs. ' . number_format($penalty, 2) . ')' : '')
                        . ($p->receipt_number ? ' · ' . $p->receipt_number : '')),
                ]);
            });

        // ── Transport fee payments (credit) ────────────────────────────────
        if (Schema::hasTable('transport_fee_payments')) {
            self::dateScoped(TransportFeePayment::where('organization_id', $orgId), 'payment_date', $start, $end)
                ->with('studentDetail:id,full_name')
                ->orderBy('payment_date')
                ->get()
                ->each(function ($p) use ($rows, $school) {
                    $student = $p->studentDetail->full_name ?? 'Student';
                    $rows->push([
                        'date'    => Carbon::parse($p->payment_date),
                        'time'    => optional($p->created_at)->format('g:i A'),
                        'sort_at' => $p->created_at ?? Carbon::parse($p->payment_date),
                        'type'    => 'credit',
                        'amount'  => (float) $p->amount,
                        'source'  => 'Transport Fee',
                        'from'    => $student,
                        'to'      => $school,
                        'mode'    => $p->payment_mode,
                        'party'   => $student,
                        'reason'  => 'Transport fee collection'
                            . ($p->receipt_number ? ' · ' . $p->receipt_number : ''),
                    ]);
                });
        }

        // ── Admission fees (credit) ────────────────────────────────────────
        if (self::hasAdmissionFees()) {
            self::admissionQuery($orgId, $start, $end)->get()->each(function ($a) use ($rows, $school) {
                $when = $a->fee_collected_at ?: $a->created_at;
                $rows->push([
                    'date'    => Carbon::parse($when),
                    'time'    => optional($a->created_at)->format('g:i A'),
                    'sort_at' => $a->created_at ?? Carbon::parse($when),
                    'type'    => 'credit',
                    'amount'  => (float) $a->collected_amount,
                    'source'  => 'Admission Fee',
                    'from'    => $a->student_name ?: 'Applicant',
                    'to'      => $school,
                    'mode'    => $a->payment_mode,
                    'party'   => $a->student_name ?: 'Applicant',
                    'reason'  => 'Admission fee collection'
                        . ($a->student_name ? ' · ' . $a->student_name : ''),
                ]);
            });
        }

        // ── Salaries (expense) ─────────────────────────────────────────────
        $salaryQ = AdminSalaryPayment::with('employee:id,name')
            ->where('organization_id', $orgId)
            ->where('status', 'paid');
        self::salaryDateScoped($salaryQ, $start, $end)
            ->get()
            ->each(function ($s) use ($rows, $school) {
                $when     = $s->payment_date ?: $s->created_at;
                $employee = $s->employee->name ?? 'Staff';
                $rows->push([
                    'date'    => Carbon::parse($when),
                    'time'    => optional($s->created_at)->format('g:i A'),
                    'sort_at' => $s->created_at ?? Carbon::parse($when),
                    'type'    => 'expense',
                    'amount'  => (float) $s->amount,
                    'source'  => 'Salary',
                    'from'    => $school,
                    'to'      => $employee,
                    'mode'    => $s->payment_mode,
                    'party'   => $employee,
                    'reason'  => 'Salary' . ($s->month ? ' · ' . $s->month : ''),
                ]);
            });

        // ── Manual entries (credit or expense) ─────────────────────────────
        self::dateScoped(LedgerTransaction::where('organization_id', $orgId), 'txn_date', $start, $end)
            ->orderBy('txn_date')
            ->get()
            ->each(function ($m) use ($rows, $school) {
                if ($m->type === 'credit') {
                    $from = $m->party ?: '—';
                    $to   = $school;
                } else {
                    $from = $m->party ?: $school;
                    $to   = $m->party_to ?: '—';
                }
                $rows->push([
                    'date'      => Carbon::parse($m->txn_date),
                    'time'      => optional($m->created_at)->format('g:i A'),
                    'sort_at'   => $m->created_at ?? Carbon::parse($m->txn_date),
                    'type'      => $m->type,
                    'amount'    => (float) $m->amount,
                    'source'    => 'Manual',
                    'from'      => $from,
                    'to'        => $to,
                    'mode'      => $m->mode,
                    'party'     => $m->party ?: '—',
                    'reason'    => $m->reason ?: 'Manual entry',
                    'manual_id' => $m->id,
                    // For credits, party_to carries the "Collected by" staff name.
                    'collected_by' => $m->type === 'credit' ? ($m->party_to ?: null) : null,
                ]);
            });

        // Oldest first so a running balance reads top-to-bottom like a bank
        // statement. Tie-break on the created timestamp keeps same-day rows in
        // the order they were actually recorded.
        return $rows->sortBy([
            ['date', 'asc'],
            ['sort_at', 'asc'],
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

    /** Admission-fee credits: only rows that actually collected money. */
    protected static function admissionQuery(int $orgId, ?Carbon $start, ?Carbon $end)
    {
        $q = AdmissionEnquiry::where('organization_id', $orgId)
            ->whereNotNull('collected_amount')
            ->where('collected_amount', '>', 0);

        // Window on the collection date, falling back to created_at.
        if ($start) {
            $q->whereRaw('DATE(COALESCE(fee_collected_at, created_at)) >= ?', [$start->toDateString()]);
        }
        if ($end) {
            $q->whereRaw('DATE(COALESCE(fee_collected_at, created_at)) <= ?', [$end->toDateString()]);
        }
        return $q;
    }

    /** Guard against schema drift — the collected_* columns are lms:migrate-only. */
    protected static function hasAdmissionFees(): bool
    {
        return Schema::hasTable('admission_enquiries')
            && Schema::hasColumn('admission_enquiries', 'collected_amount');
    }

    /** School name for the From/To labels, cached per request. */
    protected static function orgName(int $orgId): string
    {
        if (!array_key_exists($orgId, self::$orgNames)) {
            self::$orgNames[$orgId] = Organization::whereKey($orgId)->value('name') ?: 'School';
        }
        return self::$orgNames[$orgId];
    }
}
