<?php

namespace App\Models\Teacher;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;

class AssignTeacherStandard extends Model
{
    protected $fillable = ['organization_id', 'teacher_detail_id', 'standard_id', 'section_id'];

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
