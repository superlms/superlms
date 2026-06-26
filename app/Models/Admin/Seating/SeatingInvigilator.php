<?php

namespace App\Models\Admin\Seating;

use Illuminate\Database\Eloquent\Model;

class SeatingInvigilator extends Model
{
    protected $table = 'seating_invigilators';

    protected $fillable = [
        'organization_id', 'name', 'email', 'phone',
        'available_dates', 'max_rooms', 'is_active', 'notes',
    ];

    protected $casts = [
        'available_dates' => 'array',
        'is_active'       => 'boolean',
    ];

    public function isAvailableOn(string $date): bool
    {
        $dates = $this->available_dates ?? [];
        return empty($dates) ? false : in_array($date, $dates, true);
    }
}
