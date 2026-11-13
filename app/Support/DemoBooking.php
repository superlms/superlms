<?php

namespace App\Support;

use App\Models\SuperAdmin\SuperAdminCalendarEvent;
use App\Models\User;
use App\Models\WebsiteDemo;
use App\Services\MeetLink;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * When a website demo can be booked (Super Admin → Calendar → Queries →
 * Booking settings, kept in platform_settings as demo_booking): the days of
 * the week, the hours, how long a demo is, how far ahead and how much notice.
 *
 * A time is free when it is inside those hours, far enough ahead, and clear of
 * every other booked demo and of the timed meetings and events on the
 * calendar the demos go on (owner()). Tasks and all-day items never block one.
 */
class DemoBooking
{
    public const KEY = 'demo_booking';

    public const DEFAULTS = [
        'days'       => [1, 2, 3, 4, 5, 6],   // Carbon dayOfWeek: 0 Sunday … 6 Saturday
        'start'      => '10:00',
        'end'        => '18:00',
        'minutes'    => 30,
        'days_ahead' => 14,
        'notice'     => 3,                    // hours
        'owner_id'   => null,                 // whose calendar the demos go on
    ];

    public const LENGTHS = [15, 30, 45, 60];

    /**
     * The two WhatsApp templates the demo messages use, as they are to be made
     * in Meta (WhatsApp Manager → Message templates), language English: shown
     * under Booking settings and in DEMO_BOOKING.md. The button is a
     * dynamic "Visit website" URL whose {{1}} is the request's booking token.
     */
    public static function whatsAppTemplates(): array
    {
        $base = rtrim((string) config('app.url'), '/') . '/web/demo/book/{{1}}';

        return [
            [
                'name'     => (string) config('services.whatsapp.templates.demo_thanks'),
                'when'     => 'Sent right after someone requests a demo on the website',
                'category' => 'Utility',
                'body'     => "Hi {{1}}, thank you for requesting a SuperLMS demo for {{2}}!\n\n"
                            . "Please pick a date and time that suits you for your 30-minute live demo. Your meeting link is sent to you the moment you confirm.",
                'samples'  => ['Rahul', 'Doon Public School'],
                'button'   => ['Pick a time', $base],
            ],
            [
                'name'     => (string) config('services.whatsapp.templates.demo_confirmed'),
                'when'     => 'Sent the moment a time is picked on the booking page',
                'category' => 'Utility',
                'body'     => "Hi {{1}}, your SuperLMS demo is booked for {{2}}.\n\n"
                            . "Join the meeting here: {{3}}\n\n"
                            . "Please join a minute early from a laptop or phone. See you there!",
                'samples'  => ['Rahul', 'Mon, 16 Nov 2026 at 11:00 AM (IST)', 'https://meet.google.com/abc-defg-hij'],
                'button'   => ['Change time', $base],
            ],
        ];
    }

    public static function settings(): array
    {
        $saved = json_decode((string) Platform::get(self::KEY), true);

        $s = array_merge(self::DEFAULTS, is_array($saved) ? $saved : []);
        $s['days']       = array_values(array_unique(array_filter(array_map('intval', (array) $s['days']), fn ($d) => $d >= 0 && $d <= 6)));
        $s['minutes']    = in_array((int) $s['minutes'], self::LENGTHS, true) ? (int) $s['minutes'] : 30;
        $s['days_ahead'] = max(1, min(60, (int) $s['days_ahead']));
        $s['notice']     = max(0, min(72, (int) $s['notice']));
        $s['start']      = preg_match('/^\d{2}:\d{2}$/', (string) $s['start']) ? $s['start'] : self::DEFAULTS['start'];
        $s['end']        = preg_match('/^\d{2}:\d{2}$/', (string) $s['end']) ? $s['end'] : self::DEFAULTS['end'];
        sort($s['days']);

        return $s;
    }

    public static function save(array $settings): void
    {
        $keep = array_intersect_key($settings, self::DEFAULTS);
        Platform::set(self::KEY, json_encode(array_merge(self::settings(), $keep)));
    }

    /** The user whose calendar a booked demo goes on: the one picked, else the main super admin. */
    public static function ownerId(): ?int
    {
        $picked = (int) (self::settings()['owner_id'] ?? 0);
        if ($picked && User::whereKey($picked)->whereIn('role', ['super-admin', 'sub-super-admin'])->exists()) {
            return $picked;
        }

        return User::where('role', 'super-admin')->orderBy('id')->value('id');
    }

