<?php

namespace App\Models\Calendar;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTableRecurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_table_id',
        'organization_id',
        'recurrence_date',
        'is_modified',
        'modification_notes',
        'is_cancelled',
        'cancellation_reason',
        'modified_start_time',
        'modified_end_time',
        'modified_location_id',
    ];

    protected $casts = [
        'recurrence_date' => 'date',
        'is_modified' => 'boolean',
        'is_cancelled' => 'boolean',
        'modified_start_time' => 'datetime',
        'modified_end_time' => 'datetime',
    ];

    public function timeTable()
    {
        return $this->belongsTo(TimeTable::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function modifiedLocation()
    {
        return $this->belongsTo(TimeTableLocation::class, 'modified_location_id');
    }
}
