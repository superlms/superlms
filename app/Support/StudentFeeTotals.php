<?php

namespace App\Support;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;

/**
 * A student's whole fee for the year and where it stands — the printed fee
 * receipt's "Overall" (Admin\FeeReceiptController): the academic fee (their
 * class's heads and their own Last Year Dues) and the bus fee, each net of
 * concession, against every academic and bus payment. The fee_received
 * WhatsApp message (App\Services\SchoolWhatsApp) reads it, so the message
 * and the slip give the same Total fee and Fee remaining.
 */
class StudentFeeTotals
{
    /** @return array{total: float, paid: float, balance: float} */
    public static function overall(int $orgId, StudentDetail $student): array
    {
        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(fn ($q) => $q->where('section_id', $student->section_id)->orWhereNull('section_id'))
            ->where('is_active', true)
            ->get()
            ->concat(FeeStructure::ownRows($orgId, [$student->id], null));

        $concessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where('is_penalty', false)
            ->get();

        [, $transportGross] = TransportBilling::forStudent($orgId, $student->id);

        $academic = $structures->where('fee_type', 'academic');
        // The student's own rows — Last Year Dues, which a 'dues' concession is on.
        $duesGross = (float) $academic->filter(fn ($r) => !empty($r->student_detail_id))->sum('amount');

        $total = self::netOf((float) $academic->sum('amount'), 'academic', $concessions, $duesGross)
            + self::netOf((float) $transportGross, 'transport', $concessions);

        $paid = (float) FeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)
                ->where('fee_type', 'academic')->sum('amount')
            + (float) TransportFeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)->sum('amount');

        return [
            'total'   => round($total, 2),
            'paid'    => round($paid, 2),
            'balance' => round(max(0, $total - $paid), 2),
        ];
    }

    /**
     * overall() for a page of students at once (More → Boards' list, under each
     * mobile number, 2026-11-26): the same figures in a handful of queries —
     * the classes' heads, the students' own rows, their concessions, their bus
     * fee and their payments each fetched once for all of them.
     *
     * @param  iterable<StudentDetail>  $students
     * @return array<int, array{total: float, paid: float, balance: float}>  keyed by student id
     */
    public static function overallMany(int $orgId, iterable $students): array
    {
        $students = collect($students);
        $ids      = $students->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (!$ids) {
            return [];
        }

        $classRows = FeeStructure::where('organization_id', $orgId)
            ->whereIn('standard_id', $students->pluck('standard_id')->filter()->unique()->values()->all() ?: [0])
            ->where('is_active', true)
            ->get();
        $ownRows     = FeeStructure::ownRows($orgId, $ids, null)->groupBy('student_detail_id');
        $concessions = FeeConcession::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $ids)
            ->where('is_penalty', false)
            ->get()
            ->groupBy('student_detail_id');
        $busGross = TransportBilling::yearTotals($orgId, $ids);
        $paidFee  = FeePayment::where('organization_id', $orgId)->whereIn('student_detail_id', $ids)->where('fee_type', 'academic')
            ->groupBy('student_detail_id')->selectRaw('student_detail_id, SUM(amount) as paid')->pluck('paid', 'student_detail_id');
        $paidBus  = TransportFeePayment::where('organization_id', $orgId)->whereIn('student_detail_id', $ids)
            ->groupBy('student_detail_id')->selectRaw('student_detail_id, SUM(amount) as paid')->pluck('paid', 'student_detail_id');

        $out = [];
        foreach ($students as $student) {
            $id         = (int) $student->id;
            $structures = $classRows
                ->filter(fn ($r) => (int) $r->standard_id === (int) $student->standard_id
                    && ($r->section_id === null || (int) $r->section_id === (int) $student->section_id))
                ->concat($ownRows->get($id, collect()));
            $theirs   = $concessions->get($id, collect());
            $academic = $structures->where('fee_type', 'academic');
            $duesGross = (float) $academic->filter(fn ($r) => !empty($r->student_detail_id))->sum('amount');

            $total = self::netOf((float) $academic->sum('amount'), 'academic', $theirs, $duesGross)
                + self::netOf((float) ($busGross[$id] ?? 0), 'transport', $theirs);
            $paid  = (float) ($paidFee[$id] ?? 0) + (float) ($paidBus[$id] ?? 0);

            $out[$id] = [
                'total'   => round($total, 2),
                'paid'    => round($paid, 2),
                'balance' => round(max(0, $total - $paid), 2),
            ];
        }

        return $out;
    }

    /** A concession-adjusted total for one side, as the receipt works it out. */
    private static function netOf(float $gross, string $type, $concessions, float $duesGross = 0.0): float
    {
        return round(max(0, $gross - FeeConcession::takeOff($concessions, $type, $gross, $duesGross)['taken']), 2);
    }
}
