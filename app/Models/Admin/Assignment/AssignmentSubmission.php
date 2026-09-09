<?php

namespace App\Models\Admin\Assignment;

use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'organization_id',
        'assignment_id',
        'user_id',
        'student_detail_id',
        'answer_text',
        'file',
        'file_name',
        'status',
        'marks',
        'remarks',
        'mcq_score',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'marks'        => 'decimal:2',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(StudentDetail::class, 'student_detail_id');
    }

    public function answers()
    {
        return $this->hasMany(AssignmentSubmissionAnswer::class);
    }
}
