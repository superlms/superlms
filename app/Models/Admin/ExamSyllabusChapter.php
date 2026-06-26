<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Database\Eloquent\Model;

class ExamSyllabusChapter extends Model
{
    protected $table = 'exam_syllabus_chapters';

    protected $fillable = [
        'organization_id',
        'exam_id',
        'standard_id',
        'subject_id',
        'section_id',
        'chapter_id',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
