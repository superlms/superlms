<?php

namespace App\Models\Student;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    // punch_time / source: the time a biometric machine's punch marked the day (App\Support\BiometricPunches).
    protected $fillable = ['student_detail_id', 'user_id', 'organization_id', 'attendance_date', 'status', 'remarks', 'marked_by', 'punch_time', 'source'];

    protected static function booted(): void
    {
        // A student who must first pay the platform fee in the app is not
        // marked (FeeAccess). Every screen that marks leaves them out and says
        // so; this only stops a mark that comes some other way. Nothing is
        // thrown: one such student never stops the rest of a class being saved.
        $locked = fn (self $model) => $model->student_detail_id
            && \App\Support\FeeAccess::locked((int) $model->student_detail_id, $model->organization_id ? (int) $model->organization_id : null)
                ? false : null;
        static::creating($locked);
        static::updating($locked);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function markerdBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
