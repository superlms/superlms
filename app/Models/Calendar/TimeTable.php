<?php

namespace App\Models\Calendar;

use App\Models\Organization;
use App\Models\User;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTable extends Model
{
    use HasFactory,HasCommonScopes;

     protected $fillable = [
        'organization_id',
        'created_by',
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'event_type',
        'color',
        'recurrence',
        'recurrence_end_date',
        'is_all_day',
        'is_cancelled',
        'cancellation_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'recurrence_end_date' => 'date',
        'is_all_day' => 'boolean',
        'is_cancelled' => 'boolean',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function location()
    {
        return $this->hasOne(TimeTableLocation::class);
    }

    public function academic()
    {
        return $this->hasOne(TimeTableAcademic::class);
    }

    public function attendees()
    {
        return $this->hasMany(TimeTableAttendee::class);
    }

    public function recurrences()
    {
        return $this->hasMany(TimeTableRecurrence::class);
    }

    public function resources()
    {
        return $this->hasMany(TimeTableResource::class);
    }

    // Helper methods
    public function toCalendarEvent()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_all_day' => $this->is_all_day,
            'color' => $this->color,
            'event_type' => $this->event_type,
            'location' => $this->location?->location_display,
            'standard' => $this->academic?->standard?->name,
            'section' => $this->academic?->section?->name,
            'subject' => $this->academic?->subject?->name,
            'teacher' => $this->academic?->teacher?->name,
        ];
    }
}
