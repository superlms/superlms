<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/**
 * A school's biometric attendance: on or off (the super admin, with a code),
 * its times (the admin's Setting page) and the times a teacher's or student's
 * first punch is Present, Absent or Half Day (Attendance → Timing) — App\Support\Biometric.
 */
class BiometricSetting extends Model
{
    protected $fillable = ['organization_id', 'enabled', 'student_last_time', 'teacher_in_time', 'teacher_out_time', 'teacher_status_times', 'student_status_times', 'updated_by'];

    protected $casts = ['enabled' => 'boolean', 'teacher_status_times' => 'array', 'student_status_times' => 'array'];
}
