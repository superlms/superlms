<?php

namespace App\Models\Admin\Seating;

use Illuminate\Database\Eloquent\Model;

class SeatingRoom extends Model
{
    protected $table = 'seating_rooms';

    protected $fillable = [
        'organization_id', 'room_name', 'building',
        'rows', 'columns', 'capacity',
        'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function seats()
    {
        return $this->hasMany(SeatingSeat::class, 'room_id')->orderBy('row_no')->orderBy('col_no');
    }
}
