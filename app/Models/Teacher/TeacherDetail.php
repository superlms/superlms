<?php

namespace App\Models\Teacher;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Organization;
use App\Models\Admin\SchoolInfo;
use App\Models\Admin\TeacherIdCard;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\User;
use App\Models\Teacher\TeacherSubject;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class TeacherDetail extends Model
{
    use HasCommonScopes;

    protected $fillable = ['user_id', 'organization_id', 'employee_id', 'date_of_joining', 'qualification', 'phone', 'address', 'city', 'state', 'pincode', 'emergency_contact'];

    protected $casts = [
        'date_of_joining' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'teacher_detail_id')
            ->with(['subject', 'standard', 'section']);
    }

    public function teacherSections()
    {
        return $this->hasMany(TeacherSection::class, 'teacher_detail_id')
            ->with('section.standard');
    }

    public function assignedClasses()
    {
        return $this->hasMany(AssignTeacherStandard::class, 'teacher_detail_id')
            ->with(['standard', 'section']);
    }

    public function schoolInfo()
    {
        return $this->belongsTo(SchoolInfo::class, 'school_id');
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class, 'standard_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function getAllAssignments()
    {
        $assignments = collect();

        // From assignedSubjects
        if ($this->relationLoaded('assignedSubjects')) {
            $assignments = $assignments->merge(
                $this->assignedSubjects->map(function ($subject) {
                    return [
                        'type' => 'subject',
                        'subject_id' => $subject->subject_id,
                        'subject_name' => optional($subject->subject)->name,
                        'standard_id' => $subject->standard_id,
                        'standard_name' => optional($subject->standard)->name,
                        'section_id' => $subject->section_id,
                        'section_name' => optional($subject->section)->name,
                        'source' => 'teacher_subjects'
                    ];
                })
            );
        }

        // From assignedClasses (Attendance component)
        if ($this->relationLoaded('assignedClasses')) {
            $assignments = $assignments->merge(
                $this->assignedClasses->map(function ($class) {
                    return [
                        'type' => 'class',
                        'standard_id' => $class->standard_id,
                        'standard_name' => optional($class->standard)->name,
                        'section_id' => $class->section_id,
                        'section_name' => optional($class->section)->name,
                        'source' => 'assign_teacher_standard'
                    ];
                })
            );
        }

        return $assignments;
    }

    // Rest of the relations...
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function timetables()
    {
        return $this->hasMany(TeacherTimeTable::class);
    }

    public function attendance()
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    public function todayTimetable()
    {
        $dayOfWeek = now()->dayOfWeekIso;
        return $this->hasMany(TeacherTimeTable::class, 'teacher_detail_id')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true);
    }

    public function idCard()
    {
        return $this->hasOne(TeacherIdCard::class, 'teacher_detail_id');
    }

    public function idCards()
    {
        return $this->hasMany(TeacherIdCard::class, 'teacher_detail_id');
    }
}
