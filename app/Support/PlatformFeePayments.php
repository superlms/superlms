<?php

namespace App\Support;

use App\Models\PaymentTransaction;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;

/**
 * A student's own payment of the Per Student platform fee (FeeAccess), made in
 * the app through PhonePe, booked on their Super Admin fee: their payment row
 * goes up by what came (a payment row holds all of a student's fee so far),
 * so Super Admin → Fees shows them paid, and SuperAdminFeePayment's events
 * note the money on the day it came for Reports and the Ledger.
 */
class PlatformFeePayments
{
    /**
     * Book a COMPLETED platform transaction (called once, inside
     * PaymentTransaction::settle's lock). Returns the payment row's id, or -1
     * when there was nothing to book it on (the student or the fee is gone) —
     * the money is then only in the transaction, and it is logged.
     */
    public static function book(PaymentTransaction $txn, array $meta): int
    {
        $student   = StudentDetail::find($txn->student_detail_id);
        $structure = SuperAdminFeeStructure::find($meta['structure_id'] ?? 0)
            ?? FeeAccess::perStudentStructure((int) $txn->organization_id);

        if (!$student || !$structure) {
            logger()->warning('Platform fee paid but not booked', [
                'merchant_order_id' => $txn->merchant_order_id,
                'student_detail_id' => $txn->student_detail_id,
                'structure_id'      => $meta['structure_id'] ?? null,
            ]);

            return -1;
        }

        $payment = SuperAdminFeePayment::where('student_detail_id', $student->id)
            ->where('super_admin_fee_structure_id', $structure->id)
            ->first();

        $total = round((float) ($payment?->amount ?? 0) + (float) $txn->amount, 2);
        $fee   = (float) $structure->amount;

        $values = [
            'organization_id' => $structure->organization_id,
            'standard_id'     => $structure->standard_id,
            'section_id'      => $student->section_id,
            'amount'          => $total,
            'academic_year'   => $structure->academic_year,
            'payment_mode'    => 'online',
            'payment_date'    => now()->toDateString(),
            'remark'          => 'Paid by the student in the app via PhonePe (' . $txn->merchant_order_id . ')',
            'is_paid'         => $fee > 0 && $total + 0.01 >= $fee,
        ];

        if ($payment) {
            $payment->update($values);
        } else {
            $payment = SuperAdminFeePayment::create($values + [
                'student_detail_id'            => $student->id,
                'super_admin_fee_structure_id' => $structure->id,
            ]);
        }

        FeeAccess::flush();

        return (int) $payment->id;
    }
}