    /**
     * The free times, by day: ['2026-11-16' => ['10:00', '10:30', …], …] —
     * only days with at least one. $except: the demo being (re)booked, whose
     * own meeting does not block it.
     */
    public static function slots(?WebsiteDemo $except = null): array
    {
        $s      = self::settings();
        $len    = $s['minutes'];
        $now    = Carbon::now();
        $first  = $now->copy()->addHours($s['notice']);
        $today  = Carbon::today();
        $last   = $today->copy()->addDays($s['days_ahead']);
        $busy   = self::busy($today, $last, $except);

        $out = [];
        for ($day = $today->copy(); $day->lte($last); $day->addDay()) {
            if (!in_array($day->dayOfWeek, $s['days'], true)) {
                continue;
            }
            $date  = $day->toDateString();
            $start = Carbon::parse($date . ' ' . $s['start']);
            $end   = Carbon::parse($date . ' ' . $s['end']);

            for ($t = $start->copy(); $t->copy()->addMinutes($len)->lte($end); $t->addMinutes($len)) {
                if ($t->lt($first)) {
                    continue;
                }
                $tEnd = $t->copy()->addMinutes($len);
                foreach ($busy[$date] ?? [] as [$bStart, $bEnd]) {
                    if ($t->lt($bEnd) && $tEnd->gt($bStart)) {
                        continue 2;
                    }
                }
                $out[$date][] = $t->format('H:i');
            }
        }

        return $out;
    }

    public static function isFree(string $date, string $time, ?WebsiteDemo $except = null): bool
    {
        return in_array($time, self::slots($except)[$date] ?? [], true);
    }

    /**
     * Book (or move) the demo at a free time: its Meet link is made there and
     * then (MeetLink), it goes on the owner's calendar, and the request turns
     * Booked. False when the time is no longer free. One booking at a time, so
     * two people never get the same slot.
     *
     * @return array{ok: bool, moved: bool}
     */
    public static function book(WebsiteDemo $demo, string $date, string $time): array
    {
        try {
            return Cache::lock('website-demo-booking', 30)->block(15, function () use ($demo, $date, $time) {
                $demo->refresh();

                if (in_array($demo->statusKey(), ['completed', 'cancelled'], true) || !self::isFree($date, $time, $demo)) {
                    return ['ok' => false, 'moved' => false];
                }

                $len   = self::settings()['minutes'];
                $start = Carbon::parse($date . ' ' . $time);
                $moved = $demo->statusKey() === 'booked' && $demo->meeting_at !== null && $demo->meet_link;

                $meeting = null;
                if ($moved && $demo->meet_provider === 'google' && $demo->google_event_id
                    && MeetLink::move($demo->google_event_id, $start, $len)) {
                    $meeting = ['link' => $demo->meet_link, 'provider' => 'google', 'event_id' => $demo->google_event_id];
                } elseif ($moved && $demo->meet_provider !== 'google') {
                    $meeting = ['link' => $demo->meet_link, 'provider' => $demo->meet_provider ?: 'jitsi', 'event_id' => null];
                } else {
                    if ($demo->google_event_id) {
                        MeetLink::cancel($demo->google_event_id);
                    }
                    $meeting = MeetLink::create(
                        Platform::details()['platform_name'] . ' demo — ' . ($demo->school_name ?: $demo->full_name),
                        self::notes($demo),
                        $start,
                        $len,
                        filter_var((string) $demo->email, FILTER_VALIDATE_EMAIL) ? (string) $demo->email : null,
                    );
                }

                $demo->fill([
                    'status'          => 'booked',
                    'meeting_at'      => $start,
                    'meeting_minutes' => $len,
                    'meet_link'       => $meeting['link'],
                    'meet_provider'   => $meeting['provider'],
                    'google_event_id' => $meeting['event_id'],
                    'booked_at'       => now(),
                ])->save();

                self::putOnCalendar($demo);

                return ['ok' => true, 'moved' => (bool) $moved];
            });
        } catch (LockTimeoutException) {
            return ['ok' => false, 'moved' => false];
        }
    }

    /** Super Admin → Queries: the demo called off — off the calendar, and off Google's (the guest is told). */
    public static function cancel(WebsiteDemo $demo): void
    {
        if ($demo->google_event_id) {
            MeetLink::cancel($demo->google_event_id);
        }
        self::removeFromCalendar($demo);

        $demo->update(['status' => 'cancelled', 'google_event_id' => null, 'calendar_event_id' => null]);
    }

