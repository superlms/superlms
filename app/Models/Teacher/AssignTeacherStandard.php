<?php

namespace App\Models\Teacher;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AssignTeacherStandard extends Model
{
    protected $fillable = ['organization_id', 'teacher_detail_id', 'standard_id', 'section_id'];

    /**
     * Class teachers in class order — the Standard page's order — then each
     * class's sections as they were added, then by teacher name. Load
     * `standard` with its `order` column (and `teacher.user`) first.
     */
    public static function sortInClassOrder(Collection $assignments): Collection
    {
        return $assignments->sortBy(fn ($a) => [
            $a->standard?->order ?? PHP_INT_MAX,
            (int) $a->standard_id,
            (int) $a->section_id,
            mb_strtolower((string) ($a->teacher?->user?->name ?? '')),
        ])->values();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function teacher()
    {
        return $this->belongsTo(TeacherDetail::class, 'teacher_detail_id');
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
