<?php

namespace App\Models\Admin;

use App\Models\Teacher\TeacherDetail;
use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    protected $fillable = [
        'teacher_detail_id',
        'day_of_week',
        'available_from',
        'available_to',
        'is_available',
        'unavailability_reason'
    ];

    public function teacher()
    {
        return $this->belongsTo(TeacherDetail::class);
    }
}
