<?php

namespace App\Livewire\Admin;

use App\Models\SchoolPushNotification;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\FirebaseNotificationService;
use App\Support\MessagingAudience;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Push Notification: the school's admin sends a notification to the
 * school's own students and teachers, as the Super Admin's Push Notification
 * does to every school. It goes as an app push to their phones and into
 * their notification bell, to:
 *
 *   Students   all of them, a class, or a section
 *   Teachers   all of them
 *   Everyone   every student and teacher
 *   Pick       any students and teachers, found by name, admission no. or phone
 *
 * Only ever people of the admin's own school. Review shows how many people
 * and phones it reaches before it goes; what was sent is listed, can be
 * viewed, and deleted (and taken out of the bells it went to).
 */
class PushNotification extends Component
{
    use WireUiActions, WithPagination;

    /** At most this many people picked by name in one notification. */
    public const MAX_PICKED = 200;

    // ─── Compose ────────────────────────────────────────────────────────────
    public string $title      = '';
    public string $body       = '';
    public string $audience   = 'students';
    public string $standardId = '';
    public string $sectionId  = '';
    /** People picked by name: 's{student_details.id}' / 't{teacher_details.id}'. */
    public array  $picked     = [];
    public string $search     = '';

    public bool $composing  = false;
    public bool $confirming = false;
    public ?int $previewRecipients = null;
    public ?int $previewDevices    = null;

    // ─── Sent ───────────────────────────────────────────────────────────────
    public ?int $viewingId       = null;
    public ?int $deletingId      = null;
    public bool $deleteFromBells = true;

    protected function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    public function openCompose(): void
    {
        $this->resetErrorBag();
        $this->composing = true;
    }

    /** Closing keeps what was typed, so reopening carries on from there. */
    public function closeCompose(): void
    {
        $this->composing  = false;
        $this->confirming = false;
    }

    public function updatedAudience(): void
    {
        $this->standardId = '';
        $this->sectionId  = '';
        $this->search     = '';
        $this->confirming = false;
        $this->resetErrorBag('audience');
    }

    public function updatedStandardId(): void
    {
        $this->sectionId  = '';
        $this->confirming = false;
    }

    public function updatedSectionId(): void
    {
        $this->confirming = false;
    }

    // ─── Pick by name ───────────────────────────────────────────────────────

    public function pick(string $key): void
    {
        if (in_array($key, $this->picked, true) || count($this->picked) >= self::MAX_PICKED) {
            return;
        }
        if ($this->people([$key]) === []) {
            return;   // not one of this school's
        }

        $this->picked[]   = $key;
        $this->search     = '';
        $this->confirming = false;
        $this->resetErrorBag('audience');
    }

    public function unpick(string $key): void
    {
        $this->picked     = array_values(array_diff($this->picked, [$key]));
        $this->confirming = false;
    }

    /**
     * This school's students and teachers among $keys, in the order given:
     * key => ['key', 'name', 'meta', 'user_id', 'type'].
     */
    private function people(array $keys): array
    {
        $orgId      = $this->orgId();
        $studentIds = [];
        $teacherIds = [];
        foreach ($keys as $key) {
            if (!preg_match('/^([st])(\d+)$/', (string) $key, $m)) {
                continue;
            }
            if ($m[1] === 's') {
                $studentIds[] = (int) $m[2];
            } else {
                $teacherIds[] = (int) $m[2];
            }
        }

        $found = [];
        if ($studentIds) {
            StudentDetail::with(['user:id,name', 'standard:id,name', 'section:id,name'])
                ->where('organization_id', $orgId)->whereNotNull('user_id')->whereIn('id', $studentIds)
                ->get(['id', 'user_id', 'full_name', 'admission_no', 'standard_id', 'section_id'])
                ->each(function ($s) use (&$found) {
                    $found['s' . $s->id] = $this->studentRow($s);
                });
        }
        if ($teacherIds) {
            TeacherDetail::with('user:id,name')
                ->where('organization_id', $orgId)->whereNotNull('user_id')->whereIn('id', $teacherIds)
                ->get(['id', 'user_id', 'employee_id', 'phone'])
                ->each(function ($t) use (&$found) {
                    $found['t' . $t->id] = $this->teacherRow($t);
                });
        }

        $out = [];
        foreach ($keys as $key) {
            if (isset($found[$key])) {
                $out[$key] = $found[$key];
            }
        }

        return $out;
    }

    private function studentRow(StudentDetail $s): array
    {
        $class = trim(($s->standard?->name ?? '') . ($s->section?->name ? ' · ' . $s->section->name : ''));

        return [
            'key'     => 's' . $s->id,
            'type'    => 'Student',
            'name'    => $s->full_name ?: ($s->user?->name ?? 'Student'),
            'meta'    => implode(' · ', array_filter([$class, $s->admission_no ? 'Adm. ' . $s->admission_no : null])),
            'user_id' => (int) $s->user_id,
        ];
    }

