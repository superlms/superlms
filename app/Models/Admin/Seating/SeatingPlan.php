<?php

namespace App\Models\Admin\Seating;

use App\Models\Admin\Exam;
use Illuminate\Database\Eloquent\Model;

class SeatingPlan extends Model
{
    protected $table = 'seating_plans';

    protected $fillable = [
        'organization_id', 'exam_id', 'name', 'exam_date', 'session',
        'status', 'generated_at', 'total_students', 'total_seats',
        'conflict_count', 'notes',
    ];

    protected $casts = [
        'exam_date'    => 'date',
        'generated_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function seatAssignments()
    {
        return $this->hasMany(SeatAssignment::class, 'seating_plan_id');
    }

    public function invigilatorAssignments()
    {
        return $this->hasMany(InvigilatorAssignment::class, 'seating_plan_id');
    }
}
