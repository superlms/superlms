<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\PushNotificationCampaign;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class PushNotification extends Component
{
    use WithPagination;

    /** School-admin accounts live on the users table under these roles. */
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    // ─── Compose form ──────────────────────────────────────────────────────
    public string $title          = '';
    public string $body           = '';
    public string $audienceScope  = 'all';  // all | organization
    public ?int   $organizationId = null;
    /** Multi-select audience: any of students | teachers | admins. */
    public array  $audienceRoles  = ['students', 'teachers'];
    public string $screen         = '';

    // ─── Review / send ─────────────────────────────────────────────────────
    public bool $confirming        = false;
    public ?int $previewRecipients = null;
    public ?int $previewDevices    = null;

    // ─── History detail ────────────────────────────────────────────────────
    public ?int $viewingId = null;

    public function mount(): void
    {
        if ($lockedOrgId = $this->lockedOrganizationId()) {
            $this->audienceScope  = 'organization';
            $this->organizationId = $lockedOrgId;
        }
    }

    /** A sub-super-admin scoped to one school can't broadcast beyond it. */
    private function lockedOrganizationId(): ?int
    {
        $user = Auth::user();

        return $user->isSubSuperAdmin() ? $user->allowedOrganizationId() : null;
    }

    public function updatedAudienceScope(): void
    {
        $this->confirming = false;
        if ($this->audienceScope === 'all') {
            $this->organizationId = null;
        }
    }

    public function updatedOrganizationId(): void
    {
        $this->confirming = false;
    }

    public function updatedAudienceRoles(): void
    {
        $this->confirming = false;
    }

    /** Toggle a role in/out of the multi-select audience. */
    public function toggleRole(string $role): void
    {
        if (!array_key_exists($role, PushNotificationCampaign::ROLE_LABELS)) {
            return;
        }

        if (in_array($role, $this->audienceRoles, true)) {
            $this->audienceRoles = array_values(array_diff($this->audienceRoles, [$role]));
        } else {
            $this->audienceRoles[] = $role;
        }

        $this->confirming = false;
    }

    /** Distinct user ids matching the current audience selection. */
    private function recipientUserIds(): Collection
    {
        if ($this->audienceScope === 'organization' && !$this->organizationId) {
            return collect();
        }
        if (empty($this->audienceRoles)) {
            return collect();
        }

        $orgId = $this->audienceScope === 'organization' ? $this->organizationId : null;

        $ids = collect();

        if (in_array('students', $this->audienceRoles, true)) {
            $ids = $ids->merge(
                StudentDetail::when($orgId, fn ($q) => $q->where('organization_id', $orgId))
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            );
        }

        if (in_array('teachers', $this->audienceRoles, true)) {
            $ids = $ids->merge(
                TeacherDetail::when($orgId, fn ($q) => $q->where('organization_id', $orgId))
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            );
        }

        if (in_array('admins', $this->audienceRoles, true)) {
            $ids = $ids->merge(
                User::whereIn('role', self::ADMIN_ROLES)
                    ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
                    ->pluck('id')
            );
        }

        return $ids->filter()->unique()->values();
    }

    /**
     * Validate the compose form and show the recipient-count confirmation
     * step — sending is a broadcast to real people's phones, so it never
     * fires straight off the compose form.
     */
    public function review(): void
    {
        $this->validate([
            'title'          => 'required|string|max:100',
            'body'           => 'required|string|max:500',
            'audienceScope'  => 'required|in:all,organization',
            'organizationId' => $this->audienceScope === 'organization'
                ? 'required|exists:organizations,id'
                : 'nullable',
            'audienceRoles'   => 'required|array|min:1',
            'audienceRoles.*' => 'in:students,teachers,admins',
            'screen'          => 'nullable|string|max:100',
        ], [
            'audienceRoles.required' => 'Pick at least one audience (students, teachers or admins).',
            'audienceRoles.min'      => 'Pick at least one audience (students, teachers or admins).',
        ]);

        $userIds = $this->recipientUserIds();
        $this->previewRecipients = $userIds->count();
        $this->previewDevices    = $userIds->isEmpty()
            ? 0
            : UserFcmToken::whereIn('user_id', $userIds)->count();

        $this->confirming = true;
    }

    public function cancelReview(): void
    {
        $this->confirming = false;
    }

    public function send(): void
    {
        if (!$this->confirming) {
            return;
        }

        $userIds     = $this->recipientUserIds()->all();
        $deviceCount = empty($userIds) ? 0 : UserFcmToken::whereIn('user_id', $userIds)->count();
        $breakdown   = $this->deviceBreakdown($userIds);
        $delivered   = false;
        $webCount    = 0;

        try {
            if (!empty($userIds)) {
                // App: data-only FCM push to every registered device.
                $delivered = app(FirebaseNotificationService::class)->notifyUserIds(
                    $userIds,
                    'promo',
                    [
                        'title'  => $this->title,
                        'body'   => $this->body,
                        'screen' => $this->screen ?: null,
                    ]
                );

                // Web: drop it into each recipient's in-app notification bell.
                $webCount = $this->storeWebNotifications($userIds);
            }

            PushNotificationCampaign::create([
                'title'            => $this->title,
                'body'             => $this->body,
                'audience_scope'   => $this->audienceScope,
                'audience_role'    => $this->legacyRoleValue(),
                'audience_roles'   => array_values($this->audienceRoles),
                'organization_id'  => $this->audienceScope === 'organization' ? $this->organizationId : null,
                'screen'           => $this->screen ?: null,
                'recipient_count'  => count($userIds),
                'device_count'     => $deviceCount,
                'web_count'        => $webCount,
                'device_breakdown' => $breakdown,
                'delivered'        => $delivered,
                'sent_by'          => Auth::id() ?: 0,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Failed to send notification: ' . $e->getMessage(),
            ]);
            return;
        }

        $this->dispatch('notify', [
            'type'    => empty($userIds) ? 'error' : 'success',
            'message' => match (true) {
                empty($userIds) => 'No matching recipients found for that audience.',
                $delivered      => "Sent to {$deviceCount} device(s) and " . count($userIds) . ' inbox(es).',
                default         => 'Delivered to ' . count($userIds) . ' web inbox(es); no reachable app devices.',
            },
        ]);

        $lockedOrgId = $this->lockedOrganizationId();

        $this->reset(['title', 'body', 'screen']);
        $this->audienceScope      = $lockedOrgId ? 'organization' : 'all';
        $this->organizationId     = $lockedOrgId;
        $this->audienceRoles      = ['students', 'teachers'];
        $this->confirming         = false;
        $this->previewRecipients  = null;
        $this->previewDevices     = null;
        $this->resetPage();
    }

    /** Per-platform device reach for the audience, captured at send time. */
    private function deviceBreakdown(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return UserFcmToken::whereIn('user_id', $userIds)
            ->selectRaw("COALESCE(NULLIF(platform, ''), 'unknown') as platform, COUNT(*) as total")
            ->groupBy('platform')
            ->pluck('total', 'platform')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Write a database notification per recipient so it appears in the web
     * header bell. Bulk-inserted in chunks to stay cheap for large audiences.
     *
     * @return int number of inbox rows written
     */
    private function storeWebNotifications(array $userIds): int
    {
        $notifiableType = (new User())->getMorphClass();
        $now  = now();
        $data = json_encode(array_filter([
            'type'   => 'promo',
            'title'  => $this->title,
            'body'   => $this->body,
            'screen' => $this->screen ?: null,
        ], fn ($v) => $v !== null));

        $written = 0;

        foreach (array_chunk($userIds, 500) as $chunk) {
            $rows = array_map(fn ($uid) => [
                'id'              => (string) Str::uuid(),
                'type'            => 'promo',
                'notifiable_type' => $notifiableType,
                'notifiable_id'   => $uid,
                'data'            => $data,
                'read_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ], $chunk);

            DB::table('notifications')->insert($rows);
            $written += count($rows);
        }

        return $written;
    }

    /** Map the multi-select back onto the legacy single-role column. */
    private function legacyRoleValue(): string
    {
        $hasStudents = in_array('students', $this->audienceRoles, true);
        $hasTeachers = in_array('teachers', $this->audienceRoles, true);

        return match (true) {
            $hasStudents && $hasTeachers => 'both',
            $hasStudents                 => 'students',
            $hasTeachers                 => 'teachers',
            default                      => 'admins',
        };
    }

    // ─── History detail ────────────────────────────────────────────────────

    public function viewCampaign(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function render()
    {
        $lockedOrgId = $this->lockedOrganizationId();

        $campaigns = PushNotificationCampaign::with(['organization', 'sender'])
            ->when($lockedOrgId, fn ($q) => $q->where('organization_id', $lockedOrgId))
            ->latest()
            ->paginate(10);

        $viewing = $this->viewingId
            ? PushNotificationCampaign::with(['organization', 'sender'])
                ->when($lockedOrgId, fn ($q) => $q->where('organization_id', $lockedOrgId))
                ->find($this->viewingId)
            : null;

        return view('livewire.super-admin.push-notification', [
            'organizations' => Organization::orderBy('name')->get(),
            'campaigns'     => $campaigns,
            'orgLocked'     => (bool) $lockedOrgId,
            'viewing'       => $viewing,
        ]);
    }
}
