<?php

namespace App\Models\Admin\Seating;

use Illuminate\Database\Eloquent\Model;

class InvigilatorAssignment extends Model
{
    protected $table = 'invigilator_assignments';

    protected $fillable = ['seating_plan_id', 'room_id', 'invigilator_id'];

    public function plan()
    {
        return $this->belongsTo(SeatingPlan::class, 'seating_plan_id');
    }

    public function room()
    {
        return $this->belongsTo(SeatingRoom::class, 'room_id');
    }

    public function invigilator()
    {
        return $this->belongsTo(SeatingInvigilator::class, 'invigilator_id');
    }
}
