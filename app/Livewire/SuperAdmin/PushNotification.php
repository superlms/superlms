<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\PushNotificationCampaign;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\UserFcmToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PushNotification extends Component
{
    use WithPagination;

    // ─── Compose form ──────────────────────────────────────────────────────
    public string $title         = '';
    public string $body          = '';
    public string $audienceScope = 'all';  // all | organization
    public ?int   $organizationId = null;
    public string $audienceRole  = 'both'; // students | teachers | both
    public string $screen        = '';

    // ─── Review / send ─────────────────────────────────────────────────────
    public bool $confirming        = false;
    public ?int $previewRecipients = null;
    public ?int $previewDevices    = null;

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

    public function updatedAudienceRole(): void
    {
        $this->confirming = false;
    }

    /** Distinct user ids matching the current audience selection. */
    private function recipientUserIds(): Collection
    {
        if ($this->audienceScope === 'organization' && !$this->organizationId) {
            return collect();
        }

        $orgId = $this->audienceScope === 'organization' ? $this->organizationId : null;

        $studentIds = collect();
        $teacherIds = collect();

        if (in_array($this->audienceRole, ['students', 'both'], true)) {
            $studentIds = StudentDetail::when($orgId, fn($q) => $q->where('organization_id', $orgId))
                ->whereNotNull('user_id')
                ->pluck('user_id');
        }

        if (in_array($this->audienceRole, ['teachers', 'both'], true)) {
            $teacherIds = TeacherDetail::when($orgId, fn($q) => $q->where('organization_id', $orgId))
                ->whereNotNull('user_id')
                ->pluck('user_id');
        }

        return $studentIds->merge($teacherIds)->unique()->values();
    }

    /**
     * Validate the compose form and show the recipient-count confirmation
     * step — sending is a broadcast to real people's phones, so it never
     * fires straight off the compose form.
     */
    public function review(): void
    {
        $this->validate([
            'title'         => 'required|string|max:100',
            'body'          => 'required|string|max:500',
            'audienceScope' => 'required|in:all,organization',
            'organizationId' => $this->audienceScope === 'organization'
                ? 'required|exists:organizations,id'
                : 'nullable',
            'audienceRole'  => 'required|in:students,teachers,both',
            'screen'        => 'nullable|string|max:100',
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
        $delivered   = false;

        try {
            if (!empty($userIds)) {
                $delivered = app(FirebaseNotificationService::class)->notifyUserIds(
                    $userIds,
                    'promo',
                    [
                        'title'  => $this->title,
                        'body'   => $this->body,
                        'screen' => $this->screen ?: null,
                    ]
                );
            }

            PushNotificationCampaign::create([
                'title'           => $this->title,
                'body'            => $this->body,
                'audience_scope'  => $this->audienceScope,
                'audience_role'   => $this->audienceRole,
                'organization_id' => $this->audienceScope === 'organization' ? $this->organizationId : null,
                'screen'          => $this->screen ?: null,
                'recipient_count' => count($userIds),
                'device_count'    => $deviceCount,
                'delivered'       => $delivered,
                'sent_by'         => Auth::id() ?: 0,
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
            'type'    => $delivered ? 'success' : 'error',
            'message' => match (true) {
                $delivered      => "Sent to {$deviceCount} device(s) across " . count($userIds) . ' recipient(s).',
                empty($userIds) => 'No matching recipients found for that audience.',
                default          => 'No reachable devices found for the selected audience.',
            },
        ]);

        $lockedOrgId = $this->lockedOrganizationId();

        $this->reset(['title', 'body', 'screen']);
        $this->audienceScope      = $lockedOrgId ? 'organization' : 'all';
        $this->organizationId     = $lockedOrgId;
        $this->audienceRole       = 'both';
        $this->confirming         = false;
        $this->previewRecipients  = null;
        $this->previewDevices     = null;
        $this->resetPage();
    }

    public function render()
    {
        $lockedOrgId = $this->lockedOrganizationId();

        $campaigns = PushNotificationCampaign::with(['organization', 'sender'])
            ->when($lockedOrgId, fn($q) => $q->where('organization_id', $lockedOrgId))
            ->latest()
            ->paginate(10);

        return view('livewire.super-admin.push-notification', [
            'organizations' => Organization::orderBy('name')->get(),
            'campaigns'     => $campaigns,
            'orgLocked'     => (bool) $lockedOrgId,
        ]);
    }
}
