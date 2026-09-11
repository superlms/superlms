<?php

namespace App\Support;

use App\Models\Admin\Fee\FeeCycle;

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
     * @param  array  $paid    ['academic' => float, 'transport' => float]
     * @param  array  $totals  ['academic' => float, 'transport' => float] — net of concession
     * @return array<int, array{fee_type:string,label:string,year:string,total:float,paid:float,paid_count:int,count:int,installments:array}>
     */
    public static function build(int $orgId, array $paid, array $totals): array
    {
        $out = [];

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

                $due     = $c->due_date;
                $overdue = $due && $status !== 'paid' && $due->isPast();

                // Penalty accrued so far on what is still outstanding — days
                // past due times the installment's own per-day rate. Only the
                // unpaid slice of the installment carries it, so a partial
                // payment that already cleared it stops accruing more.
                $daysLate     = $overdue ? $due->diffInDays(now()->startOfDay()) : 0;
                $penaltyPerDay = (float) $c->penalty_per_day;
                $penalty      = $overdue ? round($daysLate * $penaltyPerDay, 2) : 0.0;

                $installments[] = [
                    'serial'          => (int) $c->payment_serial,
                    'label'           => $c->is_token
                        ? 'Token Fee'
                        : ($due ? $due->format('M Y') : ('Installment ' . $c->payment_serial)),
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
                    'penalty'         => $penalty,
                ];
            }

            $count = $cycles->where('is_token', false)->count();
            $out[] = [
                'fee_type'      => $type,
                'label'         => match ($count) {
                    12      => 'Monthly',
                    4       => 'Quarterly',
                    2       => 'Half-Yearly',
                    1       => 'One-Time',
                    default => 'Custom',
                },
                'year'          => $year,
                'total'         => $total,
                'paid'          => (float) ($paid[$type] ?? 0),
                'paid_count'    => collect($installments)->where('status', 'paid')->count(),
                'count'         => $count,
                'installments'  => $installments,
                'penalty_total' => round(collect($installments)->sum('penalty'), 2),
            ];
        }

        return $out;
    }
}
