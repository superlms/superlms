<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * A fee particular. Most belong to a class (or one section of it) and apply to
 * every student in it. A row with student_detail_id is one student's own — the
 * Last Year Dues added from Fee Structure's Add Dues — and applies to that
 * student alone, whatever class they are in.
 *
 * Every ordinary query sees the class's rows only (the class_wide scope), so a
 * student's dues never reach their classmates; forStudent() reads what applies
 * to one student, their own rows included, and ownTotals() what the students'
 * own rows add up to.
 */
class FeeStructure extends Model
{
    /** The particular Add Dues writes for a student. */
    public const DUES_NAME = 'Last Year Dues';

    protected $fillable = [
        'organization_id',
        'standard_id',
        'section_id',
        'student_detail_id',
        'fee_name',
        'amount',
        'fee_type',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // A student's own particulars stay out of every class-wide read.
        static::addGlobalScope('class_wide', function (Builder $query) {
            if (self::hasStudentRows()) {
                $query->whereNull($query->getModel()->qualifyColumn('student_detail_id'));
            }
        });
    }

    /**
     * Whether the student_detail_id column is there yet (it comes with a
     * migration) — asked once per application instance.
     */
    public static function hasStudentRows(): bool
    {
        $app = app();
        if (!$app->bound('fee_structures.student_rows')) {
            $app->instance('fee_structures.student_rows', Schema::hasColumn('fee_structures', 'student_detail_id'));
        }

        return (bool) $app->make('fee_structures.student_rows');
    }

    /**
     * Every row that applies to one student: their class's (the whole class,
     * or their section), and their own particulars — Last Year Dues — whatever
     * class they are in now.
     */
    public static function forStudent(StudentDetail $student): Builder
    {
        return static::withoutGlobalScope('class_wide')
            ->where('organization_id', $student->organization_id)
            ->where(function (Builder $q) use ($student) {
                $q->where(function (Builder $c) use ($student) {
                    if (self::hasStudentRows()) {
                        $c->whereNull('student_detail_id');
                    }
                    $c->where('standard_id', $student->standard_id)
                        ->where(fn ($s) => $s->whereNull('section_id')->orWhere('section_id', $student->section_id));
                });
                if (self::hasStudentRows()) {
                    $q->orWhere('student_detail_id', $student->id);
                }
            });
    }

    /** The students' own rows (Last Year Dues) — active, academic unless told otherwise. */
    public static function ownRows(int $orgId, array $studentIds = [], ?string $feeType = 'academic', bool $wholeSchool = false): Collection
    {
        if (!self::hasStudentRows() || (!$studentIds && !$wholeSchool)) {
            return collect();
        }

        return static::withoutGlobalScope('class_wide')
            ->where('organization_id', $orgId)
            ->whereNotNull('student_detail_id')
            ->when(!$wholeSchool, fn ($q) => $q->whereIn('student_detail_id', $studentIds))
            // The school's students as they are now — a removed student's dues go with them.
            ->when($wholeSchool, fn ($q) => $q->whereIn('student_detail_id',
                StudentDetail::where('organization_id', $orgId)->select('id')))
            ->where('is_active', true)
            ->when($feeType, fn ($q) => $q->where('fee_type', $feeType))
            ->orderBy('id')
            ->get();
    }

    /** [student_detail_id => what their own rows add up to]. */
    public static function ownTotals(int $orgId, array $studentIds, ?string $feeType = 'academic'): array
    {
        return self::ownRows($orgId, $studentIds, $feeType)
            ->groupBy('student_detail_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'))
            ->all();
    }

    /** What every student's own rows in the school add up to. */
    public static function ownTotalForSchool(int $orgId, ?string $feeType = 'academic'): float
    {
        return (float) self::ownRows($orgId, [], $feeType, true)->sum('amount');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentDetail::class, 'student_detail_id');
    }

    public function scopeAcademic($query)
    {
        return $query->where('fee_type', 'academic');
    }

    public function scopeTransport($query)
    {
        return $query->where('fee_type', 'transport');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, int $standardId, ?int $sectionId = null)
    {
        $query->where('standard_id', $standardId);

        if ($sectionId) {
            $query->where(function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)->orWhereNull('section_id');
            });
        }

        return $query;
    }
}
