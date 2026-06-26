<?php

namespace App\Models\Student;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    protected $fillable = ['student_detail_id', 'user_id', 'organization_id', 'attendance_date', 'status', 'remarks', 'marked_by'];

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
