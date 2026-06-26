<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Announcement;
use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
use App\Models\Calendar\TimeTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'announcement_name'    => $a->announcement_name,
            'announcement_content' => $a->announcement_content,
            'image_url'            => $this->absUrl($a->announcement_image),
            'pdf_url'              => $this->absUrl($a->announcement_pdf),
            'creator_name'         => $a->user->name ?? 'Unknown',
            'created_at'           => $a->created_at?->toIso8601String(),
        ];
    }

    /** GET /admin/announcements?type=&days= */
    public function announcements(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = Announcement::with('user:id,name')
            ->where('organization_id', $user->organization_id)
            ->latest();

        if ($request->filled('type') && in_array($request->type, ['all', 'user', 'teacher'], true)) {
            $query->where('type', $request->type);
        }
        if ($request->filled('days')) {
            $query->where('created_at', '>=', Carbon::now()->subDays((int) $request->days));
        }

        $items = $query->limit(100)->get()->map(fn ($a) => $this->shapeAnnouncement($a));

        $base = Announcement::where('organization_id', $user->organization_id);
        $stats = [
            'total'      => (clone $base)->count(),
            'this_month' => (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return $this->success(['announcements' => $items, 'stats' => $stats], 'Announcements fetched.');
    }

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
        if ($err = $this->validateWith($request, [
            'announcement_name'    => 'required|string|max:255',
            'announcement_content' => 'required|string',
            'type'                 => 'required|in:all,user,teacher',
            'file'                 => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
        ])) return $err;

        $data = [
            'organization_id'      => $user->organization_id,
            'user_id'              => $user->id,
            'announcement_name'    => $request->announcement_name,
            'announcement_content' => $request->announcement_content,
            'type'                 => $request->type,
        ];
        $this->applyAnnouncementFile($request, $data, null);

        $a = Announcement::create($data);

        return $this->success($this->shapeAnnouncement($a->load('user:id,name')), 'Announcement created.');
    }

    /** POST /admin/announcements/{id} (multipart update) */
    public function updateAnnouncement(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $a = Announcement::where('organization_id', $user->organization_id)->find($id);
        if (!$a) return $this->error('Announcement not found.', 404);

        if ($err = $this->validateWith($request, [
            'announcement_name'    => 'required|string|max:255',
            'announcement_content' => 'required|string',
            'type'                 => 'required|in:all,user,teacher',
            'file'                 => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
        ])) return $err;

        $data = [
            'announcement_name'    => $request->announcement_name,
            'announcement_content' => $request->announcement_content,
            'type'                 => $request->type,
        ];
        $this->applyAnnouncementFile($request, $data, $a);
        $a->update($data);

        return $this->success($this->shapeAnnouncement($a->fresh('user:id,name')), 'Announcement updated.');
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

    private function eventRules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'date'        => 'required|date_format:Y-m-d',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'is_all_day'  => 'nullable|boolean',
            'event_type'  => 'required|in:' . implode(',', self::EVENT_TYPES),
            'color'       => 'nullable|string|max:20',
        ];
    }

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

    /** POST /admin/calendar/events */
    public function storeEvent(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, $this->eventRules())) return $err;

        $event = TimeTable::create($this->eventPayload($request, $user->organization_id, $user->id));

        return $this->success(['id' => $event->id], 'Event created.');
    }

    /** PUT /admin/calendar/events/{id} */
    public function updateEvent(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $event = TimeTable::where('organization_id', $user->organization_id)->find($id);
        if (!$event) return $this->error('Event not found.', 404);
        if ($err = $this->validateWith($request, $this->eventRules())) return $err;

        $event->update($this->eventPayload($request, $user->organization_id, $event->created_by ?? $user->id));

        return $this->success(['id' => $event->id], 'Event updated.');
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