    private function teacherRow(TeacherDetail $t): array
    {
        return [
            'key'     => 't' . $t->id,
            'type'    => 'Teacher',
            'name'    => $t->user?->name ?? 'Teacher',
            'meta'    => implode(' · ', array_filter([$t->employee_id ? 'Emp. ' . $t->employee_id : null, $t->phone])),
            'user_id' => (int) $t->user_id,
        ];
    }

    /** Up to 8 students and 8 teachers of this school matching the search, not yet picked. */
    private function searchResults(): array
    {
        $term = trim($this->search);
        if ($this->audience !== 'selected' || mb_strlen($term) < 2) {
            return [];
        }

        $orgId = $this->orgId();
        $like  = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
        $out   = [];

        StudentDetail::with(['user:id,name', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)->whereNotNull('user_id')
            ->where(fn ($q) => $q->where('full_name', 'like', $like)
                ->orWhere('admission_no', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)))
            ->orderBy('full_name')->limit(8)
            ->get(['id', 'user_id', 'full_name', 'admission_no', 'standard_id', 'section_id'])
            ->each(function ($s) use (&$out) {
                $out[] = $this->studentRow($s);
            });

        TeacherDetail::with('user:id,name')
            ->where('organization_id', $orgId)->whereNotNull('user_id')
            ->where(fn ($q) => $q->where('phone', 'like', $like)
                ->orWhere('employee_id', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)))
            ->limit(8)
            ->get(['id', 'user_id', 'employee_id', 'phone'])
            ->each(function ($t) use (&$out) {
                $out[] = $this->teacherRow($t);
            });

