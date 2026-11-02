<?php

namespace App\Support;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\StudentDetail;
use Carbon\Carbon;

/**
 * Each student's academic installments — what each comes to and what is
 * still owed on it — worked out the way the fee reminders work them out
 * (StudentPushNotifier::remindOrg): the class's active academic fee and the
 * student's own Last Year Dues, less concessions; the token fee off the top;
 * the cycles' percentages share the rest; what was paid covers them in order.
 *
 * Used by Fee Autopay to know what to debit and when.
 */
class FeeInstallments
{
    /**
     * @param  int[]|null  $studentIds  only these students (null: every student of the school)
     * @return array<int, list<array{cycle_id:int, serial:int, label:string, due_date:?Carbon, amount:float, outstanding:float}>>
     *         student_detail_id => installments, in order
     */
    public static function forOrg(int $orgId, ?array $studentIds = null): array
    {
        $cycles = FeeCycle::where('organization_id', $orgId)->where('is_active', true)
            ->where('fee_type', 'academic')->orderBy('payment_serial')->get();
        if ($cycles->isEmpty()) {
            return [];
        }
        $tokenAmt = (float) optional($cycles->firstWhere('is_token', true))->amount;

        $students = StudentDetail::where('organization_id', $orgId)
            ->when($studentIds !== null, fn ($q) => $q->whereIn('id', $studentIds ?: [0]))
            ->get(['id', 'standard_id', 'section_id']);
        if ($students->isEmpty()) {
            return [];
        }
        $ids = $students->pluck('id')->all();

        $structures = FeeStructure::where('organization_id', $orgId)->where('is_active', true)
            ->where('fee_type', 'academic')
            ->get(['standard_id', 'section_id', 'amount']);   // class-wide only (FeeStructure's global scope)
        $ownFees = FeeStructure::ownTotals($orgId, $ids);
        $paid = FeePayment::where('organization_id', $orgId)->where('fee_type', 'academic')
            ->whereIn('student_detail_id', $ids)
            ->selectRaw('student_detail_id, SUM(amount) as paid')->groupBy('student_detail_id')
            ->pluck('paid', 'student_detail_id');
        $concessions = FeeConcession::where('organization_id', $orgId)->whereIn('fee_type', ['academic', 'all', 'dues'])
            ->whereIn('student_detail_id', $ids)->get()->groupBy('student_detail_id');

        $out = [];
        foreach ($students as $student) {
            $academicDue = (float) $structures->where('standard_id', $student->standard_id)
                ->filter(fn ($s) => !$s->section_id || (int) $s->section_id === (int) $student->section_id)
                ->sum('amount')
                + (float) ($ownFees[$student->id] ?? 0);
            if ($academicDue <= 0) {
                continue;
            }

            // A concession on the Last Year Dues is on the dues alone, never more than they come to.
            $own        = collect($concessions->get($student->id, []));
            $duesGross  = (float) ($ownFees[$student->id] ?? 0);
            $concession = min($academicDue, round((float) $own->where('fee_type', '!=', 'dues')
                ->sum(fn ($c) => $c->discountOn($academicDue))
                + min($duesGross, (float) $own->where('fee_type', 'dues')->sum(fn ($c) => $c->discountOn($duesGross))), 2));
            $base = max(0, $academicDue - $concession - $tokenAmt);
            $left = (float) ($paid[$student->id] ?? 0);

            foreach ($cycles as $cycle) {
                $amount  = $cycle->fee_percent > 0 ? round($base * (float) $cycle->fee_percent / 100, 2) : (float) $cycle->amount;
                $covered = min($left, $amount);
                $left    = max(0, $left - $covered);

                $out[$student->id][] = [
                    'cycle_id'    => (int) $cycle->id,
                    'serial'      => (int) $cycle->payment_serial,
                    'label'       => $cycle->is_token ? 'Registration fee' : self::ordinal((int) $cycle->payment_serial) . ' installment',
                    'due_date'    => $cycle->due_date ? Carbon::parse($cycle->due_date)->startOfDay() : null,
                    'amount'      => round($amount, 2),
                    'outstanding' => round($amount - $covered, 2),
                ];
            }
        }

        return $out;
    }

    public static function ordinal(int $n): string
    {
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');

        return $n . $suffix;
    }
}
