<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamCopy extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'student_detail_id', 'standard_id', 'section_id', 'subject_id', 'teacher_detail_id', 'exam_id', 'marks_obtained', 'max_marks', 'percentage', 'grade', 'remarks', 'is_absent', 'is_recheck', 'breakup', 'file', 'pdf_path', 'uploaded_by'];

    protected $casts = [
        'breakup' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacherDetails()
    {
        return $this->belongsTo(TeacherDetail::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSubjectMarks()
    {
        return $this->hasMany(ExamSubjectMark::class, 'exam_copy_id');
    }
}
