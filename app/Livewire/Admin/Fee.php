<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesFeeAnalytics;
use App\Livewire\Concerns\HandlesFeeConcessions;
use App\Livewire\Concerns\HandlesFeeSubmission;
use App\Livewire\Concerns\HandlesPayments;
use App\Livewire\Concerns\HandlesPenalties;
use App\Livewire\Concerns\HandlesStudentFeeView;
use App\Livewire\Concerns\HandlesViewFee;
use App\Livewire\Concerns\HandlesFeeCycles;
use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Fee extends Component
{
    use WireUiActions, WithPagination, HandlesFeeCycles, HandlesFeeConcessions, HandlesStudentFeeView, HandlesViewFee, HandlesFeeSubmission, HandlesPenalties, HandlesPayments, HandlesFeeAnalytics;

    public string $activeTab = ''; // '' = card menu (landing); otherwise the open tab

    // ─── Fee Structure ─────────────────────────────────────────────────────────
    public $structureStandardId = '';
    public $structureSectionId  = '';
    public $feeName             = '';
    public $feeAmount           = '';
    public $structureFeeType    = 'academic';
    public $academicYear        = '';
    public $editStructureId     = null;
    public $structureModalOpen  = false;

    // Filters for structure list
    public $filterStructureStandard  = '';
    public $filterStructureSection   = '';
    public $filterStructureYear      = '';

    // ─── Fee Submission — state and logic live in HandlesFeeSubmission ─────────

    // ─── Concession (per-student fee discount) + view — see HandlesFeeConcessions

    // ─── View Fee ─────────────────────────────────────────────────────────────
    // State and logic live in HandlesViewFee.

    // ─── Analytics — state and logic live in HandlesFeeAnalytics ──────────────

    // ─── Payments — state and logic live in HandlesPayments (shared with the
    //     accounts Payments page, so both screens stay identical) ─────────────

    // ─── Penalties — state and logic live in HandlesPenalties ──────────────────

    // ─── Fee Cycle (installments) + Calculator — see HandlesFeeCycles ───────────

    // ─── Account Users (nested component — filters proxied via events) ──────────
    public string $acctSearch = '';
    public string $acctStatus = '';

    // ─── Shared ───────────────────────────────────────────────────────────────
    public $search      = '';
    public $perPage     = 10;
    public $standards   = [];
    public $sections    = [];
    public $students    = [];

    protected $queryString = [
        'activeTab'  => ['except' => ''],
        'search'     => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->standards  = Standard::where('organization_id', $this->orgId())
            ->where('is_active', true)->orderBy('id')->get();
        $this->submitDate = today()->toDateString();
        $this->initPaymentFilters();

        // Deep links (?activeTab=analytics) skip showTab(), so seed it here too.
        if ($this->activeTab === 'analytics') {
            $this->loadAnalytics();
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    /** Return to the card menu (landing). */
    public function backToMenu(): void
    {
        $this->activeTab = '';
        $this->resetPage();
        $this->search = '';
    }

    /** Fired by the embedded fee-structure component's Analytics button. */
    #[On('fee-open-analytics')]
    public function openAnalyticsTab(): void
    {
        $this->showTab('analytics');
    }

    // ─── Account Users proxy (button + filters live in the fee header) ──────────
    public function acctAdd(): void
    {
        $this->dispatch('acct-add')->to(AccountUsers::class);
    }

    public function updatedAcctSearch(): void
    {
        $this->dispatch('acct-filter', search: $this->acctSearch, status: $this->acctStatus)->to(AccountUsers::class);
    }

    public function updatedAcctStatus(): void
    {
        $this->dispatch('acct-filter', search: $this->acctSearch, status: $this->acctStatus)->to(AccountUsers::class);
    }

    public function acctClearFilters(): void
    {
        $this->acctSearch = '';
        $this->acctStatus = '';
        $this->dispatch('acct-filter', search: '', status: '')->to(AccountUsers::class);
    }

    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->search = '';
        // Account-users filters live in this header; reset them on every switch
        // so they match the freshly-mounted nested component.
        $this->acctSearch = '';
        $this->acctStatus = '';

        if ($tab === 'analytics') {
            $this->loadAnalytics();
        }
    }

    // ─── Concession (per-student fee discount) + view — see HandlesFeeConcessions

    // ─── Fee Structure ─────────────────────────────────────────────────────────

    public function openStructureModal(int $id = null): void
    {
        $this->resetStructureForm();
        $this->editStructureId   = $id;
        $this->structureModalOpen = true;

        if ($id) {
            $s = FeeStructure::find($id);
            $this->structureStandardId = $s->standard_id;
            $this->structureSectionId  = $s->section_id;
            $this->feeName             = $s->fee_name;
            $this->feeAmount           = $s->amount;
            $this->structureFeeType    = $s->fee_type;
            $this->academicYear        = $s->academic_year;
        }
    }

    public function saveStructure(): void
    {
        $this->validate([
            'structureStandardId' => 'required|exists:standards,id',
            'feeName'             => 'required|string|max:255',
            'feeAmount'           => 'required|numeric|min:0',
            'structureFeeType'    => 'required|in:academic,transport',
            'academicYear'        => 'required|string|max:20',
        ]);

        $data = [
            'organization_id' => $this->orgId(),
            'standard_id'     => $this->structureStandardId,
            'section_id'      => $this->structureSectionId ?: null,
            'fee_name'        => $this->feeName,
            'amount'          => $this->feeAmount,
            'fee_type'        => $this->structureFeeType,
            'academic_year'   => $this->academicYear,
            'is_active'       => true,
        ];

        try {
            if ($this->editStructureId) {
                FeeStructure::find($this->editStructureId)->update($data);
                $this->notification()->success('Fee structure updated successfully!');
            } else {
                FeeStructure::create($data);
                $this->notification()->success('Fee structure added successfully!');
            }
            $this->resetStructureForm();
        } catch (\Exception $e) {
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    public function deleteStructure(int $id): void
    {
        $this->dialog()->confirm([
            'title'       => 'Delete Fee Structure?',
            'description' => 'This action cannot be undone.',
            'icon'        => 'error',
            'accept'      => ['label' => 'Yes, delete', 'method' => 'doDeleteStructure', 'params' => $id],
            'reject'      => ['label' => 'Cancel'],
        ]);
    }

    public function doDeleteStructure(int $id): void
    {
        FeeStructure::find($id)?->delete();
        $this->notification()->success('Fee structure deleted!');
    }

    private function resetStructureForm(): void
    {
        $this->reset([
            'editStructureId', 'structureModalOpen',
            'structureStandardId', 'structureSectionId',
            'feeName', 'feeAmount', 'structureFeeType', 'academicYear',
        ]);
    }

    public function updatedFilterStructureStandard(): void
    {
        $this->filterStructureSection = '';
        $this->sections = $this->filterStructureStandard
            ? Section::where('standard_id', $this->filterStructureStandard)->where('is_active', true)->get()
            : [];
        $this->resetPage();
    }

    // ─── Fee Submission — logic lives in HandlesFeeSubmission ──────────────────

    /** Analytics rows link into the View Fee tab on this same screen. */
    public function openStudentLedger(int $studentId): void
    {
        $this->showTab('view_fee');
        $this->viewSubTab    = 'by_student';
        $this->viewStudentId = (string) $studentId;
        $this->updatedViewStudentId();
    }

    // ─── Payments ── filters, the analytics strip and the merged listing all
    //     come from HandlesPayments (shared with the accounts Payments page);
    //     only the receipt route prefix differs.

    protected function paymentsRoutePrefix(): string
    {
        return 'admin';
    }

    // ─── Penalties — logic lives in HandlesPenalties ────────────────────────────

    // ── Fee Cycle (installments) + Calculator — see HandlesFeeCycles ─────────────

    // ─── Shared ───────────────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orgId = $this->orgId();
        $data  = ['standards' => $this->standards, 'sections' => $this->sections, 'students' => $this->students];

        if ($this->activeTab === 'fee_structure') {
            $data['structures'] = FeeStructure::with(['standard', 'section'])
                ->where('organization_id', $orgId)
                ->when($this->filterStructureStandard, fn($q) => $q->where('standard_id', $this->filterStructureStandard))
                ->when($this->filterStructureSection, fn($q) => $q->where('section_id', $this->filterStructureSection))
                ->when($this->filterStructureYear, fn($q) => $q->where('academic_year', $this->filterStructureYear))
                ->when($this->search, fn($q) => $q->where('fee_name', 'like', "%{$this->search}%"))
                ->orderByDesc('created_at')
                ->paginate($this->perPage);
        }

        if ($this->activeTab === 'payments') {
            $data = array_merge($data, $this->paymentsViewData());
        }

        if ($this->activeTab === 'analytics') {
            $data = array_merge($data, $this->analyticsViewData());
        }

        if ($this->activeTab === 'fee_submission') {
            $data = array_merge($data, $this->feeSubmissionViewData());
        }

        if ($this->activeTab === 'view_fee') {
            $data = array_merge($data, $this->viewFeeViewData());
        }

        if ($this->activeTab === 'penalties') {
            $data = array_merge($data, $this->penaltyViewData());
        }

        if ($this->activeTab === 'cycle') {
            $data = array_merge($data, $this->feeCycleViewData());
        }

        if ($this->activeTab === 'account_users') {
            $accBase = User::where('organization_id', $orgId)->where('role', 'accounts');
            $data['acctTotal']    = (clone $accBase)->count();
            $data['acctActive']   = (clone $accBase)->where('is_active', true)->count();
            $data['acctInactive'] = (clone $accBase)->where('is_active', false)->count();
        }

        if ($this->activeTab === 'concession') {
            $data = array_merge($data, $this->feeConcessionViewData());
        }

        return view('livewire.admin.fee', $data);
    }
}
