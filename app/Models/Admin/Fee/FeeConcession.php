<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;

class FeeConcession extends Model
{
    /**
     * What a concession can be put on, as Add Concession offers it. 'dues' is
     * the student's Last Year Dues (their own fee rows, FeeStructure::ownRows) —
     * part of the academic fee, so it is counted on the academic side. Older
     * rows may still read 'all': academic and transport both.
     */
    public const APPLIES_TO = [
        'academic'  => 'Academic',
        'transport' => 'Transport',
        'dues'      => 'Last Year Dues',
    ];

    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'standard_id',
        'section_id',
        'concession_type', // 'amount' | 'percent'
        'value',
        'fee_type',        // 'academic' | 'transport' | 'dues' | 'all' (older rows)
        'is_penalty',      // true = this waives accrued penalty, not the base fee
        'fee_cycle_id',    // penalty waivers only — the one installment this targets
        'reason',
        'academic_year',
        'created_by',
        'collected_by',    // staff name, for penalty waivers made via the Penalties tab
        'payment_mode',    // penalty waivers only — cash | online | cheque | bank_transfer
    ];

    protected $casts = [
        'value'      => 'decimal:2',
        'is_penalty' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /** Resolve this concession to a flat rupee discount against a base amount. */
    public function discountOn(float $base): float
    {
        return $this->concession_type === 'percent'
            ? round($base * ((float) $this->value) / 100, 2)
            : min((float) $this->value, $base);
    }

    /** What it is on, in words — "Academic", "Last Year Dues", "All fees". */
    public static function appliesToLabel(?string $feeType): string
    {
        return self::APPLIES_TO[$feeType] ?? ($feeType === 'all' ? 'All fees' : ucfirst((string) $feeType));
    }

    /**
     * What a set of concessions takes off one side of a student's fee, one by
     * one, none taking more than is left. An 'academic' or 'all' concession is
     * worked out on the whole academic fee; a 'dues' one on the Last Year Dues
     * alone ($duesGross, part of the academic $gross) and never more than they
     * come to. 'dues' never touches transport.
     *
     * @param  iterable<self>  $concessions
     * @return array{taken: float, dues: float, applied: array<int, array{concession: self, amount: float}>}
     */
    public static function takeOff(iterable $concessions, string $side, float $gross, float $duesGross = 0.0): array
    {
        $duesGross = max(0, min($duesGross, $gross));
        $taken     = 0.0;
        $offDues   = 0.0;
        $applied   = [];

        foreach ($concessions as $c) {
            $onDues = $c->fee_type === 'dues';
            if ($onDues ? $side !== 'academic' : ($c->fee_type !== 'all' && $c->fee_type !== $side)) {
                continue;
            }

            $base = $onDues ? $duesGross : $gross;
            $left = $gross - $taken;
            if ($onDues) {
                $left = min($left, $duesGross - $offDues);
            }

            $off = $c->concession_type === 'percent'
                ? $base * ((float) $c->value) / 100
                : (float) $c->value;
            $off = round(min($off, max(0, $left)), 2);
            if ($off <= 0) {
                continue;
            }

            $taken += $off;
            if ($onDues) {
                $offDues += $off;
            }
            $applied[] = ['concession' => $c, 'amount' => $off];
        }

        return ['taken' => round($taken, 2), 'dues' => round($offDues, 2), 'applied' => $applied];
    }
}