    /** Super Admin → Queries: open again — no meeting, and the booking link works again. */
    public static function reopen(WebsiteDemo $demo): void
    {
        if ($demo->statusKey() === 'booked') {
            return;
        }
        if ($demo->google_event_id) {
            MeetLink::cancel($demo->google_event_id);
        }
        self::removeFromCalendar($demo);

        $demo->update([
            'status' => 'pending', 'meeting_at' => null, 'meeting_minutes' => null, 'meet_link' => null,
            'meet_provider' => null, 'google_event_id' => null, 'calendar_event_id' => null, 'booked_at' => null,
        ]);
    }

    /** The booked demo as a meeting on the owner's calendar (made, or moved with it). */
    public static function putOnCalendar(WebsiteDemo $demo): void
    {
        $owner = self::ownerId();
        if (!$owner || !$demo->meeting_at) {
            return;
        }

        $data = [
            'user_id'    => $owner,
            'type'       => 'meeting',
            'title'      => 'Demo — ' . ($demo->school_name ?: $demo->full_name),
            'date'       => $demo->meeting_at->toDateString(),
            'start_time' => $demo->meeting_at->format('H:i:s'),
            'end_time'   => $demo->meetingEnd()->format('H:i:s'),
            'with_whom'  => mb_strimwidth(trim($demo->full_name . ($demo->role ? ' (' . $demo->role . ')' : '') . ($demo->phone ? ' · ' . $demo->phone : '')), 0, 191, '…'),
            'location'   => $demo->meet_link,
            'notes'      => self::notes($demo),
        ];

        $row = $demo->calendar_event_id ? SuperAdminCalendarEvent::find($demo->calendar_event_id) : null;
        if ($row) {
            $row->update($data);
        } else {
            $row = SuperAdminCalendarEvent::create($data);
            $demo->forceFill(['calendar_event_id' => $row->id])->saveQuietly();
        }
    }

    private static function removeFromCalendar(WebsiteDemo $demo): void
    {
        if ($demo->calendar_event_id) {
            SuperAdminCalendarEvent::whereKey($demo->calendar_event_id)->delete();
        }
    }

    private static function notes(WebsiteDemo $demo): string
    {
        return collect([
            'Booked from the website (Calendar → Queries).',
            $demo->school_name ? 'School: ' . $demo->school_name . ($demo->city ? ', ' . $demo->city : '') : null,
            $demo->no_of_students ? 'Students: ' . $demo->no_of_students : null,
            $demo->email ? 'Email: ' . $demo->email : null,
            $demo->phone ? 'Phone: ' . $demo->phone : null,
        ])->filter()->implode("\n");
    }

    /** [date => [[start, end], …]] — booked demos and timed meetings / events on the owner's calendar. */
    private static function busy(Carbon $from, Carbon $to, ?WebsiteDemo $except): array
    {
        $busy = [];

        $demos = WebsiteDemo::query()
            ->where('status', 'booked')
            ->whereNotNull('meeting_at')
            ->whereBetween('meeting_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->id))
            ->get(['id', 'meeting_at', 'meeting_minutes', 'calendar_event_id']);

        $ownCalendarRows = [];
        foreach ($demos as $d) {
            $busy[$d->meeting_at->toDateString()][] = [$d->meeting_at->copy(), $d->meetingEnd()];
            if ($d->calendar_event_id) {
                $ownCalendarRows[] = (int) $d->calendar_event_id;
            }
        }
        if ($except?->calendar_event_id) {
            $ownCalendarRows[] = (int) $except->calendar_event_id;
        }

        $owner = self::ownerId();
        if ($owner) {
            $events = SuperAdminCalendarEvent::query()->ownedBy($owner)
                ->whereIn('type', ['meeting', 'event'])
                ->whereNotNull('start_time')
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString())
                ->when($ownCalendarRows, fn ($q) => $q->whereNotIn('id', $ownCalendarRows))
                ->get(['id', 'date', 'start_time', 'end_time']);

            foreach ($events as $e) {
                $date  = $e->date->toDateString();
                $start = Carbon::parse($date . ' ' . SuperAdminCalendarEvent::hm($e->start_time));
                $end   = $e->end_time ? Carbon::parse($date . ' ' . SuperAdminCalendarEvent::hm($e->end_time)) : $start->copy()->addMinutes(30);
                if ($end->lte($start)) {
                    $end = $start->copy()->addMinutes(30);
                }
                $busy[$date][] = [$start, $end];
            }
        }

        return $busy;
    }
}
