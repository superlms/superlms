<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesFeeConcessions;
use App\Livewire\Concerns\HandlesFeeSubmission;
use App\Livewire\Concerns\HandlesPayments;
use App\Livewire\Concerns\HandlesPenalties;
use App\Livewire\Concerns\HandlesStudentFeeView;
use App\Livewire\Concerns\HandlesViewFee;
use App\Livewire\Concerns\HandlesFeeCycles;
use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Fee extends Component
{
    use WireUiActions, WithPagination, HandlesFeeCycles, HandlesFeeConcessions, HandlesStudentFeeView, HandlesViewFee, HandlesFeeSubmission, HandlesPenalties, HandlesPayments;

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

    // ─── Analytics ────────────────────────────────────────────────────────────
    public $analyticsStandardId  = '';
    public $analyticsSectionId   = '';
    public $analyticsData        = [];
    public $analyticsStudentList = [];
    public $analyticsDaily         = [];   // last 14 days collection series
    public $analyticsModeBreakdown = [];   // cash / online / cheque / bank_transfer
    public $analyticsPeriodStats   = [];   // today / week / month totals & counts
    public $analyticsRecentPayments = [];  // latest payments feed

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

    // ─── Analytics ────────────────────────────────────────────────────────────

    public function updatedAnalyticsStandardId(): void
    {
        $this->analyticsSectionId = '';
        $this->sections = $this->analyticsStandardId
            ? Section::where('standard_id', $this->analyticsStandardId)->where('is_active', true)->get()
            : [];
        $this->loadAnalytics();
    }

    public function updatedAnalyticsSectionId(): void
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        $orgId = $this->orgId();

        $structureQuery = FeeStructure::where('organization_id', $orgId)->where('is_active', true);
        $paymentQuery   = FeePayment::where('organization_id', $orgId);

        if ($this->analyticsStandardId) {
            $structureQuery->where('standard_id', $this->analyticsStandardId);
            $paymentQuery->where('standard_id', $this->analyticsStandardId);
        }
        if ($this->analyticsSectionId) {
            $structureQuery->where(function ($q) {
                $q->where('section_id', $this->analyticsSectionId)->orWhereNull('section_id');
            });
            $paymentQuery->where('section_id', $this->analyticsSectionId);
        }

        $academicTotal   = (clone $structureQuery)->where('fee_type', 'academic')->sum('amount');
        $transportTotal  = (clone $structureQuery)->where('fee_type', 'transport')->sum('amount');
        $totalCollected  = (clone $paymentQuery)->sum('amount');
        $academicPaid    = (clone $paymentQuery)->where('fee_type', 'academic')->sum('amount');
        $transportPaid   = (clone $paymentQuery)->where('fee_type', 'transport')->sum('amount');

        $this->analyticsData = [
            'totalFee'       => $academicTotal + $transportTotal,
            'academicTotal'  => $academicTotal,
            'transportTotal' => $transportTotal,
            'collected'      => $totalCollected,
            'academicPaid'   => $academicPaid,
            'transportPaid'  => $transportPaid,
            'remaining'      => max(0, ($academicTotal + $transportTotal) - $totalCollected),
        ];

        // Student list
        $studentQuery = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $orgId);
        if ($this->analyticsStandardId) {
            $studentQuery->where('standard_id', $this->analyticsStandardId);
        }
        if ($this->analyticsSectionId) {
            $studentQuery->where('section_id', $this->analyticsSectionId);
        }

        $structures = FeeStructure::where('organization_id', $orgId)->where('is_active', true)
            ->when($this->analyticsStandardId, fn($q) => $q->where('standard_id', $this->analyticsStandardId))
            ->get();

        $this->analyticsStudentList = $studentQuery->get()->map(function ($student) use ($structures, $orgId) {
            $studentStructures = $structures->filter(function ($s) use ($student) {
                return $s->standard_id == $student->standard_id &&
                    (is_null($s->section_id) || $s->section_id == $student->section_id);
            });

            $academicFee  = $studentStructures->where('fee_type', 'academic')->sum('amount');
            $transportFee = $student->transportation_required
                ? $studentStructures->where('fee_type', 'transport')->sum('amount')
                : 0;
            $collected    = FeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)->sum('amount');

            return [
                'id'           => $student->id,
                'name'         => $student->user->name ?? '-',
                'admission_no' => $student->admission_no,
                'class'        => $student->standard->name ?? '-',
                'section'      => $student->section->name ?? '-',
                'totalFee'     => $academicFee + $transportFee,
                'collected'    => $collected,
            ];
        })->values()->toArray();

        $this->loadAnalyticsDaily();
    }

    /**
     * Daily collection series (last 14 days), payment-mode split, period
     * totals and a recent-payments feed — powers the redesigned analytics
     * dashboard so admins can watch day-by-day cash flow.
     */
    private function loadAnalyticsDaily(): void
    {
        $orgId = $this->orgId();

        // Fresh base query honouring the class/section filters.
        $base = fn () => FeePayment::where('organization_id', $orgId)
            ->when($this->analyticsStandardId, fn ($q) => $q->where('standard_id', $this->analyticsStandardId))
            ->when($this->analyticsSectionId, fn ($q) => $q->where('section_id', $this->analyticsSectionId));

        // ── Last 14 days series ──
        $labels = $amounts = $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[]  = $day->format('d M');
            $amounts[] = (float) $base()->whereDate('payment_date', $day)->sum('amount');
            $counts[]  = (int)   $base()->whereDate('payment_date', $day)->count();
        }
        $this->analyticsDaily = ['labels' => $labels, 'amounts' => $amounts, 'counts' => $counts];

        // ── Payment-mode breakdown (this financial context = all matching) ──
        $modes = ['cash', 'online', 'cheque', 'bank_transfer'];
        $this->analyticsModeBreakdown = [];
        foreach ($modes as $mode) {
            $this->analyticsModeBreakdown[$mode] = (float) $base()->where('payment_mode', $mode)->sum('amount');
        }

        // ── Period stats ──
        $today = today();
        $this->analyticsPeriodStats = [
            'today_amt'   => (float) $base()->whereDate('payment_date', $today)->sum('amount'),
            'today_cnt'   => (int)   $base()->whereDate('payment_date', $today)->count(),
            'week_amt'    => (float) $base()->whereBetween('payment_date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])->sum('amount'),
            'month_amt'   => (float) $base()->whereMonth('payment_date', $today->month)->whereYear('payment_date', $today->year)->sum('amount'),
            'month_cnt'   => (int)   $base()->whereMonth('payment_date', $today->month)->whereYear('payment_date', $today->year)->count(),
            'avg_txn'     => (float) $base()->avg('amount'),
        ];

        // ── Recent payments feed ──
        $this->analyticsRecentPayments = $base()
            ->with(['studentDetail.user', 'standard', 'section'])
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->take(8)->get()
            ->map(fn ($p) => [
                'name'    => $p->studentDetail->user->name ?? '-',
                'class'   => trim(($p->standard->name ?? '') . ($p->section ? ' / ' . $p->section->name : '')) ?: '-',
                'amount'  => (float) $p->amount,
                'mode'    => str_replace('_', ' ', $p->payment_mode),
                'date'    => \Carbon\Carbon::parse($p->payment_date)->format('d M Y'),
                'receipt' => $p->receipt_number,
            ])->toArray();
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
