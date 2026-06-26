<?php

namespace App\Models\Admin\Seating;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SeatAssignment extends Model
{
    protected $table = 'seat_assignments';

    protected $fillable = [
        'seating_plan_id', 'seat_id', 'room_id',
        'student_id', 'class_label', 'has_conflict', 'is_locked',
    ];

    protected $casts = [
        'has_conflict' => 'boolean',
        'is_locked'    => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SeatingPlan::class, 'seating_plan_id');
    }

    public function seat()
    {
        return $this->belongsTo(SeatingSeat::class, 'seat_id');
    }

    public function room()
    {
        return $this->belongsTo(SeatingRoom::class, 'room_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
