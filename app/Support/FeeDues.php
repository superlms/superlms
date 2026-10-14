<?php

namespace App\Support;

use App\Models\Admin\Fee\FeePayment;
use Illuminate\Support\Facades\Schema;

/**
 * A student's academic fee in two parts: this year's — what the school's fee
 * cycle splits into installments — and their Last Year Dues (the student's own
 * fee rows, FeeStructure::ownRows), each with what has been paid against it.
 *
 * Collect Fee's "Dues" books an academic payment marked applies_to = 'dues':
 * it goes against the dues, never this year's installments. An ordinary
 * academic payment goes to this year's fee first and only what is over it
 * reaches the dues; a dues payment over the dues reaches this year's fee.
 * Either way the two parts add up to the academic fee and academic payments
 * every other screen reads, so their totals do not move.
 *
 * The View Fee ledger (HandlesStudentFeeView) and the receipts read it, so
 * the slip says what the screen says.
 */
class FeeDues
{
    /** Whether fee_payments has its applies_to column yet (the migration of 14 Oct 2026). */
    public static function tracked(): bool
    {
        $key = 'fee_payments.applies_to';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasColumn('fee_payments', 'applies_to');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Whether one payment was taken for the dues. */
    public static function isDues($payment): bool
    {
        return ($payment->applies_to ?? null) === 'dues';
    }

    /** What a student has paid as dues — academic payments marked for them. */
    public static function paidAsDues(int $orgId, int $studentId): float
    {
        if (!self::tracked()) {
            return 0.0;
        }

        return round((float) FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->where('fee_type', 'academic')
            ->where('applies_to', 'dues')
            ->sum('amount'), 2);
    }

    /**
     * @param  float  $academicNet   the academic fee, dues included, net of concession
     * @param  float  $duesGross     the student's own rows (Last Year Dues)
     * @param  float  $academicPaid  every academic payment, dues payments included
     * @param  float  $paidAsDues    the academic payments marked for the dues
     * @return array{dues_total: float, dues_paid: float, dues_remaining: float, cycle_total: float, cycle_paid: float}
     */
    public static function split(float $academicNet, float $duesGross, float $academicPaid, float $paidAsDues): array
    {
        // A concession comes off this year's fee first; the dues stay whole
        // unless it is larger than this year's fee.
        $duesTotal  = round(max(0, min($duesGross, $academicNet)), 2);
        $cycleTotal = round(max(0, $academicNet - $duesTotal), 2);

        $paidAsDues = round(max(0, min($paidAsDues, $academicPaid)), 2);
        $ordinary   = round(max(0, $academicPaid - $paidAsDues), 2);

        // Ordinary payments: this year first, the rest to the dues.
        // Dues payments: the dues first, the rest to this year.
        $cyclePaid = min($cycleTotal, $ordinary + max(0, $paidAsDues - $duesTotal));
        $duesPaid  = min($duesTotal, $paidAsDues + max(0, $ordinary - $cycleTotal));

        return [
            'dues_total'     => $duesTotal,
            'dues_paid'      => round($duesPaid, 2),
            'dues_remaining' => round(max(0, $duesTotal - $duesPaid), 2),
            'cycle_total'    => $cycleTotal,
            'cycle_paid'     => round($cyclePaid, 2),
        ];
    }
}
