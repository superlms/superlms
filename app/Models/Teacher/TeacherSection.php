<?php

namespace App\Models\Teacher;

use App\Models\Student\Section;
use Illuminate\Database\Eloquent\Model;

class TeacherSection extends Model
{
    protected $fillable = ['teacher_detail_id', 'subject_id', 'standard_id', 'section_id', 'organization_id'];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
