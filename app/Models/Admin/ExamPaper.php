<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Subject;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPaper extends Model
{
    protected $fillable = [
        'organization_id',
        'exam_id',
        'standard_id',
        'section_id',
        'subject_id',
        'title',
        'description',
        'file_path',
        'uploaded_by',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** NULL when the paper was filed under "Other" (no class subject). */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** Label for the papers list: the subject name, or "Other". */
    public function subjectLabel(): string
    {
        return $this->subject->name ?? 'Other';
    }
}
