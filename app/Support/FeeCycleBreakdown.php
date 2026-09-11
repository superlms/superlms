<?php

namespace App\Support;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeePayment;

/**
 * A student's fee split the way their school's fee cycle defines it, with what
 * they have actually paid allocated across the installments oldest-first.
 *
 * Lives out here rather than in a Livewire component because the fee receipt
 * needs the same breakdown the Fee Submission screen shows — one rule, two
 * readers, no chance of the printed slip disagreeing with the screen.
 */
class FeeCycleBreakdown
{
    /**
     * @param  int    $orgId
     * @param  int    $studentId  whose penalty waivers/payments net the accrued penalty down
     * @param  array  $paid    ['academic' => float, 'transport' => float]
     * @param  array  $totals  ['academic' => float, 'transport' => float] — net of concession
     * @return array<int, array{fee_type:string,label:string,year:string,total:float,paid:float,paid_count:int,count:int,installments:array,penalty_total:float,penalty_waived:float,penalty_paid:float,penalty_net:float}>
     */
    public static function build(int $orgId, int $studentId, array $paid, array $totals): array
    {
        $out = [];

        // Penalty waivers (each pinned to one installment, via the Penalties
        // tab's Waiver panel) and penalty payments (one shared pool — Fee
        // Submission's "Penalties" type has no installment picker, so it's
        // applied oldest installment first, academic side before transport)
        // net the raw accrued penalty down to what is actually still owed.
        $penaltyConcessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->where('is_penalty', true)
            ->get();

        $penaltyPaidPool = (float) FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->where('fee_type', 'penalty')
            ->sum('amount');

        foreach (['academic', 'transport'] as $type) {
            $total = (float) ($totals[$type] ?? 0);
            if ($total <= 0) {
                continue;
            }

            $cycles = FeeCycle::where('organization_id', $orgId)
                ->where('fee_type', $type)
                ->where('is_active', true)
                ->get();

            if ($cycles->isEmpty()) {
                continue;
            }

            // Use the latest academic year that actually has installments.
            $year   = $cycles->max('academic_year');
            $cycles = $cycles->where('academic_year', $year)
                ->sortBy('payment_serial')
                ->values();
            if ($cycles->isEmpty()) {
                continue;
            }

            // The token fee (if any) is a fixed up-front charge, not a % of the
            // fee — it comes off the top before the % installments split the rest.
            $tokenAmt      = (float) optional($cycles->firstWhere('is_token', true))->amount;
            $remainingBase = max(0, $total - $tokenAmt);

            $remaining    = (float) ($paid[$type] ?? 0);
            $installments = [];

            foreach ($cycles as $c) {
                $amount    = $c->is_token
                    ? (float) $c->amount
                    : round(((float) $c->fee_percent / 100) * $remainingBase, 2);
                $covered   = min($remaining, $amount);
                $remaining = max(0, $remaining - $covered);

                $status = $amount <= 0
                    ? 'na'
                    : ($covered >= $amount - 0.01
                        ? 'paid'
                        : ($covered > 0 ? 'partial' : 'pending'));

                $due = $c->due_date;

                // "Apr–Jun (26)" — the installment's own billing period, short
                // month names with the start year's last two digits, rather
                // than just the month its due date happens to fall in.
                $periodLabel = ($c->start_date && $c->end_date)
                    ? $c->start_date->format('M') . '–' . $c->end_date->format('M') . ' (' . $c->start_date->format('y') . ')'
                    : null;

                $overdue = $due && $status !== 'paid' && $due->isPast();

                // Penalty accrued so far on what is still outstanding — days
                // past due times the installment's own per-day rate. Only the
                // unpaid slice of the installment carries it, so a partial
                // payment that already cleared it stops accruing more.
                $daysLate     = $overdue ? $due->diffInDays(now()->startOfDay()) : 0;
                $penaltyPerDay = (float) $c->penalty_per_day;
                $rawPenalty   = $overdue ? round($daysLate * $penaltyPerDay, 2) : 0.0;

                // Waivers pinned to this exact installment (the Waiver panel
                // has you pick the fee cycle it targets), then whatever is
                // left of the shared penalty-payment pool — oldest
                // installment first, academic side before transport.
                $waived = 0.0;
                foreach ($penaltyConcessions->where('fee_cycle_id', $c->id) as $con) {
                    $off = $con->concession_type === 'percent'
                        ? round($rawPenalty * ((float) $con->value) / 100, 2)
                        : (float) $con->value;
                    $waived += min($off, max(0, $rawPenalty - $waived));
                }
                $waived = round($waived, 2);

                $afterWaiver = max(0, $rawPenalty - $waived);
                $paidOff     = round(min($penaltyPaidPool, $afterWaiver), 2);
                $penaltyPaidPool = round($penaltyPaidPool - $paidOff, 2);
                $penaltyNet  = round(max(0, $afterWaiver - $paidOff), 2);

                $installments[] = [
                    'cycle_id'        => $c->id,
                    'serial'          => (int) $c->payment_serial,
                    'label'           => $c->is_token
                        ? 'Token Fee'
                        : ($periodLabel ?? ($due ? $due->format('M Y') : ('Installment ' . $c->payment_serial))),
                    'due_date'        => $due ? $due->format('d M Y') : null,
                    'start_date'      => $c->start_date ? $c->start_date->format('d M Y') : null,
                    'end_date'        => $c->end_date ? $c->end_date->format('d M Y') : null,
                    'overdue'         => $overdue,
                    'percent'         => (float) $c->fee_percent,
                    'amount'          => $amount,
                    'paid'            => $covered,
                    'balance'         => round(max(0, $amount - $covered), 2),
                    'status'          => $status,
                    'penalty_per_day' => $penaltyPerDay,
                    'days_late'       => $daysLate,
                    'penalty'         => $rawPenalty,
                    'penalty_waived'  => $waived,
                    'penalty_paid'    => $paidOff,
                    'penalty_net'     => $penaltyNet,
                ];
            }

            $count = $cycles->where('is_token', false)->count();
            $out[] = [
                'fee_type'       => $type,
                'label'          => match ($count) {
                    12      => 'Monthly',
                    4       => 'Quarterly',
                    2       => 'Half-Yearly',
                    1       => 'One-Time',
                    default => 'Custom',
                },
                'year'           => $year,
                'total'          => $total,
                'paid'           => (float) ($paid[$type] ?? 0),
                'paid_count'     => collect($installments)->where('status', 'paid')->count(),
                'count'          => $count,
                'installments'   => $installments,
                'penalty_total'  => round(collect($installments)->sum('penalty'), 2),
                'penalty_waived' => round(collect($installments)->sum('penalty_waived'), 2),
                'penalty_paid'   => round(collect($installments)->sum('penalty_paid'), 2),
                'penalty_net'    => round(collect($installments)->sum('penalty_net'), 2),
            ];
        }

        return $out;
    }
}
