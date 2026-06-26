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

    // ── Approve modal ─────────────────────────────────────────────────────────
    public bool   $showApproveModal       = false;
    public ?int   $approveQueryId         = null;
    public string $approveAmount          = '';
    public string $approveStartDate       = '';
    public string $approveEndDate         = '';
    public string $approveRemark          = '';
    public string $approvePenaltiesPerDay = '0';

    // ── Status / Remark modal ─────────────────────────────────────────────────
    public bool   $showStatusModal = false;
    public ?int   $statusQueryId   = null;
    public string $newStatus       = '';
    public string $statusRemark    = '';

    // ── Mark as Collected modal ───────────────────────────────────────────────
    public bool   $showCollectModal   = false;
    public ?int   $collectQueryId     = null;
    public string $collectQueryAmount = '';
    public string $collectQuerySchool = '';

    // ── Policy form (edit policies tab) ──────────────────────────────────────
    public bool   $showPolicyForm  = false;
    public ?int   $editPolicyId    = null;
    public string $policyTitle     = '';
    public string $policyContent   = '';
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

    // ── Approve modal ─────────────────────────────────────────────────────────
    public function openApproveModal(int $id): void
    {
        $query = CreditQuery::with('organization')->findOrFail($id);
        $this->approveQueryId         = $id;
        $this->approveAmount          = (string) $query->amount;
        $this->approveStartDate       = $query->start_date->format('Y-m-d');
        $this->approveEndDate         = $query->end_date->format('Y-m-d');
        $this->approveRemark          = $query->admin_remark ?? '';
        $this->approvePenaltiesPerDay = (string) ($query->penalties_per_day ?? 0);
        $this->showApproveModal       = true;
    }

    public function closeApproveModal(): void
    {
        $this->showApproveModal = false;
        $this->approveQueryId   = null;
    }

    public function approveQuery(): void
    {
        $this->validate([
            'approveAmount'          => 'required|numeric|min:1',
            'approveStartDate'       => 'required|date',
            'approveEndDate'         => 'required|date|after:approveStartDate',
            'approvePenaltiesPerDay' => 'nullable|numeric|min:0',
        ]);

        CreditQuery::where('id', $this->approveQueryId)->update([
            'status'           => 'approved',
            'amount'           => $this->approveAmount,
            'start_date'       => $this->approveStartDate,
            'end_date'         => $this->approveEndDate,
            'admin_remark'     => $this->approveRemark ?: null,
            'penalties_per_day'=> $this->approvePenaltiesPerDay ?: 0,
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
        ]);

        $this->closeApproveModal();
        $this->notification()->success('Approved', 'Credit query approved successfully.');
    }

    // ── Status / Remark modal ─────────────────────────────────────────────────
    public function openStatusModal(int $id): void
    {
        $query = CreditQuery::findOrFail($id);
        $this->statusQueryId   = $id;
        $this->newStatus       = $query->status;
        $this->statusRemark    = $query->admin_remark ?? '';
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->statusQueryId   = null;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'newStatus'   => 'required|in:pending,processing,approved,denied',
            'statusRemark'=> 'nullable|string|max:1000',
        ]);

        $data = [
            'status'       => $this->newStatus,
            'admin_remark' => $this->statusRemark ?: null,
        ];

        if ($this->newStatus === 'approved') {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        CreditQuery::where('id', $this->statusQueryId)->update($data);
        $this->closeStatusModal();
        $this->notification()->success('Updated', 'Status updated successfully.');
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

        CreditQuery::where('id', $this->collectQueryId)->update([
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

        if ($id) {
            $p = CreditPolicy::findOrFail($id);
            $this->policyTitle    = $p->title;
            $this->policyContent  = $p->content;
            $this->policyLink     = $p->link ?? '';
            $this->policyIsActive = (bool) $p->is_active;
        } else {
            $this->policyTitle    = '';
            $this->policyContent  = '';
            $this->policyLink     = '';
            $this->policyIsActive = true;
        }
        $this->showPolicyForm = true;
    }

    public function closePolicyForm(): void
    {
        $this->showPolicyForm = false;
        $this->editPolicyId   = null;
    }

    public function savePolicy(): void
    {
        $this->validate([
            'policyTitle'    => 'required|string|max:255',
            'policyContent'  => 'required|string|min:10',
            'policyLink'     => 'nullable|url|max:500',
            'policyImage'    => 'nullable|image|max:4096',
            'policyDocument' => 'nullable|mimes:pdf,doc,docx|max:10240',
            'policyIsActive' => 'boolean',
        ]);

        $data = [
            'title'     => $this->policyTitle,
            'content'   => $this->policyContent,
            'link'      => $this->policyLink ?: null,
            'is_active' => $this->policyIsActive,
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
            CreditPolicy::where('id', $this->editPolicyId)->update($data);
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
            'queries', 'policies', 'selectedQuery', 'organizations', 'analytics'
        ));
    }
}
