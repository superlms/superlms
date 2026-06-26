<?php

namespace App\Models\Calendar;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTableResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_table_id',
        'organization_id',
        'resource_name',
        'resource_type',
        'quantity',
        'description',
        'specifications',
        'is_available',
        'availability_notes',
    ];

    protected $casts = [
        'specifications' => 'array',
        'is_available' => 'boolean',
    ];

    public function timeTable()
    {
        return $this->belongsTo(TimeTable::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
