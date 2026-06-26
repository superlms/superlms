<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TeacherTimeTable extends Model
{
    protected $fillable = [
        'teacher_detail_id',
        'organization_id',
        'standard_id',
        'section_id',
        'subject_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'assigned_by',
        'effective_from',
        'effective_to'
    ];

    protected $dates = ['effective_from', 'effective_to'];

    public function teacher()
    {
        return $this->belongsTo(TeacherDetail::class, 'teacher_detail_id');
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

    // Change this relationship name to avoid conflict
    public function teacherArrangements()
    {
        return $this->hasMany(TeacherArrangement::class, 'teacher_detail_id');
    }

    // Add a belongsTo relationship for current substitute
    public function currentSubstitute()
    {
        return $this->belongsTo(TeacherArrangement::class, 'id', 'teacher_detail_id')
            ->whereDate('date', now()->toDateString());
    }

    // Or use a hasOne relationship
    public function todaysArrangement()
    {
        return $this->hasOne(TeacherArrangement::class, 'original_teacher_id')
            ->whereDate('date', now()->toDateString());
    }

    // Add relationship to substitute teacher
    public function substituteTeacher()
    {
        return $this->hasOneThrough(
            TeacherDetail::class,
            TeacherArrangement::class,
            'teacher_detail_id', // Foreign key on TeacherArrangement table
            'id', // Foreign key on TeacherDetail table
            'teacher_detail_id', // Local key on TeacherTimeTable
            'substitute_teacher_id' // Local key on TeacherArrangement
        )->whereDate('teacher_arrangements.date', now()->toDateString());
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // Scope for active timetables
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for specific day
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // Scope for current effective timetables
    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_from')
                ->orWhere('effective_from', '<=', today());
        })->where(function ($q) {
            $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', today());
        });
    }
}