        return array_values(array_filter($out, fn ($p) => !in_array($p['key'], $this->picked, true)));
    }

    // ─── Who it goes to ─────────────────────────────────────────────────────

    /** The users it reaches: every one of them this school's student or teacher with a login. */
    private function recipientUserIds(): array
    {
        if ($this->audience === 'selected') {
            $ids = array_column($this->people($this->picked), 'user_id');
        } else {
            $ids = array_column(
                MessagingAudience::recipients($this->orgId(), $this->audience, (int) $this->standardId ?: null, (int) $this->sectionId ?: null),
                'user_id',
            );
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function audienceLabel(): string
    {
        if ($this->audience !== 'selected') {
            return MessagingAudience::label($this->orgId(), $this->audience, (int) $this->standardId ?: null, (int) $this->sectionId ?: null);
        }

        $names = array_column($this->people($this->picked), 'name');
        $shown = array_slice($names, 0, 3);
        $more  = count($names) - count($shown);

        return Str::limit(implode(', ', $shown), 200) . ($more > 0 ? ' +' . $more . ' more' : '');
    }

    private function validateMessage(): void
    {
        $this->validate([
            'title'      => 'required|string|max:100',
            'body'       => 'required|string|max:500',
            'audience'   => 'required|in:' . implode(',', array_keys(SchoolPushNotification::AUDIENCES)),
            'standardId' => 'nullable|integer',
            'sectionId'  => 'nullable|integer',
        ], [], ['body' => 'message', 'standardId' => 'class', 'sectionId' => 'section']);

        if ($this->audience === 'students' && $this->standardId !== ''
            && !Standard::where('organization_id', $this->orgId())->whereKey((int) $this->standardId)->exists()) {
            $this->addError('standardId', 'Pick one of the school\'s classes.');
        }
        if ($this->audience === 'students' && $this->sectionId !== ''
            && !Section::where('standard_id', (int) $this->standardId)->whereKey((int) $this->sectionId)->exists()) {
            $this->addError('sectionId', 'Pick a section of the class.');
        }
        if ($this->audience === 'selected' && $this->people($this->picked) === []) {
            $this->addError('audience', 'Pick at least one student or teacher.');
        }
    }

    /** The first press: checked, and how many it reaches shown before it goes. */
    public function review(): void
    {
        $this->validateMessage();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $userIds = $this->recipientUserIds();
        if ($userIds === []) {
            $this->addError('audience', 'Nobody here has a login to send to.');
            return;
        }

        $appUserIds              = FirebaseNotificationService::pushableUserIds($userIds);
        $this->previewRecipients = count($userIds);
        $this->previewDevices    = $appUserIds ? UserFcmToken::whereIn('user_id', $appUserIds)->count() : 0;
        $this->confirming        = true;
    }

    public function cancelReview(): void
    {
        $this->confirming = false;
    }

    /** The second press: to the phones, into the bells, and onto the list. */
    public function send(): void
    {
        if (!$this->confirming) {
            return;
        }
        $this->validateMessage();
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->confirming = false;
            return;
        }

        $userIds = $this->recipientUserIds();
        if ($userIds === []) {
            $this->confirming = false;
            $this->addError('audience', 'Nobody here has a login to send to.');
            return;
        }

        $title       = trim($this->title);
        $body        = trim($this->body);
        $appUserIds  = FirebaseNotificationService::pushableUserIds($userIds);
        $deviceCount = $appUserIds ? UserFcmToken::whereIn('user_id', $appUserIds)->count() : 0;
        $delivered   = false;

        // The app push is fail-open: without Firebase set up (or with it
        // failing) the bells and the list still get it.
        try {
            $delivered = (bool) app(FirebaseNotificationService::class)->notifyUserIds($userIds, 'promo', [
                'title' => $title,
                'body'  => $body,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $webCount = $this->storeWebNotifications($userIds, $title, $body);

        SchoolPushNotification::create([
            'organization_id'  => $this->orgId(),
            'title'            => $title,
            'body'             => $body,
            'audience'         => $this->audience,
            'standard_id'      => $this->audience === 'students' && $this->standardId !== '' ? (int) $this->standardId : null,
            'section_id'       => $this->audience === 'students' && $this->sectionId !== '' ? (int) $this->sectionId : null,
            'audience_label'   => $this->audienceLabel(),
            'user_ids'         => $userIds,
            'recipient_count'  => count($userIds),
            'device_count'     => $deviceCount,
            'web_count'        => $webCount,
            'device_breakdown' => $this->deviceBreakdown($appUserIds),
            'delivered'        => $delivered,
            'sent_by'          => Auth::id(),
        ]);

        $this->reset(['title', 'body', 'standardId', 'sectionId', 'picked', 'search', 'confirming', 'composing', 'previewRecipients', 'previewDevices']);
        $this->audience = 'students';
        $this->resetPage();

        $this->notification()->success($delivered
            ? 'Sent to ' . number_format($deviceCount) . ' phone(s) and ' . number_format(count($userIds)) . ' notification bell(s).'
            : 'Sent to ' . number_format(count($userIds)) . ' notification bell(s); no phones could be reached.');
    }

    /** Phones it reached, per platform. */
    private function deviceBreakdown(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return UserFcmToken::whereIn('user_id', $userIds)
            ->selectRaw("COALESCE(NULLIF(platform, ''), 'unknown') as platform, COUNT(*) as total")
            ->groupBy('platform')
            ->pluck('total', 'platform')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** The bell's copy, as the Super Admin's push writes it. */
    private static function bellData(string $title, string $body): string
    {
        return json_encode(['type' => 'promo', 'title' => $title, 'body' => $body]);
    }

    /** One bell notification per user, written in chunks. */
    private function storeWebNotifications(array $userIds, string $title, string $body): int
    {
        $type = (new User())->getMorphClass();
        $now  = now();
        $data = self::bellData($title, $body);
        $done = 0;

        foreach (array_chunk($userIds, 500) as $chunk) {
            DB::table('notifications')->insert(array_map(fn ($uid) => [
                'id'              => (string) Str::uuid(),
                'type'            => 'promo',
                'notifiable_type' => $type,
                'notifiable_id'   => $uid,
                'data'            => $data,
                'read_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ], $chunk));
            $done += count($chunk);
        }

        return $done;
    }

    // ─── Sent: view and delete ──────────────────────────────────────────────

    private function own()
    {
        return SchoolPushNotification::where('organization_id', $this->orgId());
    }

    public function viewCampaign(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->deleteFromBells = true;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    /** Off the list and, when asked, out of the bells it went to. Phones keep what they showed. */
    public function deleteCampaign(): void
    {
        $sent = $this->own()->find($this->deletingId);
        $this->deletingId = null;
        if (!$sent) {
            return;
        }

        $removed = 0;
        if ($this->deleteFromBells) {
            $data = self::bellData($sent->title, $sent->body);
            foreach (array_chunk((array) $sent->user_ids, 500) as $chunk) {
                $removed += DB::table('notifications')
                    ->where('type', 'promo')
                    ->where('data', $data)
                    ->whereIn('notifiable_id', $chunk)
                    ->whereBetween('created_at', [$sent->created_at->copy()->subMinutes(15), $sent->created_at->copy()->addMinute()])
                    ->delete();
            }
        }

        if ($this->viewingId === $sent->id) {
            $this->viewingId = null;
        }
        $sent->delete();

        $this->notification()->success('Notification deleted' . ($removed > 0 ? ' and taken out of ' . number_format($removed) . ' bell(s).' : '.'));
    }

    public function render()
    {
        $orgId = $this->orgId();

        return view('livewire.admin.push-notification', [
            'sent'      => $this->own()->with('sender:id,name')->latest('id')->paginate(10),
            'viewing'   => $this->viewingId ? $this->own()->with('sender:id,name')->find($this->viewingId) : null,
            'standards' => $this->composing ? Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name']) : collect(),
            'sections'  => $this->composing && $this->standardId !== ''
                ? Section::where('standard_id', (int) $this->standardId)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'pickedPeople' => $this->audience === 'selected' ? $this->people($this->picked) : [],
            'results'      => $this->composing ? $this->searchResults() : [],
        ]);
    }
}
