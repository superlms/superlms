<?php

namespace App\Models\Calendar;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTableLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_table_id',
        'organization_id',
        'room_number',
        'building',
        'location',
        'address',
        'floor',
        'capacity',
        'facilities',
    ];

    protected $casts = [
        'facilities' => 'array',
    ];

    public function timeTable()
    {
        return $this->belongsTo(TimeTable::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function getLocationDisplayAttribute()
    {
        if ($this->room_number && $this->building) {
            return "{$this->room_number}, {$this->building}";
        }
        return $this->location ?? 'TBA';
    }
}
