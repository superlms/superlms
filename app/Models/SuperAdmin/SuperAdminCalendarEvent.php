<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One meeting, task or event on a super admin's (or sub-super-admin's) own
 * calendar. It belongs to the user who added it and is shown to that user
 * alone: always read these through ownedBy().
 */
class SuperAdminCalendarEvent extends Model
{
    public const TYPES = [
        'meeting' => 'Meeting',
        'task'    => 'Task',
        'event'   => 'Event',
    ];

    protected $table = 'super_admin_calendar_events';

    protected $fillable = [
        'user_id', 'type', 'title', 'date', 'start_time', 'end_time',
        'with_whom', 'location', 'notes', 'is_done', 'done_at',
    ];

    protected $casts = [
        'date'    => 'date',
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    /** The calendar of this user only. */
    public function scopeOwnedBy(Builder $query, ?int $userId): Builder
    {
        return $query->where('user_id', (int) $userId);
    }

    /** "10:30" from the stored "10:30:00" (null when there is no time). */
    public static function hm(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    /** "10:30 AM – 11:15 AM", "10:30 AM", or null for a day without a time. */
    public function timeLabel(): ?string
    {
        $fmt = fn(?string $t) => $t ? \Carbon\Carbon::createFromFormat('H:i', self::hm($t))->format('g:i A') : null;

        $start = $fmt($this->start_time);
        $end   = $fmt($this->end_time);

        if ($start && $end) {
            return $start . ' – ' . $end;
        }

        return $start ?: ($end ? 'by ' . $end : null);
    }
}
