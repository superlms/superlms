<?php

namespace App\Models\Teacher;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TeacherAttendance extends Model
{
    protected $fillable = ['teacher_detail_id', 'organization_id', 'attendance_date', 'status', 'remarks', 'marked_by'];

    protected $dates = [
        'attendance_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function teacherDetail()
    {
        return $this->belongsTo(TeacherDetail::class, 'teacher_detail_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function getStatusLabelAttribute()
    {
        $statusMap = [
            1 => 'present',
            0 => 'absent',
            2 => 'late', 
            3 => 'half_day',
        ];

        return $statusMap[$this->status] ?? 'present';
    }
}
