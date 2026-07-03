<?php

namespace App\Models\Admin;

use App\Models\Admin\AdminAttendance;
use App\Models\Organization;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class AdminEmployee extends Model
{
    use HasCommonScopes;

    protected $fillable = [
        'name',
        'organization_id',
        'teacher_detail_id',
        'driver_detail_id',
        'email',
        'mobile',
        'designation',
        'type',
        'salary',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_holder_name',
        'bank_branch',
        'bank_ifsc',
        'photo',
        'is_active',
        'joining_date',
    ];

    protected $hidden = [
        'password',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function teacherDetail()
    {
        return $this->belongsTo(TeacherDetail::class);
    }

    public function driverDetail()
    {
        return $this->belongsTo(DriverDetail::class);
    }

    public function idCard()
    {
        return $this->hasOne(EmployeeIdCard::class, 'admin_employee_id');
    }

    public function idCards()
    {
        return $this->hasMany(EmployeeIdCard::class, 'admin_employee_id');
    }

    public const TEACHER_STATUS_MAP = [
        'present'  => 1,
        'absent'   => 0,
        'late'     => 2,
        'half_day' => 3,
    ];

    public function isTeacher(): bool
    {
        return $this->type === 'teacher';
    }

    public function getAttendanceStatusForDate(string $date): ?string
    {
        if ($this->isTeacher() && $this->teacher_detail_id) {
            $attendance = TeacherAttendance::where('teacher_detail_id', $this->teacher_detail_id)
                ->whereDate('attendance_date', $date)
                ->first();

            return $attendance?->statusLabel;
        }

        $attendance = AdminAttendance::where('admin_employee_id', $this->id)
            ->whereDate('date', $date)
            ->first();

        return $attendance?->status;
    }
}
