<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Announcement;
use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
use App\Models\Calendar\TimeTable;
use App\Models\Student\Standard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Admin management APIs for the mobile app: Announcements, Calendar events and
 * Enquiries. Mirrors the web admin Livewire screens over the same models.
 */
class AdminContentController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    /** Return a stored value as an absolute URL (handles both full URLs and bare S3 keys). */
    private function absUrl(?string $v): ?string
    {
        if (!$v) return null;
        return str_starts_with($v, 'http') ? $v : Storage::disk('s3')->url($v);
    }

    private function s3Delete(?string $url): void
    {
        if (!$url) return;
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') return;
        try {
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    // ══════════════════════════ ANNOUNCEMENTS ══════════════════════════

    private function shapeAnnouncement(Announcement $a): array
    {
        return [
            'id'                   => $a->id,
            'type'                 => $a->type,
            // null = every class; only set on student announcements.
            'standard_id'          => $a->standard_id,
            'standard_name'        => $a->standard?->name,
            'announcement_name'    => $a->announcement_name,
            'announcement_content' => $a->announcement_content,
            'image_url'            => $this->absUrl($a->announcement_image),
            'pdf_url'              => $this->absUrl($a->announcement_pdf),
            'creator_name'         => $a->user->name ?? 'Unknown',
            'created_at'           => $a->created_at?->toIso8601String(),
        ];
    }

    /**
     * Announcements older than 60 days go, with their files — as the admin
     * panel's Announcement screen trims them each time it draws.
     */
    private function purgeOldAnnouncements(int $orgId): void
    {
        $stale = Announcement::where('organization_id', $orgId)
            ->where('created_at', '<', Carbon::now()->subDays(60))
            ->get();

        foreach ($stale as $row) {
            $this->s3Delete($row->announcement_image);
            $this->s3Delete($row->announcement_pdf);
            $row->delete();
        }
    }

    /**
     * GET /admin/announcements?type=&days=&date=
     *
     * `date` (Y-m-d) is one day's announcements and wins over `days`, as the
     * panel's date filter wins over its period. The school's active classes
     * come along for the "which students" picker and the list's labels.
     */
    public function announcements(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'date' => 'nullable|date_format:Y-m-d',
            'days' => 'nullable|integer|min:1|max:366',
        ])) return $err;

        $orgId = $user->organization_id;
        $this->purgeOldAnnouncements($orgId);

        $query = Announcement::with(['user:id,name', 'standard:id,name'])
            ->where('organization_id', $orgId)
            ->latest();

        if ($request->filled('type') && in_array($request->type, ['all', 'user', 'teacher'], true)) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } elseif ($request->filled('days')) {
            $query->where('created_at', '>=', Carbon::now()->subDays((int) $request->days));
        }

        $items = $query->limit(200)->get()->map(fn ($a) => $this->shapeAnnouncement($a));

        $base = Announcement::where('organization_id', $orgId);
        $lastMonth = now()->subMonthNoOverflow();
        $stats = [
            'total'      => (clone $base)->count(),
            'this_month' => (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'last_month' => (clone $base)->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
        ];

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)
            ->inClassOrder()
            ->get(['id', 'name']);

        return $this->success([
            'announcements' => $items,
            'stats'         => $stats,
            'standards'     => $standards,
        ], 'Announcements fetched.');
    }

    /** The panel's limits: a 1000-character title, 3000 of content, a file of 1 MB. */
    private function announcementRules(int $orgId): array
    {
        return [
            'announcement_name'    => 'required|string|max:1000',
            'announcement_content' => 'required|string|max:3000',
            'type'                 => 'required|in:all,user,teacher',
            'standard_id'          => ['nullable', Rule::exists('standards', 'id')->where('organization_id', $orgId)],
            'file'                 => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:1024',
            'remove_image'         => 'nullable|boolean',
            'remove_pdf'           => 'nullable|boolean',
        ];
    }

    private const ANNOUNCEMENT_MESSAGES = [
        'announcement_name.max'    => 'Title may not be longer than 1000 characters.',
        'announcement_content.max' => 'Content may not be longer than 3000 characters.',
        'file.max'                 => 'Attachment must be 1 MB (1024 KB) or smaller.',
        'file.mimes'               => 'Attachment must be an image or PDF.',
    ];

    /** Store the uploaded file (image or PDF) into the right column on $data. */
    private function applyAnnouncementFile(Request $request, array &$data, ?Announcement $existing): void
    {
        if (!$request->hasFile('file')) return;

        $file  = $request->file('file');
        $ext   = strtolower($file->getClientOriginalExtension());
        $isPdf = $ext === 'pdf' || $file->getMimeType() === 'application/pdf';
        $dir   = $isPdf ? 'admin/announcements/pdfs' : 'admin/announcements/images';

        $path = $file->store($dir, 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        $url = Storage::disk('s3')->url($path);

        if ($isPdf) {
            if ($existing) $this->s3Delete($existing->announcement_pdf);
            $data['announcement_pdf'] = $url;
        } else {
            if ($existing) $this->s3Delete($existing->announcement_image);
            $data['announcement_image'] = $url;
        }
    }

    /** POST /admin/announcements (multipart: announcement_name, announcement_content, type, file?) */
    public function storeAnnouncement(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith(
            $request,
            $this->announcementRules($user->organization_id),
            self::ANNOUNCEMENT_MESSAGES,
        )) return $err;

        $data = [
            'organization_id'      => $user->organization_id,
            'user_id'              => $user->id,
            'announcement_name'    => $request->announcement_name,
            'announcement_content' => $request->announcement_content,
            'type'                 => $request->type,
            // Only a student announcement can be aimed at a class; null = all.
            'standard_id'          => $request->type === 'user' ? ($request->standard_id ?: null) : null,
        ];
        $this->applyAnnouncementFile($request, $data, null);

        $a = Announcement::create($data);

        return $this->success($this->shapeAnnouncement($a->load(['user:id,name', 'standard:id,name'])), 'Announcement created.');
    }

    /**
     * POST /admin/announcements/{id} (multipart update)
     *
     * remove_image / remove_pdf take the file that is there off the
     * announcement (and out of storage), as the panel's cross on it does.
     */
    public function updateAnnouncement(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $a = Announcement::where('organization_id', $user->organization_id)->find($id);
        if (!$a) return $this->error('Announcement not found.', 404);

        if ($err = $this->validateWith(
            $request,
            $this->announcementRules($user->organization_id),
            self::ANNOUNCEMENT_MESSAGES,
        )) return $err;

        $data = [
            'announcement_name'    => $request->announcement_name,
            'announcement_content' => $request->announcement_content,
            'type'                 => $request->type,
            // Only a student announcement can be aimed at a class; null = all.
            'standard_id'          => $request->type === 'user' ? ($request->standard_id ?: null) : null,
        ];
        if ($request->boolean('remove_image') && $a->announcement_image) {
            $this->s3Delete($a->announcement_image);
            $data['announcement_image'] = null;
        }
        if ($request->boolean('remove_pdf') && $a->announcement_pdf) {
            $this->s3Delete($a->announcement_pdf);
            $data['announcement_pdf'] = null;
        }
        $this->applyAnnouncementFile($request, $data, $a);
        $a->update($data);

        return $this->success($this->shapeAnnouncement($a->fresh(['user:id,name', 'standard:id,name'])), 'Announcement updated.');
    }

    /** DELETE /admin/announcements/{id} */
    public function deleteAnnouncement($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $a = Announcement::where('organization_id', $user->organization_id)->find($id);
        if (!$a) return $this->error('Announcement not found.', 404);

        $this->s3Delete($a->announcement_image);
        $this->s3Delete($a->announcement_pdf);
        $a->delete();

        return $this->success(null, 'Announcement deleted.');
    }

    // ══════════════════════════ CALENDAR EVENTS ══════════════════════════

    private const EVENT_TYPES = ['class', 'exam', 'meeting', 'event', 'holiday'];

    private function defaultColor(string $type): string
    {
        return match ($type) {
            'class'   => '#3b82f6',
            'exam'    => '#ef4444',
            'meeting' => '#f59e0b',
            'event'   => '#10b981',
            'holiday' => '#8b5cf6',
            default   => '#6b7280',
        };
    }

    /**
     * The panel's event form: a start and an end time unless the event runs
     * all day, and an image or PDF of up to 1 MB. The title column holds 255
     * characters.
     */
    private function eventRules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'date'        => 'required|date_format:Y-m-d',
            'is_all_day'  => 'nullable|boolean',
            'start_time'  => 'exclude_if:is_all_day,1,true|required|date_format:H:i',
            'end_time'    => 'exclude_if:is_all_day,1,true|required|date_format:H:i',
            'event_type'  => 'required|in:' . implode(',', self::EVENT_TYPES),
            'color'       => 'nullable|string|max:20',
            'attachment'  => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:1024',
        ];
    }

    private const EVENT_MESSAGES = [
        'start_time.required' => 'Pick a start time, or make it an all-day event.',
        'end_time.required'   => 'Pick an end time, or make it an all-day event.',
        'description.max'     => 'Description may not be longer than 3000 characters.',
        'attachment.max'      => 'Attachment must be 1 MB (1024 KB) or smaller.',
        'attachment.mimes'    => 'Attachment must be an image or PDF.',
    ];

    private function eventPayload(Request $request, int $orgId, int $userId): array
    {
        $allDay = filter_var($request->input('is_all_day', false), FILTER_VALIDATE_BOOLEAN);
        return [
            'organization_id' => $orgId,
            'created_by'      => $userId,
            'title'           => $request->title,
            'description'     => $request->description,
            'date'            => $request->date,
            'start_time'      => $allDay ? null : $request->start_time,
            'end_time'        => $allDay ? null : $request->end_time,
            'is_all_day'      => $allDay,
            'event_type'      => $request->event_type,
            'color'           => $request->filled('color') ? $request->color : $this->defaultColor($request->event_type),
            'is_cancelled'    => false,
        ];
    }

    /** A newly chosen attachment goes up and the one it replaces comes down. */
    private function applyEventAttachment(Request $request, array &$data, ?TimeTable $existing): void
    {
        if (!$request->hasFile('attachment') || !Schema::hasColumn('time_tables', 'attachment')) return;

        if ($existing?->attachment) $this->s3Delete($existing->attachment);
        $path = $request->file('attachment')->store('admin/calendar/attachments', 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        $data['attachment'] = Storage::disk('s3')->url($path);
    }

    /** POST /admin/calendar/events (JSON, or multipart with an attachment) */
    public function storeEvent(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, $this->eventRules(), self::EVENT_MESSAGES)) return $err;

        $data = $this->eventPayload($request, $user->organization_id, $user->id);
        $this->applyEventAttachment($request, $data, null);
        $event = TimeTable::create($data);

        return $this->success(['id' => $event->id], 'Event created.');
    }

    /**
     * PUT /admin/calendar/events/{id}, or POST for multipart with an attachment.
     * An event whose day has passed is completed and stays as it was, as in
     * the panel.
     */
    public function updateEvent(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $event = TimeTable::where('organization_id', $user->organization_id)->find($id);
        if (!$event) return $this->error('Event not found.', 404);
        if ($event->date && $event->date->lt(Carbon::today())) {
            return $this->error('This event is already completed and cannot be edited.', 422);
        }
        if ($err = $this->validateWith($request, $this->eventRules(), self::EVENT_MESSAGES)) return $err;

        $data = $this->eventPayload($request, $user->organization_id, $event->created_by ?? $user->id);
        $this->applyEventAttachment($request, $data, $event);
        $event->update($data);

        return $this->success(['id' => $event->id], 'Event updated.');
    }

    /** One event as the admin calendar lists and shows it. */
    private function shapeEvent(TimeTable $e): array
    {
        $acad = $e->academic;
        return [
            'id'            => $e->id,
            'title'         => $e->title,
            'description'   => $e->description,
            'date'          => $e->date?->format('Y-m-d'),
            'start_time'    => $e->start_time?->format('H:i'),
            'end_time'      => $e->end_time?->format('H:i'),
            'is_all_day'    => (bool) $e->is_all_day,
            'event_type'    => $e->event_type,
            'color'         => $e->color ?: $this->defaultColor((string) $e->event_type),
            'attachment'    => $e->attachment ?? null,
            'location'      => $e->location?->location_display,
            'standard'      => $acad?->standard?->name,
            'section'       => $acad?->section?->name,
            'subject'       => $acad?->subject?->name,
            'teacher'       => $acad?->teacher?->name,
            'creator_name'  => $e->creator?->name,
            'is_completed'  => $e->date ? $e->date->lt(Carbon::today()) : false,
        ];
    }

    private function eventsIn(int $orgId, Carbon $from, Carbon $to)
    {
        return TimeTable::where('organization_id', $orgId)
            ->where('is_cancelled', false)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * GET /admin/calendar/month?month=YYYY-MM
     *
     * The month's events, and the panel's counts: today, this week, the month
     * in view and this year.
     */
    public function calendarMonth(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['month' => 'nullable|date_format:Y-m'])) return $err;

        $orgId = $user->organization_id;
        $start = $request->filled('month')
            ? Carbon::createFromFormat('Y-m-d', $request->month . '-01')->startOfDay()
            : Carbon::today()->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $today = Carbon::today();

        $events = $this->eventsIn($orgId, $start, $end)
            ->with(['academic.standard', 'academic.section', 'academic.subject', 'academic.teacher', 'location', 'creator:id,name'])
            ->orderBy('date')->orderBy('start_time')->orderBy('id')
            ->get()
            ->map(fn ($e) => $this->shapeEvent($e));

        return $this->success([
            'month'  => $start->format('Y-m'),
            'events' => $events,
            'stats'  => [
                'today'         => $this->eventsIn($orgId, $today, $today)->count(),
                'this_week'     => $this->eventsIn($orgId, $today->copy()->startOfWeek(), $today->copy()->endOfWeek())->count(),
                'current_month' => $events->count(),
                'this_year'     => $this->eventsIn($orgId, $today->copy()->startOfYear(), $today->copy()->endOfYear())->count(),
                'total'         => TimeTable::where('organization_id', $orgId)->where('is_cancelled', false)->count(),
            ],
        ], 'Calendar fetched.');
    }

    /**
     * GET /admin/calendar/year?year=YYYY — the panel's yearly view: each
     * month's total and how many events fall on each of its days.
     */
    public function calendarYear(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['year' => 'nullable|integer|min:2000|max:2100'])) return $err;

        $year = (int) ($request->year ?: now()->year);
        $byDay = $this->eventsIn($user->organization_id, Carbon::create($year, 1, 1), Carbon::create($year, 12, 31))
            ->get(['id', 'date'])
            ->groupBy(fn ($e) => $e->date->format('Y-m-d'))
            ->map->count();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $key  = sprintf('%04d-%02d', $year, $m);
            $days = $byDay->filter(fn ($n, $day) => str_starts_with($day, $key));
            $months[] = [
                'month' => $m,
                'name'  => Carbon::create($year, $m, 1)->format('F'),
                'total' => $days->sum(),
                'days'  => (object) $days->all(),
            ];
        }

        return $this->success(['year' => $year, 'months' => $months], 'Year fetched.');
    }

    /** DELETE /admin/calendar/events/{id} */
    public function deleteEvent($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $event = TimeTable::where('organization_id', $user->organization_id)->find($id);
        if (!$event) return $this->error('Event not found.', 404);
        $event->delete();

        return $this->success(null, 'Event deleted.');
    }

    // ══════════════════════════ ENQUIRIES ══════════════════════════

    private function enquiryModel(string $tab): string
    {
        return $tab === 'student' ? ContactAdminStudent::class : ContactAdminTeacher::class;
    }

    private function shapeEnquiry($e, string $tab): array
    {
        return [
            'id'          => $e->id,
            'topic'       => $e->topic,
            'query'       => $tab === 'student' ? $e->student_query : $e->teacher_query,
            'image_url'   => $this->absUrl($e->image),
            'admin_text'  => $e->admin_text,
            'replied'     => (bool) $e->admin_reply,
            'user_name'   => $e->user->name ?? 'Unknown',
            'user_email'  => $e->user->email ?? null,
            'created_at'  => $e->created_at?->toIso8601String(),
        ];
    }

    /** GET /admin/enquiries?tab=teacher|student&search=&days=&status= */
    public function enquiries(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $tab     = $request->input('tab') === 'student' ? 'student' : 'teacher';
        $model   = $this->enquiryModel($tab);
        $orgId   = $user->organization_id;
        $queryCol = $tab === 'student' ? 'student_query' : 'teacher_query';

        $q = $model::where('organization_id', $orgId)->with('user:id,name,email');

        if ($request->filled('days')) {
            $q->where('created_at', '>=', Carbon::now()->subDays((int) $request->days));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s, $queryCol) {
                $w->where('topic', 'like', "%$s%")
                    ->orWhere($queryCol, 'like', "%$s%")
                    ->orWhere('admin_text', 'like', "%$s%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
            });
        }
        if ($request->status === 'replied') $q->where('admin_reply', true);
        if ($request->status === 'pending') $q->where('admin_reply', false);

        $items = $q->latest()->limit(100)->get()->map(fn ($e) => $this->shapeEnquiry($e, $tab));

        $base  = $model::where('organization_id', $orgId);
        $stats = [
            'total'   => (clone $base)->count(),
            'pending' => (clone $base)->where('admin_reply', false)->count(),
            'replied' => (clone $base)->where('admin_reply', true)->count(),
        ];

        return $this->success([
            'tab'         => $tab,
            'enquiries'   => $items,
            'stats'       => $stats,
            'tab_totals'  => [
                'teacher' => ContactAdminTeacher::where('organization_id', $orgId)->count(),
                'student' => ContactAdminStudent::where('organization_id', $orgId)->count(),
            ],
        ], 'Enquiries fetched.');
    }

    /** POST /admin/enquiries/{tab}/{id}/reply  (admin_text) */
    public function replyEnquiry(Request $request, $tab, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['admin_text' => 'required|string|min:2'])) return $err;

        $tab   = $tab === 'student' ? 'student' : 'teacher';
        $model = $this->enquiryModel($tab);
        $e     = $model::where('organization_id', $user->organization_id)->with('user:id,name,email')->find($id);
        if (!$e) return $this->error('Enquiry not found.', 404);

        $e->update(['admin_text' => $request->admin_text, 'admin_reply' => true]);

        return $this->success($this->shapeEnquiry($e->fresh('user:id,name,email'), $tab), 'Reply sent.');
    }

    /** DELETE /admin/enquiries/{tab}/{id} */
    public function deleteEnquiry($tab, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $tab   = $tab === 'student' ? 'student' : 'teacher';
        $model = $this->enquiryModel($tab);
        $e     = $model::where('organization_id', $user->organization_id)->find($id);
        if (!$e) return $this->error('Enquiry not found.', 404);

        $this->s3Delete($e->image);
        $e->delete();

        return $this->success(null, 'Enquiry deleted.');
    }
}
