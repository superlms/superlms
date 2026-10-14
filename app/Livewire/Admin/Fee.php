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
use App\Models\Admin\Fee\FeePaymentRequest;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\Fee\PaymentQrCode;
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
    use WireUiActions, WithPagination, HandlesFeeCycles, HandlesFeeConcessions, HandlesStudentFeeView, HandlesPenalties, HandlesPayments, HandlesFeeAnalytics;
    // View Fee and Fee Submission are one tab here ("View & Submit Fee"): the
    // ledger on screen is View Fee's, and Submit Fee in the header collects for
    // the same student. These few are wrapped below to keep the two in step;
    // the traits themselves (shared with the accounts pages) are unchanged.
    use HandlesViewFee {
        updatedViewStudentId as protected viewFeeUpdatedStudentId;
        viewStudentFromClass as protected viewFeeStudentFromClass;
    }
    use HandlesFeeSubmission {
        updatedSelectedStudentId as protected submissionUpdatedStudentId;
        openPaymentDateEdit as protected submissionOpenPaymentDateEdit;
    }

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

    // ─── QR Payments (nested component — filters proxied via events) ───────────
    // One card with two tabs: 'payments' (what students reported, checked here)
    // and 'qr' (the school's own QR and UPI ID — the Payment QR component).
    public string $qrSubTab  = 'payments';
    public string $qrStatus  = FeePaymentRequest::STATUS_PENDING;
    public string $qrFeeType = '';
    public string $qrSearch  = '';
    public string $qrDate    = '';

    // ─── Shared ───────────────────────────────────────────────────────────────
    public $search      = '';
    public $perPage     = 10;
    public $standards   = [];
    public $sections    = [];
    public $students    = [];

    protected $queryString = [
        'activeTab'  => ['except' => ''],
        'search'     => ['except' => '', 'history' => false],
        'qrSubTab'   => ['except' => 'payments'],
    ];

    public function mount(): void
    {
        $this->standards  = Standard::where('organization_id', $this->orgId())
            ->where('is_active', true)->inClassOrder()->get();
        $this->submitDate = today()->toDateString();
        $this->initPaymentFilters();

        // Old links to the two tabs that were merged land on the merged ones.
        if ($this->activeTab === 'fee_submission') {
            $this->activeTab = 'view_fee';
        } elseif ($this->activeTab === 'payment_qr') {
            $this->activeTab = 'qr_payments';
            $this->qrSubTab  = 'qr';
        }

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

    // ─── QR Payments proxy (the filter bar lives in the fee header) ───────────
    public function updatedQrStatus(): void  { $this->pushQrFilters(); }
    public function updatedQrFeeType(): void { $this->pushQrFilters(); }
    public function updatedQrSearch(): void  { $this->pushQrFilters(); }
    public function updatedQrDate(): void    { $this->pushQrFilters(); }

    public function qrClearFilters(): void
    {
        $this->resetQrFilters();
        $this->pushQrFilters();
    }

    private function resetQrFilters(): void
    {
        $this->qrStatus  = FeePaymentRequest::STATUS_PENDING;
        $this->qrFeeType = '';
        $this->qrSearch  = '';
        $this->qrDate    = '';
    }

    private function pushQrFilters(): void
    {
        $this->dispatch(
            'qr-filter',
            status: $this->qrStatus,
            feeType: $this->qrFeeType,
            search: $this->qrSearch,
            date: $this->qrDate,
        )->to(QrPayments::class);
    }

    public function showTab(string $tab): void
    {
        // Fee Submission is part of View Fee now, Payment QR part of QR Payments.
        $qrSub = $tab === 'payment_qr' ? 'qr' : 'payments';
        $tab   = match ($tab) {
            'fee_submission' => 'view_fee',
            'payment_qr'     => 'qr_payments',
            default          => $tab,
        };
        $this->qrSubTab  = $qrSub;
        $this->activeTab = $tab;
        $this->resetPage();
        $this->search = '';
        // The nested components' filters live in this header; reset them on
        // every switch so they match the freshly-mounted component.
        $this->acctSearch = '';
        $this->acctStatus = '';
        $this->resetQrFilters();

        if ($tab === 'analytics') {
            $this->loadAnalytics();
        }
    }

    // ─── Concession (per-student fee discount) + view — see HandlesFeeConcessions

    // ─── QR Payments | Payment QR ──────────────────────────────────────────────
    public function setQrSubTab(string $tab): void
    {
        $this->qrSubTab = $tab === 'qr' ? 'qr' : 'payments';
        $this->resetQrFilters();
    }

    // ─── View & Submit Fee (View Fee and Fee Submission in one tab) ────────────

    /** The student whose ledger the tab shows — picked by student, or opened from the class list. */
    public function feeTabStudentId(): ?int
    {
        if ($this->viewSubTab === 'by_student') {
            return $this->viewStudentId ? (int) $this->viewStudentId : null;
        }

        return $this->classViewStudentId ? (int) $this->classViewStudentId : null;
    }

    /** Picking a student shows the ledger and readies Submit Fee for them. */
    public function updatedViewStudentId(): void
    {
        $this->viewFeeUpdatedStudentId();
        $this->readySubmitFor($this->viewStudentId ? (int) $this->viewStudentId : null);
    }

    public function viewStudentFromClass(int $studentId): void
    {
        $this->viewFeeStudentFromClass($studentId);
        $this->readySubmitFor($studentId);
    }

    private function readySubmitFor(?int $studentId): void
    {
        $this->selectedStudentId = $studentId ? (string) $studentId : '';
        $this->submissionUpdatedStudentId();
    }

    /** The header's Submit Fee: the Collect Fee panel, for the student on screen. */
    public function openFeeSubmit(): void
    {
        $id = $this->feeTabStudentId();
        if (!$id) {
            $this->notification()->error('Select a student first.');
            return;
        }
        if ((int) $this->selectedStudentId !== $id || empty($this->submissionLedger)) {
            $this->readySubmitFor($id);
        }
        $this->openSubmitPanel();
    }

    /** A payment's date, corrected from the merged tab's ledger. */
    public function openPaymentDateEdit(string $kind, $id): void
    {
        if ($this->activeTab === 'view_fee' && ($sid = $this->feeTabStudentId()) && (int) $this->selectedStudentId !== $sid) {
            $this->readySubmitFor($sid);
        }
        $this->submissionOpenPaymentDateEdit($kind, $id);
    }

    /**
     * After a payment or a date fix the submission side reloads; the merged
     * tab shows the same ledger (and, by class, the list's figures), so both
     * follow.
     */
    public function updatedSelectedStudentId(): void
    {
        $this->submissionUpdatedStudentId();

        if ($this->activeTab !== 'view_fee' || !$this->selectedStudentId
            || (int) $this->selectedStudentId !== $this->feeTabStudentId()) {
            return;
        }

        if ($this->viewSubTab === 'by_student') {
            $this->studentFeeView = $this->submissionLedger;
        } else {
            $open = $this->classViewStudentId;
            $this->loadClassFeeView();
            $this->classViewStudentId  = $open;
            $this->classStudentFeeView = $this->submissionLedger;
        }
    }

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
            // …and Collect Fee's (the caps under Amount, the earliest date).
            $data = array_merge($data, $this->viewFeeViewData(), $this->feeSubmissionViewData());
        }

        if ($this->activeTab === 'penalties') {
            $data = array_merge($data, $this->penaltyViewData());
        }

        if ($this->activeTab === 'cycle') {
            $data = array_merge($data, $this->feeCycleViewData());
        }

        if ($this->activeTab === '') {
            // The QR Payments card carries how many wait to be checked.
            $data['qrPending'] = FeePaymentRequest::where('organization_id', $orgId)
                ->where('status', FeePaymentRequest::STATUS_PENDING)
                ->count();
        }

        if ($this->activeTab === 'qr_payments' && $this->qrSubTab === 'qr') {
            // The header's button reads Add or Edit.
            $data['qrExists'] = PaymentQrCode::where('organization_id', $orgId)->exists();
        } elseif ($this->activeTab === 'qr_payments') {
            // What the header counts — the chosen day, or everything.
            $qrBase = fn () => FeePaymentRequest::where('organization_id', $orgId)
                ->when($this->qrDate !== '', fn ($q) => $q->whereDate('paid_on', $this->qrDate));

            $data['qrStats'] = [
                'total'    => $qrBase()->count(),
                'amount'   => (float) $qrBase()->sum('amount'),
                'pending'  => $qrBase()->where('status', FeePaymentRequest::STATUS_PENDING)->count(),
                'approved' => $qrBase()->where('status', FeePaymentRequest::STATUS_APPROVED)->count(),
                'rejected' => $qrBase()->where('status', FeePaymentRequest::STATUS_REJECTED)->count(),
            ];
        }

        if ($this->activeTab === 'payment_qr') {
            // The header's button reads Add or Edit.
            $data['qrExists'] = PaymentQrCode::where('organization_id', $orgId)->exists();
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
