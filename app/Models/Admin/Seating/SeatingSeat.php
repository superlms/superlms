<?php

namespace App\Models\Admin\Seating;

use Illuminate\Database\Eloquent\Model;

class SeatingSeat extends Model
{
    protected $table = 'seating_seats';

    protected $fillable = ['room_id', 'row_no', 'col_no', 'seat_number'];

    public function room()
    {
        return $this->belongsTo(SeatingRoom::class, 'room_id');
    }
}
