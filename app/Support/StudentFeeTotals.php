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

    /** A concession-adjusted total for one side, as the receipt works it out. */
    private static function netOf(float $gross, string $type, $concessions, float $duesGross = 0.0): float
    {
        return round(max(0, $gross - FeeConcession::takeOff($concessions, $type, $gross, $duesGross)['taken']), 2);
    }
}
