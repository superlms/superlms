<?php

namespace App\Models\Calendar;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTableAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_table_id',
        'organization_id',
        'user_id',
        'role',
        'attendance_status',
        'notes',
        'attended_at',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];

    const ROLES = [
        'teacher' => 'Teacher',
        'student' => 'Student',
        'staff' => 'Staff',
        'parent' => 'Parent',
        'guest' => 'Guest',
    ];

    const ATTENDANCE_STATUSES = [
        'scheduled' => 'Scheduled',
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'leave' => 'On Leave',
    ];

    public function timeTable()
    {
        return $this->belongsTo(TimeTable::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
