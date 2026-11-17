<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin → More → Gatepass: a student let out of school — why, when, who took
 * them, and when they came back if they did. Numbered per school.
 */
class StudentGatePass extends Model
{
    protected $fillable = [
        'organization_id', 'pass_number', 'student_detail_id', 'standard_id', 'section_id',
        'reason', 'out_at', 'returned_at', 'picked_by_name', 'picked_by_relation',
        'picked_by_mobile', 'remarks', 'added_by',
    ];

    protected $casts = [
        'out_at'      => 'datetime',
        'returned_at' => 'datetime',
    ];

    /** Who usually takes a student out. */
    public const RELATIONS = ['Father', 'Mother', 'Guardian', 'Brother', 'Sister', 'Grandfather', 'Grandmother', 'Uncle', 'Aunt', 'Driver', 'Other'];

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

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /** "GP-0007". */
    public function number(): string
    {
        return 'GP-' . str_pad((string) $this->pass_number, 4, '0', STR_PAD_LEFT);
    }

    /** The next number for a school's pass. */
    public static function nextNumber(int $orgId): int
    {
        return (int) static::where('organization_id', $orgId)->max('pass_number') + 1;
    }
}
