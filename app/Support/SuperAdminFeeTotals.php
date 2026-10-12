<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use Illuminate\Support\Collection;

/**
 * The Super Admin's fee from the schools, read from the fee structure set for
 * each school on the Fees screen — what the year should bring in — against
 * the money recorded on its payments. The Super Admin home and Analytics
 * count it this way; the formula is the Fees screen's own
 * (`Fees::expectedForStructure`) — change both together.
 */
class SuperAdminFeeTotals
{
    /** The year the Fees screen works in ("2026-2027"). */
    public static function year(): string
    {
        return FeeAccess::academicYear();
    }

    /** The plan as the Fees screen's dropdown names it. */
    public static function planName(?SuperAdminFeeStructure $fs): string
    {
        if (!$fs) {
            return 'Not set';
        }

        return match (true) {
            $fs->fee_type === 'per_student'                                       => 'Per Student',
            $fs->fee_type === 'one_time' && $fs->installment_frequency === 'monthly' => 'Monthly',
            $fs->fee_type === 'one_time'                                          => 'One Time',
            default                                                               => 'Class Wise',
        };
    }

    /**
     * What one structure should bring in over the year:
     *   - one_time (Monthly / One Time): the sum of its period amounts, else its
     *     total, else amount × the school's students (rows from before either);
     *   - per_student: amount × the school's students;
     *   - class_wise (legacy): amount × the students of that class.
     */
    public static function expected(SuperAdminFeeStructure $fs, int $schoolStudents, int $classStudents = 0): float
    {
        if ($fs->fee_type === 'one_time') {
            if (!empty($fs->period_amounts)) {
                return (float) array_sum($fs->period_amounts);
            }

            return $fs->total_amount !== null
                ? (float) $fs->total_amount
                : (float) $fs->amount * $schoolStudents;
        }

        if ($fs->fee_type === 'per_student') {
            return (float) $fs->amount * $schoolStudents;
        }

        return (float) $fs->amount * $classStudents;
    }

    /**
     * One row per school with an active fee structure for the year: its plan,
     * what it should pay, what it has paid and what is left — biggest fee first.
     *
     * @return Collection<int, array{organization_id:int, name:string, plan:string, expected:float, collected:float, remaining:float, pct:float}>
     */
    public static function bySchool(?string $year = null): Collection
    {
        $year = $year ?: self::year();

        $structures = SuperAdminFeeStructure::active()->forYear($year)->get();
        if ($structures->isEmpty()) {
            return collect();
        }

        $orgIds = $structures->pluck('organization_id')->unique()->values()->all();

        $students = StudentDetail::whereIn('organization_id', $orgIds)
            ->selectRaw('organization_id, COUNT(*) as c')
            ->groupBy('organization_id')
            ->pluck('c', 'organization_id');

        // Legacy per-class rows need their class's head count.
        $classIds = $structures->where('fee_type', 'class_wise')->pluck('standard_id')->filter()->unique()->values()->all();
        $classes  = $classIds
            ? StudentDetail::whereIn('standard_id', $classIds)
                ->selectRaw('organization_id, standard_id, COUNT(*) as c')
                ->groupBy('organization_id', 'standard_id')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->organization_id . '-' . $r->standard_id => (int) $r->c])
            : collect();

        $collected = SuperAdminFeePayment::forYear($year)
            ->whereIn('organization_id', $orgIds)
            ->selectRaw('organization_id, SUM(amount) as total')
            ->groupBy('organization_id')
            ->pluck('total', 'organization_id');

        $names = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        return $structures->groupBy('organization_id')->map(function (Collection $rows, $orgId) use ($students, $classes, $collected, $names) {
            $expected = $rows->sum(fn (SuperAdminFeeStructure $fs) => self::expected(
                $fs,
                (int) ($students[$orgId] ?? 0),
                (int) ($classes[$orgId . '-' . $fs->standard_id] ?? 0)
            ));
            $paid = (float) ($collected[$orgId] ?? 0);
            $main = $rows->firstWhere('fee_type', 'per_student') ?? $rows->firstWhere('fee_type', 'one_time') ?? $rows->first();

            return [
                'organization_id' => (int) $orgId,
                'name'            => $names[$orgId] ?? 'Unknown',
                'plan'            => self::planName($main),
                'expected'        => (float) $expected,
                'collected'       => $paid,
                'remaining'       => max(0, $expected - $paid),
                'pct'             => $expected > 0 ? round($paid / $expected * 100, 1) : 0,
            ];
        })->sortByDesc('expected')->values();
    }

    /**
     * The year's totals over every school: what the fee structures should bring
     * in, what was collected (every payment's amount — part payments are money
     * received too, as the Fees screen counts them) and what is left.
     */
    public static function summary(?string $year = null): array
    {
        $year    = $year ?: self::year();
        $schools = self::bySchool($year);

        $expected  = (float) $schools->sum('expected');
        $collected = (float) SuperAdminFeePayment::forYear($year)->sum('amount');

        return [
            'year'        => $year,
            'expected'    => $expected,
            'collected'   => $collected,
            'remaining'   => max(0, $expected - $collected),
            'pct'         => $expected > 0 ? round($collected / $expected * 100, 1) : 0,
            'schools'     => $schools->count(),
            'schools_due' => $schools->filter(fn ($s) => $s['remaining'] > 0.005)->count(),
            // Times money came this year (an Amount Received for many students is one).
            'payments'    => SuperAdminFeeReceipt::where('academic_year', $year)->where('amount', '>', 0)->count(),
            'by_school'   => $schools,
        ];
    }
}
