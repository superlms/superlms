<?php

namespace App\Models\Admin\Seating;

use Illuminate\Database\Eloquent\Model;

class SeatingRoom extends Model
{
    protected $table = 'seating_rooms';

    protected $fillable = [
        'organization_id', 'room_name', 'building',
        'rows', 'columns', 'seat_capacity', 'capacity',
        'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Candidates per desk — 1 on rooms saved before the field existed. */
    public function seatCapacity(): int
    {
        return max(1, (int) ($this->seat_capacity ?? 1));
    }

    public function seats()
    {
        return $this->hasMany(SeatingSeat::class, 'room_id')->orderBy('row_no')->orderBy('col_no');
    }
}
