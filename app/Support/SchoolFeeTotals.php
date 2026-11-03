<?php

namespace App\Support;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A school's whole fee for this academic year and where it stands — what the
 * school's own Analytics shows (Admin\Analytics), and Super Admin → School
 * Data with it, so both always give the same figures:
 *
 *  - a school on Custom Fee: every student's own fee, and all that has come
 *    in on it up to today;
 *  - otherwise the academic fee (each class's active heads × its students),
 *    the students' own Last Year Dues and the bus fee (each rider's monthly
 *    fee × their billable months), against the academic and bus payments
 *    made since the academic year began.
 */
class SchoolFeeTotals
{
    /** @return array{totalFee: float, collected: float, remaining: float, transportFee: float, academicFee: float, lastYearDues: float} */
    public static function summary(int $orgId): array
    {
        if (CustomFee::enabled($orgId)) {
            $totalFee  = CustomFee::schoolTotal($orgId);
            $collected = CustomFee::collected($orgId, null, Carbon::today()->toDateString());

            return [
                'totalFee'     => $totalFee,
                'collected'    => $collected,
                'remaining'    => max(0, $totalFee - $collected),
                'transportFee' => 0.0,
                'academicFee'  => $totalFee,
                'lastYearDues' => 0.0,
            ];
        }

        $heads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic')
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');

        $pairs = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, section_id, COUNT(*) as students')
            ->groupBy('standard_id', 'section_id')
            ->get();

        $academic = 0.0;
        foreach ($pairs as $pair) {
            $fee = ($heads[$pair->standard_id] ?? collect())
                ->filter(fn ($h) => $h->section_id === null || (int) $h->section_id === (int) $pair->section_id)
                ->sum('amount');
            $academic += (float) $fee * (int) $pair->students;
        }

        $lastYearDues = FeeStructure::ownTotalForSchool($orgId); // students' own Last Year Dues

        $transport = 0.0;
        $riders = DB::table('transportation_students as ts')
            ->join('transportations as t', 'ts.transportation_id', '=', 't.id')
            ->join('student_details as sd', 'ts.student_detail_id', '=', 'sd.id')
            ->where('ts.organization_id', $orgId)
            ->get(['ts.billable_months', 't.monthly_fee']);
        foreach ($riders as $row) {
            $transport += (float) $row->monthly_fee * self::billableMonthCount($row->billable_months);
        }

        $from  = AcademicYear::start()->toDateString();
        $today = Carbon::today()->toDateString();

        $collected = (float) FeePayment::where('organization_id', $orgId)
                ->whereIn('fee_type', ['academic', 'transport'])
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $today)
                ->sum('amount')
            + (float) TransportFeePayment::where('organization_id', $orgId)
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $today)
                ->sum('amount');

        $totalFee = $academic + $lastYearDues + $transport;

        return [
            'totalFee'     => $totalFee,
            'collected'    => round($collected, 2),
            'remaining'    => max(0, $totalFee - $collected),
            'transportFee' => $transport,
            'academicFee'  => $academic,
            'lastYearDues' => $lastYearDues,
        ];
    }

    /**
     * Months a bus rider is billed for: those switched on in their saved
     * month list (June off unless set), all but June when nothing is saved.
     */
    public static function billableMonthCount($raw): int
    {
        $months = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (empty($raw) || !is_array($raw)) {
            return 11;
        }

        $count = 0;
        foreach ($months as $m) {
            $on = array_key_exists($m, $raw) ? (bool) $raw[$m] : ($m !== 'jun');
            if ($on) {
                $count++;
            }
        }

        return $count;
    }
}
