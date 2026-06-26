<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TeacherArrangement extends Model
{
    protected $fillable = [
        'original_teacher_id',
        'substitute_teacher_id',
        'teacher_time_table_id',
        'date',
        'reason',
        'arranged_by',
        'organization_id'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function originalTeacher()
    {
        return $this->belongsTo(TeacherDetail::class, 'original_teacher_id');
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(TeacherDetail::class, 'substitute_teacher_id');
    }

    public function timetable()
    {
        return $this->belongsTo(TeacherTimeTable::class, 'teacher_time_table_id');
    }

    public function arrangedBy()
    {
        return $this->belongsTo(User::class, 'arranged_by');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('date', '>=', today());
    }
}
