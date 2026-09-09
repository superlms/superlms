<?php

namespace App\Models\Admin\Assignment;

use Illuminate\Database\Eloquent\Model;

class AssignmentQuestion extends Model
{
    protected $fillable = [
        'organization_id',
        'assignment_id',
        'question_text',
        'marks',
        'order',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function options()
    {
        return $this->hasMany(AssignmentQuestionOption::class)->orderBy('id');
    }
}
