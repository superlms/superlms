<?php

namespace App\Livewire\Accounts;

use App\Models\SuperAdmin\CreditPolicy;
use App\Models\SuperAdmin\CreditQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Credit extends Component
{
    use WithPagination, WireUiActions;

    public string $activeTab = 'queries';

    // ── Ask / Edit Credit modal ──────────────────────────────────────────────
    public bool   $showCreditModal = false;
    public ?int   $editQueryId     = null;
    public string $creditAmount    = '';
    public string $creditStartDate = '';
    public string $creditEndDate   = '';
    public string $creditHeading   = '';
    public string $creditReason    = '';

    // ── View modal ───────────────────────────────────────────────────────────
    public bool  $showViewModal   = false;
    public ?int  $viewQueryId     = null;

    // ── Delete confirm ───────────────────────────────────────────────────────
    public ?int $pendingDeleteId = null;

    // ── Filters ──────────────────────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatedCreditStartDate(string $value): void
    {
        if ($value) {
            $this->creditEndDate = Carbon::parse($value)->addDays(20)->format('Y-m-d');
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openAskCreditModal(): void
    {
        $this->editQueryId     = null;
        $this->creditAmount    = '';
        $this->creditStartDate = '';
        $this->creditEndDate   = '';
        $this->creditHeading   = '';
        $this->creditReason    = '';
        $this->showCreditModal = true;
    }

    public function openEditModal(int $id): void
    {
        $query = CreditQuery::forOrg(Auth::user()->organization_id)->findOrFail($id);
        if ($query->status !== 'pending') {
            $this->notification()->error('Cannot Edit', 'Only pending queries can be edited.');
            return;
        }
        $this->editQueryId     = $id;
        $this->creditAmount    = (string) $query->amount;
        $this->creditStartDate = $query->start_date->format('Y-m-d');
        $this->creditEndDate   = $query->end_date->format('Y-m-d');
        $this->creditHeading   = $query->heading;
        $this->creditReason    = $query->reason;
        $this->showCreditModal = true;
    }

    public function closeCreditModal(): void
    {
        $this->showCreditModal = false;
        $this->editQueryId     = null;
    }

    public function saveCreditQuery(): void
    {
        $this->validate([
            'creditAmount'    => 'required|numeric|min:1',
            'creditStartDate' => 'required|date',
            'creditEndDate'   => 'required|date|after:creditStartDate',
            'creditHeading'   => 'required|string|max:255',
            'creditReason'    => 'required|string|min:10',
        ]);

        $data = [
            'amount'     => $this->creditAmount,
            'start_date' => $this->creditStartDate,
            'end_date'   => $this->creditEndDate,
            'heading'    => $this->creditHeading,
            'reason'     => $this->creditReason,
        ];

        if ($this->editQueryId) {
            CreditQuery::forOrg(Auth::user()->organization_id)
                ->where('id', $this->editQueryId)
                ->update($data);
            $this->notification()->success('Updated', 'Credit query updated successfully.');
        } else {
            CreditQuery::create(array_merge($data, [
                'organization_id' => Auth::user()->organization_id,
                'status'          => 'pending',
            ]));
            $this->notification()->success('Submitted', 'Credit request submitted successfully.');
        }

        $this->closeCreditModal();
    }

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

    public function confirmDelete(int $id): void  { $this->pendingDeleteId = $id; }
    public function cancelDelete(): void          { $this->pendingDeleteId = null; }
    public function executeDelete(): void
    {
        CreditQuery::forOrg(Auth::user()->organization_id)
            ->where('id', $this->pendingDeleteId)
            ->delete();
        $this->pendingDeleteId = null;
        $this->notification()->success('Deleted', 'Credit query deleted successfully.');
    }

    public function render()
    {
        $orgId = Auth::user()->organization_id;

        $queries = CreditQuery::forOrg($orgId)
            ->when($this->search, fn($q) => $q->where(fn($s) =>
                $s->where('heading', 'like', "%{$this->search}%")
                  ->orWhere('reason', 'like', "%{$this->search}%")
            ))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $policies = CreditPolicy::where('is_active', true)->latest()->get();

        $selectedQuery = $this->viewQueryId
            ? CreditQuery::forOrg($orgId)->find($this->viewQueryId)
            : null;

        $stats = [
            'total'      => CreditQuery::forOrg($orgId)->count(),
            'pending'    => CreditQuery::forOrg($orgId)->pending()->count(),
            'processing' => CreditQuery::forOrg($orgId)->processing()->count(),
            'approved'   => CreditQuery::forOrg($orgId)->approved()->count(),
            'denied'     => CreditQuery::forOrg($orgId)->denied()->count(),
        ];

        return view('livewire.accounts.credit', compact(
            'queries', 'policies', 'selectedQuery', 'stats'
        ));
    }
}
