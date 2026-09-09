<?php

namespace App\Models\Admin\Assignment;

use Illuminate\Database\Eloquent\Model;

class AssignmentQuestionOption extends Model
{
    protected $fillable = [
        'organization_id',
        'assignment_question_id',
        'option_text',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(AssignmentQuestion::class, 'assignment_question_id');
    }
}
