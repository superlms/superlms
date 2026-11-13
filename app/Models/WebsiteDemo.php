<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A demo request from the website (or a "Schedule a call"). A demo request is
 * thanked with a private link (booking_token) where the person picks a free
 * time; the meeting then has its Meet link (App\Http\Controllers\DemoBookingController).
 * Super Admin → Calendar → Queries lists them with their status.
 */
class WebsiteDemo extends Model
{
    /** status => label. A row from before statuses reads as pending. */
    public const STATUSES = [
        'pending'   => 'Pending',
        'booked'    => 'Booked',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'full_name',
        'school_name',
        'phone',
        'email',
        'city',
        'no_of_students',
        'role',
        'preferred_date',
        'preferred_time',
        'remark',
        'status',
        'booking_token',
        'meeting_at',
        'meeting_minutes',
        'meet_link',
        'meet_provider',
        'google_event_id',
        'calendar_event_id',
        'booked_at',
        'messages',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'meeting_at'     => 'datetime',
        'booked_at'      => 'datetime',
        'messages'       => 'array',
    ];

    public function statusKey(): string
    {
        return array_key_exists((string) $this->status, self::STATUSES) ? (string) $this->status : 'pending';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->statusKey()];
    }

    /** A "Schedule a call" request (a phone call at the time they chose), not a demo to book. */
    public function isCallRequest(): bool
    {
        return $this->preferred_date !== null;
    }

    /** The private link where the meeting time is picked (a token made the first time it is asked for). */
    public function bookingUrl(): string
    {
        return route('website.demo.book', $this->ensureToken());
    }

    public function ensureToken(): string
    {
        if (!$this->booking_token) {
            $this->booking_token = Str::random(40);
            if ($this->exists) {
                $this->saveQuietly();
            }
        }

        return $this->booking_token;
    }

    /** When the booked meeting ends. */
    public function meetingEnd(): ?\Carbon\Carbon
    {
        return $this->meeting_at?->copy()->addMinutes((int) ($this->meeting_minutes ?: 30));
    }

    /** "Mon, 16 Nov 2026 · 11:00 AM – 11:30 AM" */
    public function meetingLabel(bool $withEnd = true): ?string
    {
        if (!$this->meeting_at) {
            return null;
        }

        return $this->meeting_at->format('D, d M Y · g:i A') . ($withEnd ? ' – ' . $this->meetingEnd()->format('g:i A') : '');
    }

    /** Note what was sent: ['type' => thanks|confirmed, 'email' => ok|why, 'whatsapp' => ok|why]. */
    public function logMessage(array $entry): void
    {
        $log   = (array) ($this->messages ?? []);
        $log[] = $entry + ['at' => now()->toDateTimeString()];

        $this->messages = array_slice($log, -20);
        $this->saveQuietly();
    }
}
