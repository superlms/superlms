<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\SuperAdmin\CreditPolicy;
use App\Models\SuperAdmin\CreditQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Credit extends Component
{
    use WithPagination, WireUiActions, WithFileUploads;

    public string $activeTab = 'credit';

    // ── Credit tab filters ────────────────────────────────────────────────────
    public string $search         = '';
    public string $statusFilter   = '';
    public string $orgFilter      = '';

    // ── View query modal ──────────────────────────────────────────────────────
    public bool  $showViewModal  = false;
    public ?int  $viewQueryId    = null;

    // ── Status / Remark modal (also handles approval details) ──────────────────
    public bool   $showStatusModal = false;
    public ?int   $statusQueryId   = null;
    public string $newStatus       = '';
    public string $statusRemark    = '';

    // Approval details — shown inside the status modal when "Approved" is chosen.
    public string $approveAmount          = '';
    public string $approveStartDate       = '';
    public string $approveEndDate         = '';
    public string $approvePenaltiesPerDay = '0';

    // ── Mark as Collected modal ───────────────────────────────────────────────
    public bool   $showCollectModal   = false;
    public ?int   $collectQueryId     = null;
    public string $collectQueryAmount = '';
    public string $collectQuerySchool = '';

    // ── Policy form (edit policies tab) ──────────────────────────────────────
    public bool   $showPolicyForm  = false;
    public ?int   $editPolicyId    = null;
    public string $policyTitle     = '';
    public array  $policyParagraphs = [''];   // terms-style paragraphs (add/edit/delete)
    public string $policyLink      = '';
    public bool   $policyIsActive  = true;
    public        $policyImage     = null;   // uploaded file
    public        $policyDocument  = null;   // uploaded file

    // ── Delete confirms ───────────────────────────────────────────────────────
    public ?int $pendingDeleteQueryId  = null;
    public ?int $pendingDeletePolicyId = null;

    // ── Tabs ──────────────────────────────────────────────────────────────────
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ── Filters ───────────────────────────────────────────────────────────────
    public function clearFilters(): void
    {
        $this->search       = '';
        $this->statusFilter = '';
        $this->orgFilter    = '';
        $this->resetPage();
    }

    // ── View query ────────────────────────────────────────────────────────────
    public function viewQuery(int $id): void
    {
        $this->viewQueryId   = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewQueryId   = null;
    }

    // ── Status / Approval modal ───────────────────────────────────────────────
    public function openStatusModal(int $id): void
    {
        $query = CreditQuery::findOrFail($id);
        $this->statusQueryId          = $id;
        $this->newStatus              = $query->status;
        $this->statusRemark           = $query->admin_remark ?? '';
        // Pre-fill approval details (used when "Approved" is selected).
        $this->approveAmount          = (string) $query->amount;
        $this->approveStartDate       = $query->start_date->format('Y-m-d');
        $this->approveEndDate         = $query->end_date->format('Y-m-d');
        $this->approvePenaltiesPerDay = (string) ($query->penalties_per_day ?? 0);
        $this->showStatusModal        = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->statusQueryId   = null;
        $this->resetErrorBag();
    }

    public function updateStatus(): void
    {
        $rules = [
            'newStatus'    => 'required|in:pending,processing,approved,denied',
            'statusRemark' => 'nullable|string|max:1000',
        ];

        // When approving, the credit terms are required.
        if ($this->newStatus === 'approved') {
            $rules['approveAmount']          = 'required|numeric|min:1';
            $rules['approveStartDate']       = 'required|date';
            $rules['approveEndDate']         = 'required|date|after:approveStartDate';
            $rules['approvePenaltiesPerDay'] = 'nullable|numeric|min:0';
        }

        $this->validate($rules);

        $data = [
            'status'       => $this->newStatus,
            'admin_remark' => $this->statusRemark ?: null,
        ];

        if ($this->newStatus === 'approved') {
            $data['amount']            = $this->approveAmount;
            $data['start_date']        = $this->approveStartDate;
            $data['end_date']          = $this->approveEndDate;
            $data['penalties_per_day'] = $this->approvePenaltiesPerDay ?: 0;
            $data['approved_by']       = Auth::id();
            $data['approved_at']       = now();
        }

        // Instance update so model events fire (super-admin notification).
        CreditQuery::find($this->statusQueryId)?->update($data);
        $this->closeStatusModal();
        $this->notification()->success(
            'Updated',
            $this->newStatus === 'approved' ? 'Credit query approved successfully.' : 'Status updated successfully.'
        );
    }

    // ── Mark as Collected modal ───────────────────────────────────────────────
    public function openCollectModal(int $id): void
    {
        $query = CreditQuery::with('organization')->find($id);

        if (!$query) {
            $this->notification()->error('Not found', 'Credit query not found.');
            return;
        }

        $this->collectQueryId     = $query->id;
        $this->collectQueryAmount = (string) ($query->amount ?? '0');
        $this->collectQuerySchool = (string) ($query->organization->name ?? 'this school');
        $this->showCollectModal   = true;
    }

    public function closeCollectModal(): void
    {
        $this->showCollectModal   = false;
        $this->collectQueryId     = null;
        $this->collectQueryAmount = '';
        $this->collectQuerySchool = '';
    }

    public function markAsCollected(): void
    {
        if (!$this->collectQueryId) {
            $this->closeCollectModal();
            return;
        }

        CreditQuery::find($this->collectQueryId)?->update([
            'collected_at' => now(),
        ]);

        $school = $this->collectQuerySchool;
        $this->closeCollectModal();
        $this->notification()->success('Collected', "Payment from {$school} marked as collected.");
    }

    // ── Delete query ──────────────────────────────────────────────────────────
    public function confirmDeleteQuery(int $id): void  { $this->pendingDeleteQueryId = $id; }
    public function cancelDeleteQuery(): void          { $this->pendingDeleteQueryId = null; }
    public function executeDeleteQuery(): void
    {
        CreditQuery::where('id', $this->pendingDeleteQueryId)->delete();
        $this->pendingDeleteQueryId = null;
        $this->notification()->success('Deleted', 'Credit query deleted.');
    }

    // ── Policy CRUD ───────────────────────────────────────────────────────────
    public function openPolicyForm(?int $id = null): void
    {
        $this->editPolicyId   = $id;
        $this->policyImage    = null;
        $this->policyDocument = null;
        $this->resetErrorBag();

        if ($id) {
            $p = CreditPolicy::findOrFail($id);
            $this->policyTitle      = $p->title;
            $paras                  = $p->body_paragraphs;
            $this->policyParagraphs = !empty($paras) ? $paras : [''];
            $this->policyLink       = $p->link ?? '';
            $this->policyIsActive   = (bool) $p->is_active;
        } else {
            $this->policyTitle      = '';
            $this->policyParagraphs = [''];
            $this->policyLink       = '';
            $this->policyIsActive   = true;
        }
        $this->showPolicyForm = true;
    }

    public function closePolicyForm(): void
    {
        $this->showPolicyForm = false;
        $this->editPolicyId   = null;
    }

    // Policy paragraph repeater
    public function addPolicyParagraph(): void
    {
        $this->policyParagraphs[] = '';
    }

    public function removePolicyParagraph(int $index): void
    {
        unset($this->policyParagraphs[$index]);
        $this->policyParagraphs = array_values($this->policyParagraphs);
        if (empty($this->policyParagraphs)) {
            $this->policyParagraphs = [''];
        }
    }

    public function movePolicyParagraphUp(int $index): void
    {
        if ($index > 0) {
            [$this->policyParagraphs[$index - 1], $this->policyParagraphs[$index]] =
                [$this->policyParagraphs[$index], $this->policyParagraphs[$index - 1]];
        }
    }

    public function movePolicyParagraphDown(int $index): void
    {
        if ($index < count($this->policyParagraphs) - 1) {
            [$this->policyParagraphs[$index + 1], $this->policyParagraphs[$index]] =
                [$this->policyParagraphs[$index], $this->policyParagraphs[$index + 1]];
        }
    }

    public function savePolicy(): void
    {
        $this->validate([
            'policyTitle'      => 'required|string|max:255',
            'policyParagraphs' => 'array',
            'policyParagraphs.*' => 'nullable|string|max:20000',
            'policyLink'       => 'nullable|url|max:500',
            'policyImage'      => 'nullable|image|max:4096',
            'policyDocument'   => 'nullable|mimes:pdf,doc,docx|max:10240',
            'policyIsActive'   => 'boolean',
        ]);

        $paragraphs = array_values(array_filter(
            array_map('trim', $this->policyParagraphs),
            fn($p) => $p !== ''
        ));

        if (empty($paragraphs)) {
            $this->addError('policyParagraphs', 'Add at least one paragraph.');
            return;
        }

        $data = [
            'title'      => $this->policyTitle,
            'paragraphs' => $paragraphs,
            // Keep the plain-text content in sync (used by API & legacy views).
            'content'    => implode("\n\n", $paragraphs),
            'link'       => $this->policyLink ?: null,
            'is_active'  => $this->policyIsActive,
        ];

        if ($this->policyImage) {
            $path = $this->policyImage->store('superadmin/credit-policies/images', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['image'] = $path;
        }
        if ($this->policyDocument) {
            $path = $this->policyDocument->store('superadmin/credit-policies/documents', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['document'] = $path;
        }

        if ($this->editPolicyId) {
            CreditPolicy::find($this->editPolicyId)?->update($data);
            $this->notification()->success('Updated', 'Policy updated successfully.');
        } else {
            CreditPolicy::create($data);
            $this->notification()->success('Created', 'Policy created successfully.');
        }

        $this->closePolicyForm();
    }

    public function confirmDeletePolicy(int $id): void  { $this->pendingDeletePolicyId = $id; }
    public function cancelDeletePolicy(): void          { $this->pendingDeletePolicyId = null; }
    public function executeDeletePolicy(): void
    {
        $policy = CreditPolicy::find($this->pendingDeletePolicyId);
        if ($policy) {
            if ($policy->image)    Storage::disk('s3')->delete($policy->image);
            if ($policy->document) Storage::disk('s3')->delete($policy->document);
            $policy->delete();
        }
        $this->pendingDeletePolicyId = null;
        $this->notification()->success('Deleted', 'Policy deleted.');
    }

    // ── Render ────────────────────────────────────────────────────────────────
    public function render()
    {
        $queries = CreditQuery::with(['organization'])
            ->when($this->search, fn($q) => $q->whereHas('organization', fn($o) =>
                $o->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->orgFilter, fn($q) => $q->where('organization_id', $this->orgFilter))
            ->latest()
            ->paginate(15);

        $policies = CreditPolicy::latest()->paginate(20);

        $selectedQuery = $this->viewQueryId
            ? CreditQuery::with(['organization', 'approvedBy'])->find($this->viewQueryId)
            : null;

        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        // When a specific school is selected, surface its full credit record
        // (how many were approved / denied / pending, totals & collections).
        $orgSummary = null;
        if ($this->orgFilter) {
            $base = CreditQuery::where('organization_id', $this->orgFilter);
            $orgSummary = [
                'school'         => $organizations->firstWhere('id', (int) $this->orgFilter)?->name
                                    ?? Organization::find($this->orgFilter)?->name ?? 'School',
                'total'          => (clone $base)->count(),
                'approved'       => (clone $base)->where('status', 'approved')->count(),
                'denied'         => (clone $base)->where('status', 'denied')->count(),
                'pending'        => (clone $base)->whereIn('status', ['pending', 'processing'])->count(),
                'total_approved' => (clone $base)->where('status', 'approved')->sum('amount'),
                'collected'      => (clone $base)->whereNotNull('collected_at')->count(),
            ];
        }

        $analytics = [
            'total'               => CreditQuery::count(),
            'pending'             => CreditQuery::pending()->count(),
            'processing'          => CreditQuery::processing()->count(),
            'approved'            => CreditQuery::approved()->count(),
            'denied'              => CreditQuery::denied()->count(),
            'active_credits'      => CreditQuery::activeCredit()->count(),
            'total_amount_leased' => CreditQuery::approved()->sum('amount'),
            'total_penalties'     => CreditQuery::approved()
                ->where('end_date', '<', now())
                ->selectRaw('SUM(penalties_per_day * DATEDIFF(NOW(), end_date)) as total')
                ->value('total') ?? 0,
            'total_to_collect'    => CreditQuery::approved()->where('end_date', '>=', now())->sum('amount'),
        ];

        return view('livewire.super-admin.credit', compact(
            'queries', 'policies', 'selectedQuery', 'organizations', 'analytics', 'orgSummary'
        ));
    }
}
