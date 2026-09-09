<?php

namespace App\Models\Admin\Assignment;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'standard_id',
        'section_id',
        'subject_id',
        'title',
        'description',
        'type',
        'submission_mode',
        'file',
        'start_date',
        'end_date',
        'total_marks',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function questions()
    {
        return $this->hasMany(AssignmentQuestion::class)->orderBy('order')->orderBy('id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function isMcq(): bool
    {
        return $this->type === 'mcq';
    }

    /** Marks the assignment is out of — MCQs total their own marks when nothing is set. */
    public function maxMarks(): int
    {
        if ($this->total_marks > 0) {
            return (int) $this->total_marks;
        }

        return $this->isMcq() ? (int) $this->questions()->sum('marks') : 0;
    }

    /** open | upcoming | closed — where "now" sits in the start → end window. */
    public function windowStatus(): string
    {
        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return 'upcoming';
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return 'closed';
        }

        return 'open';
    }
}
