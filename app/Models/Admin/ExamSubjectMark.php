<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Subject;
use Illuminate\Database\Eloquent\Model;

class ExamSubjectMark extends Model
{
    protected $fillable = ['organization_id', 'exam_copy_id', 'subject_id', 'marks_obtained', 'max_marks', 'percentage', 'grade', 'evaluation_type', 'academic_year', 'counts_towards_yearly', 'weightage'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function examCopy()
    {
        return $this->belongsTo(ExamCopy::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
