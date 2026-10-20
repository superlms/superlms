<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/**
 * A school's biometric attendance: on or off (the super admin, with a code),
 * and its times (the admin's Setting page) — App\Support\Biometric.
 */
class BiometricSetting extends Model
{
    protected $fillable = ['organization_id', 'enabled', 'student_last_time', 'teacher_in_time', 'teacher_out_time', 'updated_by'];

    protected $casts = ['enabled' => 'boolean'];
}
