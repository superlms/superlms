<?php

namespace App\Models\Admin\Assignment;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmissionAnswer extends Model
{
    protected $fillable = [
        'organization_id',
        'assignment_submission_id',
        'assignment_question_id',
        'assignment_question_option_id',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }

    public function question()
    {
        return $this->belongsTo(AssignmentQuestion::class, 'assignment_question_id');
    }

    public function option()
    {
        return $this->belongsTo(AssignmentQuestionOption::class, 'assignment_question_option_id');
    }
}
