<?php

namespace App\Support;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use Illuminate\Support\Facades\DB;

/**
 * What a student is charged for the bus in one academic year, and what they
 * have paid for it.
 *
 * The rule lives here rather than in a controller because both receipts need
 * it — the academic one prints transport as a line of the overall standing,
 * the transport one prints it as the whole story — and two copies of a billing
 * rule is how they end up disagreeing.
 */
class TransportBilling
{
    /** Academic year, April first. June is the free month unless the school says otherwise. */
    public const MONTHS = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

    /**
     * The student's route and what the year costs on it.
     *
     * @return array{0:?Transportation,1:float,2:int}  route, year total, billed months
     */
    public static function forStudent(int $orgId, int $studentId): array
    {
        $route = Transportation::where('organization_id', $orgId)
            ->whereHas('students', fn ($q) => $q->where('student_details.id', $studentId))
            ->orderByDesc('is_active')
            ->first();

        if (! $route) {
            return [null, 0.0, 0];
        }

        $billed = self::billedMonths($orgId, $route->id, $studentId);

        return [$route, round((float) $route->monthly_fee * $billed, 2), $billed];
    }

    /**
     * forStudent()'s year total for many students at once, keyed by student
     * id: the route's monthly fee times the months each is billed for, on the
     * route forStudent() would pick (the active one first). A student on no
     * route is left out.
     *
     * @param  int[]  $studentIds
     * @return array<int,float>
     */
    public static function yearTotals(int $orgId, array $studentIds): array
    {
        if (! $studentIds) {
            return [];
        }

        $rows = DB::table('transportation_students as ts')
            ->join('transportations as t', 't.id', '=', 'ts.transportation_id')
            ->where('t.organization_id', $orgId)
            ->whereIn('ts.student_detail_id', $studentIds)
            ->orderByDesc('t.is_active')
            ->get(['ts.student_detail_id', 't.monthly_fee', 'ts.billable_months']);

        $totals = [];
        foreach ($rows as $row) {
            // A student's first row is their active route, if they have one.
            if (! array_key_exists($row->student_detail_id, $totals)) {
                $totals[$row->student_detail_id] = round((float) $row->monthly_fee * self::billedCount($row->billable_months), 2);
            }
        }

        return $totals;
    }

    /** How many months this student is billed for on this route. */
    public static function billedMonths(int $orgId, int $routeId, int $studentId): int
    {
        $pivot = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->where('transportation_id', $routeId)
            ->where('student_detail_id', $studentId)
            ->first();

        return self::billedCount($pivot->billable_months ?? null);
    }

    /**
     * What each student has paid for the bus, keyed by student id.
     *
     * Transport payments live in transport_fee_payments. fee_payments keeps a
     * transport row only where one could not be moved there — linked to an
     * online checkout, or carrying a penalty or a waiver — and a payment sits
     * in one table or the other, never both, so the two sums add up.
     *
     * @param  int[]  $studentIds
     * @return array<int,float>
     */
    public static function paidByStudent(int $orgId, array $studentIds): array
    {
        if (! $studentIds) {
            return [];
        }

        $paid = [];
        $queries = [
            TransportFeePayment::where('organization_id', $orgId),
            FeePayment::where('organization_id', $orgId)->where('fee_type', 'transport'),
        ];

        foreach ($queries as $query) {
            $sums = $query->whereIn('student_detail_id', $studentIds)
                ->selectRaw('student_detail_id, SUM(amount) as paid')
                ->groupBy('student_detail_id')
                ->pluck('paid', 'student_detail_id');

            foreach ($sums as $studentId => $sum) {
                $paid[$studentId] = round(($paid[$studentId] ?? 0) + (float) $sum, 2);
            }
        }

        return $paid;
    }

    /** What a whole school has taken for the bus — both tables, as paidByStudent(). */
    public static function paidForOrg(int $orgId): float
    {
        return round(
            (float) TransportFeePayment::where('organization_id', $orgId)->sum('amount')
            + (float) FeePayment::where('organization_id', $orgId)->where('fee_type', 'transport')->sum('amount'),
            2
        );
    }

    /** Months billed under a pivot row's billable_months flags (null, JSON or array). */
    private static function billedCount($raw): int
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $raw = (array) $raw;

        $billed = 0;
        foreach (self::MONTHS as $key) {
            // Unset means "billed", except June, which is free by default.
            $on = array_key_exists($key, $raw) ? (bool) $raw[$key] : ($key !== 'jun');
            if ($on) {
                $billed++;
            }
        }

        return $billed;
    }
}
