<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Services\SchoolWhatsApp;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;
use App\Support\NameOrder;
use App\Support\SuperAdminFeeAllocator;

class Fees extends Component
{
    use WireUiActions, WithPagination;

    // ─── View State ───────────────────────────────────────────────────────────
    public string $activeView     = 'list';
    public        $selectedSchool = null;
    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'view_fee')]
    public string $activeTab      = 'view_fee';

    // ─── Academic Year ────────────────────────────────────────────────────────
    public string $academicYear = '';

    // ─── Global Analytics (header) ────────────────────────────────────────────
    public int   $totalStudentsAll  = 0;
    public float $totalFeeToCollect = 0;
    public float $totalFeeCollected = 0;
    public float $totalFeeRemaining = 0;
    public float $avgFeePerStudent  = 0;

    // ─── Schools List + Filters (student-style bar) ───────────────────────────
    public string $search             = '';
    public string $filterOrganization = '';
    public string $filterFeeType      = ''; // '' | 'one_time' | 'per_student'
    public string $filterBoard        = '';

    // ─── School Stats ─────────────────────────────────────────────────────────
    public array $schoolStats = [];

    // ─── Add/Update Fee slide-in panel ─────────────────────────────────────────
    public bool   $showFeePanel = false;
    public        $standards    = [];
    // The plan the super-admin picks in the dropdown. Maps onto the stored
    // fee_type/installment_frequency: monthly & one_time both persist as
    // 'one_time' (monthly = 12 Apr–Mar periods, one_time = a single yearly total),
    // per_student persists as 'per_student'.
    public string $feePlan              = 'monthly';   // monthly | one_time | per_student
    public string $feeType              = 'one_time';  // stored type: 'one_time' | 'per_student'
    public string $oneTimeLabel          = 'Annual Platform Fee';
    public string $oneTimeAmount         = '';          // single full-year amount (one_time plan)
    public string $installmentFrequency  = 'monthly';   // monthly | yearly (internal, derived from plan)
    public array  $periodAmounts         = [];          // period key => amount (string) — monthly plan
    public string $perStudentAmount      = '';
    public string $perStudentLabel       = 'Annual Platform Fee';

    // ─── Legacy per-class fee edit modal (only for old 'class_wise' rows) ─────
    public bool   $showEditModal = false;
    public        $editFeeId     = null;
    public        $editAmount    = '';
    public string $editLabel     = '';

    // ─── Update Fee slide-in panel (choose org → update its payments) ─────────
    public bool    $showUpdatePanel    = false;
    public string  $updateOrgId        = '';
    public string  $updateOrgName      = '';
    public ?string $updateOrgFeeType   = null; // 'one_time' | 'per_student' | null
    public string  $updateOrgFrequency = '';   // monthly | quarterly | yearly
    public string  $updateOrgFeeLabel  = '';

    // ─── Update panel: per_student flow ───────────────────────────────────────
    public string $updateStandardId = '';
    public string $updateSectionId  = '';
    public        $updateSections   = [];
    public array  $studentFeeList   = [];

    // ─── View Fee tab: per-student status list (class / section / status filter) ─
    public string $viewFeeStandardId = '';
    public string $viewFeeSectionId  = '';
    public string $viewFeeStatus     = '';  // '' | paid | partial | pending
    // 'class' — class by class, section, then A to Z: the order Amount Received
    // marks students paid in (SuperAdminFeeAllocator), so the paid ones read
    // in one run. 'name' — A to Z across the school, as the list was before.
    public string $viewFeeSort       = 'class';
    public        $viewFeeSections   = [];

    // ─── Update panel: one_time flow (org-level installments) ─────────────────
    public array $installments = [];

    // ─── Update panel: one amount received from the school, put against what
    // is due in order (SuperAdminFeeAllocator) ─────────────────────────────────
    public string $receiveAmount = '';
    public string $receiveMode   = 'cash';
    public string $receiveDate   = '';
    public string $receiveRemark = '';

    // ─── School view: Payments — each time money came (SuperAdminFeeReceipt),
    // with View and Edit (the Amount Received form, filled in again) ─────────
    public int    $paymentsShown = 10;
    public ?int   $viewPaymentId = null;
    public ?int   $editPaymentId = null;
    public string $editPayAmount = '';
    public string $editPayMode   = 'cash';
    public string $editPayDate   = '';
    public string $editPayRemark = '';

    // ─── School view: WhatsApp — the school's paid messages, on / off, and a
    // month's count of each kind with what it comes to (SchoolWhatsApp) ──────
    public string $waMonth = '';

    // ─── Record / Edit Payment Modal (shared by both flows) ───────────────────
    public bool   $showPayModal      = false;
    public        $payStudentId      = null;   // set for per_student flow
    public        $payInstallmentKey = null;   // set for one_time flow
    public        $payStructureId    = null;
    public        $payAmount         = '';     // total collected so far (cumulative)
    public        $payTotalFee       = 0;      // full amount due
    public        $payCollected      = 0;      // amount already collected before this edit
    public string $payContextLabel   = '';     // student name OR installment label
    public string $payMode           = 'cash';
    public string $payDate           = '';
    public string $payRemark         = '';
    public        $payExistingId     = null;
    public bool   $isEditPayment     = false;

    public function mount(): void
    {
        $this->academicYear = now()->year . '-' . (now()->year + 1);
        $this->payDate      = now()->format('Y-m-d');
        $this->loadGlobalStats();
    }

    // ─── Global / List ──────────────────────────────────────────────────────

    public function loadGlobalStats(): void
    {
        $this->totalStudentsAll = StudentDetail::count();

        $structures = SuperAdminFeeStructure::active()
            ->forYear($this->academicYear)
            ->get();

        $this->totalFeeToCollect = $structures->sum(fn($fs) => $this->expectedForStructure($fs));
        // Each payment row's `amount` is money actually received, so partial
        // collections count toward the collected total too.
        $this->totalFeeCollected = (float) SuperAdminFeePayment::forYear($this->academicYear)->sum('amount');
        $this->totalFeeRemaining = max(0, $this->totalFeeToCollect - $this->totalFeeCollected);
        $this->avgFeePerStudent  = $this->totalStudentsAll > 0
            ? round($this->totalFeeToCollect / $this->totalStudentsAll)
            : 0;
    }

    /**
     * The total amount a fee structure is expected to collect.
     *   - one_time: sum of its per-period amounts (falls back to total_amount,
     *     then amount × students for rows saved before per-period amounts existed).
     *   - per_student: flat rate × total students in the school.
     *   - class_wise (legacy): per-class rate × students in that class.
     */
    private function expectedForStructure(SuperAdminFeeStructure $fs): float
    {
        if ($fs->fee_type === 'one_time') {
            if (!empty($fs->period_amounts)) {
                return (float) array_sum($fs->period_amounts);
            }

            return $fs->total_amount !== null
                ? (float) $fs->total_amount
                : (float) $fs->amount * StudentDetail::where('organization_id', $fs->organization_id)->count();
        }

        if ($fs->fee_type === 'per_student') {
            return (float) $fs->amount * StudentDetail::where('organization_id', $fs->organization_id)->count();
        }

        // legacy class_wise
        $count = StudentDetail::where('organization_id', $fs->organization_id)
            ->where('standard_id', $fs->standard_id)
            ->count();

        return (float) $fs->amount * $count;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterOrganization(): void
    {
        $this->resetPage();
    }

    public function updatedFilterFeeType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBoard(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterOrganization', 'filterFeeType', 'filterBoard']);
        $this->resetPage();
    }

    /** Every selectable board — fixed catalog merged with any legacy values already in use. */
    private function boardOptions()
    {
        return collect(\App\Helpers\Constants::BOARD)
            ->merge(
                Organization::whereNotNull('education_board')
                    ->where('education_board', '<>', '')
                    ->distinct()
                    ->pluck('education_board')
            )
            ->unique(fn($b) => mb_strtoupper(trim($b)))
            ->sort()
            ->values();
    }

    private function schoolsQuery()
    {
        return Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])
            ->when($this->search, fn($q) => $q->where(
                fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('school_code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterOrganization, fn($q) => $q->where('id', $this->filterOrganization))
            ->when($this->filterBoard, fn($q) => $q->where('education_board', $this->filterBoard))
            ->when($this->filterFeeType, fn($q) => $q->whereHas('feeStructures', function ($q) {
                $q->where('academic_year', $this->academicYear)
                    ->where('fee_type', $this->filterFeeType)
                    ->where('is_active', true);
            }))
            ->latest();
    }

    public function selectSchool($id): void
    {
        $this->selectedSchool = Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])->find($id);

        if (!$this->selectedSchool) return;

        $this->loadSchoolStats();
        $this->activeTab  = 'analytics';
        $this->activeView = 'school';
        $this->waMonth    = '';
    }

    public function backToList(): void
    {
        $this->activeView     = 'list';
        $this->selectedSchool = null;
        $this->schoolStats    = [];
        $this->paymentsShown  = 10;
        $this->viewPaymentId  = null;
        $this->waMonth        = '';
        $this->closeEditPayment();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /** The active one_time/per_student structure's type for this school+year, if any. */
    private function currentFeeType(): ?string
    {
        return SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->whereIn('fee_type', ['one_time', 'per_student'])
            ->active()
            ->value('fee_type');
    }

    /**
     * The organization the payment-update flow is acting on: the org chosen in
     * the Update Fee panel, falling back to the school open in detail view.
     */
    private function activeOrgId(): ?int
    {
        if ($this->updateOrgId !== '') {
            return (int) $this->updateOrgId;
        }

        return $this->selectedSchool?->id;
    }

    // ─── Update Fee Panel (header) ──────────────────────────────────────────

    public function openUpdatePanel(): void
    {
        $this->resetUpdateFlow();
        $this->updateOrgId        = '';
        $this->updateOrgName      = '';
        $this->updateOrgFeeType   = null;
        $this->updateOrgFrequency = '';
        $this->updateOrgFeeLabel  = '';
        $this->showUpdatePanel    = true;
    }

    public function closeUpdatePanel(): void
    {
        $this->showUpdatePanel = false;
        $this->resetUpdateFlow();
        $this->reset(['updateOrgId', 'updateOrgName', 'updateOrgFeeType', 'updateOrgFrequency', 'updateOrgFeeLabel']);
    }

    /** Open the payment-update panel already scoped to the school in detail view. */
    public function openUpdatePanelForCurrent(): void
    {
        $this->openUpdatePanel();

        if ($this->selectedSchool) {
            $this->updateOrgId = (string) $this->selectedSchool->id;
            $this->updatedUpdateOrgId();
        }
    }

    /** Choosing an org loads whichever fee structure it has set for this year. */
    public function updatedUpdateOrgId(): void
    {
        $this->resetUpdateFlow();
        $this->updateOrgName      = '';
        $this->updateOrgFeeType   = null;
        $this->updateOrgFrequency = '';
        $this->updateOrgFeeLabel  = '';

        if ($this->updateOrgId === '') {
            return;
        }

        $org = Organization::find($this->updateOrgId);
        if (!$org) {
            $this->updateOrgId = '';
            return;
        }

        $this->updateOrgName = $org->name;

        $structure = SuperAdminFeeStructure::where('organization_id', $org->id)
            ->where('academic_year', $this->academicYear)
            ->whereIn('fee_type', ['one_time', 'per_student'])
            ->active()
            ->first();

        $this->updateOrgFeeType   = $structure?->fee_type;
        $this->updateOrgFrequency = $structure?->installment_frequency ?? '';
        $this->updateOrgFeeLabel  = $structure?->fee_label ?? '';

        if ($this->updateOrgFeeType === 'one_time') {
            // monthly → Apr–Mar rows, quarterly → 4 rows, yearly → single total row
            $this->loadInstallments();
        } elseif ($this->updateOrgFeeType === 'per_student') {
            $this->standards = Standard::where('organization_id', $org->id)->inClassOrder()->get();
        }
    }

    private function resetUpdateFlow(): void
    {
        $this->updateStandardId = '';
        $this->updateSectionId  = '';
        $this->updateSections   = [];
        $this->studentFeeList   = [];
        $this->installments     = [];
        $this->standards        = [];
        $this->receiveAmount    = '';
        $this->receiveMode      = 'cash';
        $this->receiveDate      = now()->format('Y-m-d');
        $this->receiveRemark    = '';
    }

    private function loadSchoolStats(): void
    {
        $orgId         = $this->selectedSchool->id;
        $totalStudents = StudentDetail::where('organization_id', $orgId)->count();

        $structures = SuperAdminFeeStructure::where('organization_id', $orgId)
            ->where('academic_year', $this->academicYear)
            ->active()
            ->get();

        $expected  = $structures->sum(fn($fs) => $this->expectedForStructure($fs));
        $collected = (float) SuperAdminFeePayment::forOrg($orgId)
            ->forYear($this->academicYear)
            ->sum('amount');

        $pct = $expected > 0 ? round(($collected / $expected) * 100) : 0;

        // The year's months (April → March of $academicYear): money received
        // on each, as Reports counts it (SuperAdminFeeReceipt), keyed "2026-4".
        $fyFrom  = \Illuminate\Support\Carbon::create((int) explode('-', $this->academicYear)[0], 4, 1);
        $fyChart = SuperAdminFeeReceipt::where('organization_id', $orgId)
            ->whereBetween('payment_date', [$fyFrom->toDateString(), $fyFrom->copy()->addYear()->subDay()->toDateString()])
            ->get(['payment_date', 'amount'])
            ->groupBy(fn ($r) => $r->payment_date->year . '-' . $r->payment_date->month)
            ->map(fn ($rows) => round((float) $rows->sum('amount'), 2))
            ->toArray();

        $this->schoolStats = [
            'total_students'   => $totalStudents,
            'total_to_collect' => $expected,
            'collected'        => $collected,
            'remaining'        => max(0, $expected - $collected),
            'avg_per_student'  => $totalStudents > 0 ? round($expected / $totalStudents) : 0,
            'collection_pct'   => $pct,
            'fy_monthly_chart' => $fyChart,
        ];
    }

    // ─── Add/Update Fee Panel ───────────────────────────────────────────────────

    /** The periods (key + label) the current frequency splits into, for this academic year. */
    public function currentPeriodsList(): array
    {
        return (new SuperAdminFeeStructure([
            'academic_year'         => $this->academicYear,
            'installment_frequency' => $this->installmentFrequency,
        ]))->installmentPeriods();
    }

    private function blankPeriodAmounts(): array
    {
        $out = [];
        foreach ($this->currentPeriodsList() as $period) {
            $out[$period['key']] = '';
        }

        return $out;
    }

    public function updatedInstallmentFrequency(): void
    {
        $preserved = $this->periodAmounts;
        $blank     = [];
        foreach ($this->currentPeriodsList() as $period) {
            $blank[$period['key']] = $preserved[$period['key']] ?? '';
        }
        $this->periodAmounts = $blank;
    }

    /** Fresh compose form — blank fields, ready for a brand-new setup. */
    public function openAddFeePanel(): void
    {
        $this->feePlan              = 'monthly';
        $this->installmentFrequency = 'monthly';
        $this->oneTimeLabel         = 'Annual Platform Fee';
        $this->oneTimeAmount        = '';
        $this->periodAmounts        = $this->blankPeriodAmounts();
        $this->perStudentAmount     = '';
        $this->perStudentLabel      = 'Annual Platform Fee';
        $this->showFeePanel         = true;
    }

    /** Switching the plan dropdown re-derives the internal frequency + rows. */
    public function updatedFeePlan(): void
    {
        $this->installmentFrequency = $this->feePlan === 'monthly' ? 'monthly' : 'yearly';

        if ($this->feePlan === 'monthly') {
            $preserved = $this->periodAmounts;
            $blank     = [];
            foreach ($this->currentPeriodsList() as $period) {
                $blank[$period['key']] = $preserved[$period['key']] ?? '';
            }
            $this->periodAmounts = $blank;
        }
    }

    /** Edit form — pre-filled from whichever structure is currently active. */
    public function openUpdateFeePanel(): void
    {
        $existing = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->whereIn('fee_type', ['one_time', 'per_student'])
            ->active()
            ->first();

        if (!$existing) {
            $this->openAddFeePanel();
            return;
        }

        if ($existing->fee_type === 'per_student') {
            $this->feePlan              = 'per_student';
            $this->perStudentAmount     = (string) $existing->amount;
            $this->perStudentLabel      = $existing->fee_label ?? 'Annual Platform Fee';
            $this->installmentFrequency = 'monthly';
            $this->periodAmounts        = $this->blankPeriodAmounts();
            $this->oneTimeAmount        = '';
        } elseif (($existing->installment_frequency ?? '') === 'monthly') {
            $this->feePlan              = 'monthly';
            $this->installmentFrequency = 'monthly';
            $this->oneTimeLabel         = $existing->fee_label ?? 'Annual Platform Fee';

            $stored  = (array) ($existing->period_amounts ?? []);
            $amounts = [];
            foreach ($this->currentPeriodsList() as $period) {
                $amounts[$period['key']] = array_key_exists($period['key'], $stored)
                    ? (string) $stored[$period['key']]
                    : '';
            }
            $this->periodAmounts    = $amounts;
            $this->oneTimeAmount    = '';
            $this->perStudentAmount = '';
            $this->perStudentLabel  = 'Annual Platform Fee';
        } else {
            // one_time yearly (or a legacy quarterly row) → single full-year amount.
            $this->feePlan              = 'one_time';
            $this->installmentFrequency = 'yearly';
            $this->oneTimeLabel         = $existing->fee_label ?? 'Annual Platform Fee';
            $this->oneTimeAmount        = (string) ($existing->total_amount
                ?? (!empty($existing->period_amounts) ? array_sum($existing->period_amounts) : $existing->amount));
            $this->periodAmounts        = $this->blankPeriodAmounts();
            $this->perStudentAmount     = '';
            $this->perStudentLabel      = 'Annual Platform Fee';
        }

        $this->showFeePanel = true;
    }

    public function closeFeePanel(): void
    {
        $this->showFeePanel = false;
    }

    public function saveFeeStructures(): void
    {
        // The new flow (one_time / per_student) fully replaces any legacy
        // per-class structure this school might still have from before.
        SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'class_wise')
            ->delete();

        if ($this->feePlan === 'per_student') {
            $this->validate([
                'perStudentAmount' => 'required|numeric|min:0.01',
                'perStudentLabel'  => 'required|string|max:100',
            ]);

            SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->where('fee_type', 'one_time')
                ->delete();

            SuperAdminFeeStructure::updateOrCreate(
                [
                    'organization_id' => $this->selectedSchool->id,
                    'academic_year'   => $this->academicYear,
                    'fee_type'        => 'per_student',
                ],
                [
                    'standard_id'           => null,
                    'amount'                => $this->perStudentAmount,
                    'total_amount'          => null,
                    'installment_frequency' => null,
                    'period_amounts'        => null,
                    'fee_label'             => $this->perStudentLabel,
                    'is_active'             => true,
                ]
            );
        } else {
            // Monthly and One Time both persist as fee_type = one_time; the
            // installment_frequency distinguishes them (monthly = 12 Apr–Mar
            // rows, yearly = one full-year total).
            $this->installmentFrequency = $this->feePlan === 'monthly' ? 'monthly' : 'yearly';

            if ($this->feePlan === 'monthly') {
                $this->validate(['oneTimeLabel' => 'required|string|max:100']);

                $periods = $this->currentPeriodsList();
                $amounts = [];
                $total   = 0;

                foreach ($periods as $period) {
                    $val = $this->periodAmounts[$period['key']] ?? '';
                    if ($val === '' || $val === null) {
                        // A month left blank is stored as null (no fee that month), not 0.
                        $amounts[$period['key']] = null;
                        continue;
                    }
                    if (!is_numeric($val) || (float) $val < 0) {
                        $this->addError('periodAmounts.' . $period['key'], 'Enter a valid amount.');
                        return;
                    }
                    $amounts[$period['key']] = round((float) $val, 2);
                    $total += $amounts[$period['key']];
                }

                if ($total <= 0) {
                    $this->notification()->error("Enter at least one month's amount.");
                    return;
                }
            } else {
                $this->validate([
                    'oneTimeLabel'  => 'required|string|max:100',
                    'oneTimeAmount' => 'required|numeric|min:0.01',
                ]);

                $periods = $this->currentPeriodsList(); // single yearly period
                $key     = $periods[0]['key'] ?? (string) $this->academicYear;
                $amounts = [$key => round((float) $this->oneTimeAmount, 2)];
                $total   = $amounts[$key];
            }

            SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->where('fee_type', 'per_student')
                ->delete();

            SuperAdminFeeStructure::updateOrCreate(
                [
                    'organization_id' => $this->selectedSchool->id,
                    'academic_year'   => $this->academicYear,
                    'fee_type'        => 'one_time',
                ],
                [
                    'standard_id'           => null,
                    'amount'                => round($total / max(1, count($amounts)), 2),
                    'total_amount'          => $total,
                    'installment_frequency' => $this->installmentFrequency,
                    'period_amounts'        => $amounts,
                    'fee_label'             => $this->oneTimeLabel,
                    'is_active'             => true,
                ]
            );
        }

        $this->closeFeePanel();
        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->notification()->success('Fee structure saved successfully!');
    }

    // ─── Legacy per-class fee edit (old 'class_wise' rows only) ───────────────

    public function openEditFee($id): void
    {
        $fee = SuperAdminFeeStructure::find($id);
        if (!$fee) return;

        $this->editFeeId     = $id;
        $this->editAmount    = $fee->amount;
        $this->editLabel     = $fee->fee_label ?? '';
        $this->showEditModal = true;
    }

    public function saveEditFee(): void
    {
        $this->validate([
            'editAmount' => 'required|numeric|min:0',
            'editLabel'  => 'required|string|max:100',
        ]);

        // Instance update so model events fire (super-admin notification).
        SuperAdminFeeStructure::find($this->editFeeId)?->update([
            'amount'    => $this->editAmount,
            'fee_label' => $this->editLabel,
        ]);

        $this->showEditModal = false;
        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->notification()->success('Fee updated!');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editFeeId', 'editAmount', 'editLabel']);
    }

    public function deleteFee($id): void
    {
        $this->dialog()->confirm([
            'title'       => 'Delete Fee?',
            'description' => 'This will remove this fee structure and any payments recorded against it.',
            'icon'        => 'exclamation-circle',
            'iconColor'   => 'text-red-500',
            'accept'      => ['label' => 'Yes, delete', 'method' => 'doDeleteFee', 'params' => $id, 'color' => 'negative'],
            'reject'      => ['label' => 'No'],
        ]);
    }

    public function doDeleteFee($id): void
    {
        SuperAdminFeeStructure::find($id)?->delete();
        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->notification()->success('Fee deleted!');
    }

    // ─── Update Tab: per_student flow ──────────────────────────────────────────

    public function updatedUpdateStandardId(): void
    {
        $this->updateSectionId = '';
        $this->studentFeeList  = [];
        $this->updateSections  = $this->updateStandardId
            ? Section::where('standard_id', $this->updateStandardId)->get()
            : [];

        if ($this->updateStandardId) {
            $this->loadStudentFeeList();
        }
    }

    public function updatedUpdateSectionId(): void
    {
        if ($this->updateStandardId) {
            $this->loadStudentFeeList();
        }
    }

    public function loadStudentFeeList(): void
    {
        $orgId = $this->activeOrgId();

        if (!$orgId || !$this->updateStandardId) {
            $this->notification()->error('Please select a class.');
            return;
        }

        $structure = SuperAdminFeeStructure::where('organization_id', $orgId)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'per_student')
            ->active()
            ->first();

        $students = StudentDetail::with(['user', 'section', 'standard:id,name'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->updateStandardId)
            ->when($this->updateSectionId, fn($q) => $q->where('section_id', $this->updateSectionId))
            ->tap(fn ($q) => NameOrder::students($q))
            ->get();

        $this->studentFeeList = $students->map(
            fn($s, $i) => $this->mapStudentFeeRow($s, $i, $structure)
        )->toArray();
    }

    /**
     * Build one student's fee-status row. `collected` is the actual amount on
     * the payment record (partial collections included); the status is derived
     * from collected vs. the full fee — paid / partial / pending.
     */
    private function mapStudentFeeRow($s, int $i, ?SuperAdminFeeStructure $structure, ?\Illuminate\Support\Collection $payments = null): array
    {
        $feeAmount = (float) ($structure?->amount ?? 0);

        // $payments (keyed by student) saves a query a student on long lists.
        $payment = $structure
            ? ($payments !== null
                ? $payments->get($s->id)
                : SuperAdminFeePayment::where('student_detail_id', $s->id)
                    ->where('super_admin_fee_structure_id', $structure->id)
                    ->first())
            : null;

        $collected = $payment ? (float) $payment->amount : 0;
        $remaining = max(0, $feeAmount - $collected);

        if ($collected <= 0) {
            $status = 'pending';
        } elseif ($feeAmount > 0 && $collected + 0.01 >= $feeAmount) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        return [
            'serial'        => $i + 1,
            'id'            => $s->id,
            'name'          => $s->full_name ?? $s->user?->name ?? '—',
            'admission_no'  => $s->admission_no ?? '—',
            'mobile'        => $s->mobile_number ?? $s->user?->mobile_number ?? '—',
            'email'         => $s->user?->email ?? '—',
            'section'       => $s->section?->name ?? '—',
            'class'         => $s->relationLoaded('standard') ? $s->standard?->name : null,
            'total_fee'     => $feeAmount,
            'collected'     => $collected,
            'remaining'     => $remaining,
            'status'        => $status,
            'is_paid'       => $status === 'paid',
            'payment_id'    => $payment?->id,
            'structure_id'  => $structure?->id,
            'payment_date'  => $payment?->payment_date?->format('d M Y') ?? '—',
            'payment_mode'  => $payment?->payment_mode ?? '—',
        ];
    }

    // ─── Update Tab: one_time flow (org-level installments) ───────────────────

    public function loadInstallments(): void
    {
        $orgId = $this->activeOrgId();

        if (!$orgId) {
            $this->installments = [];
            return;
        }

        $structure = SuperAdminFeeStructure::where('organization_id', $orgId)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'one_time')
            ->active()
            ->first();

        if (!$structure) {
            $this->installments = [];
            return;
        }

        $this->installments = $this->installmentRows($structure);
    }

    /** One row per period of a one-time / monthly fee: its amount, what came and its status. */
    private function installmentRows(SuperAdminFeeStructure $structure): array
    {
        $payments      = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->get()
            ->keyBy('installment_period');
        $periodAmounts = (array) ($structure->period_amounts ?? []);

        return collect($structure->installmentPeriods())->map(function ($period) use ($structure, $payments, $periodAmounts) {
            $amount    = array_key_exists($period['key'], $periodAmounts)
                ? (float) $periodAmounts[$period['key']]
                : (float) $structure->amount;
            $payment   = $payments->get($period['key']);
            $collected = $payment ? (float) $payment->amount : 0;
            $remaining = max(0, $amount - $collected);

            if ($collected <= 0) {
                $status = 'pending';
            } elseif ($amount > 0 && $collected + 0.01 >= $amount) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }

            return [
                'key'          => $period['key'],
                'label'        => $period['label'],
                'amount'       => $amount,
                'collected'    => $collected,
                'remaining'    => $remaining,
                'status'       => $status,
                'structure_id' => $structure->id,
                'payment_id'   => $payment?->id,
                'payment_date' => $payment?->payment_date?->format('d M Y') ?? '—',
                'payment_mode' => $payment?->payment_mode ?? '—',
            ];
        })->toArray();
    }

    // ─── Update panel: amount received from the school ─────────────────────────

    /** The structure the Update Fee panel works on (as updatedUpdateOrgId finds it). */
    private function receiveStructure(): ?SuperAdminFeeStructure
    {
        $orgId = $this->activeOrgId();
        if (!$orgId) {
            return null;
        }

        return SuperAdminFeeStructure::where('organization_id', $orgId)
            ->where('academic_year', $this->academicYear)
            ->whereIn('fee_type', ['one_time', 'per_student'])
            ->active()
            ->first();
    }

    /** For the panel: the school's total due and what the amount typed would cover. */
    public function receivePreview(): array
    {
        $structure = $this->receiveStructure();
        if (!$structure) {
            return ['due' => 0, 'amount' => 0, 'over' => false, 'lines' => [], 'per_student' => false];
        }

        $dues   = SuperAdminFeeAllocator::dues($structure);
        $due    = round(array_sum(array_column($dues, 'due')), 2);
        $amount = is_numeric($this->receiveAmount) ? max(0, round((float) $this->receiveAmount, 2)) : 0;

        return [
            'due'         => $due,
            'amount'      => $amount,
            'over'        => $amount > $due + 0.005,
            'lines'       => $amount > 0 ? SuperAdminFeeAllocator::lines(SuperAdminFeeAllocator::plan($dues, $amount)) : [],
            'per_student' => $structure->fee_type === 'per_student',
        ];
    }

    /**
     * Record one amount received from the school: per student it goes to the
     * first class's students, then the next class's; monthly to April, then
     * May…; one time to the year's fee. Rows already part paid are topped up.
     */
    public function applyReceivedAmount(): void
    {
        $this->validate([
            'receiveAmount' => 'required|numeric|min:0.01',
            'receiveMode'   => 'required|in:cash,online,cheque,upi',
            'receiveDate'   => 'required|date',
            'receiveRemark' => 'nullable|string|max:255',
        ], [], [
            'receiveAmount' => 'amount',
            'receiveMode'   => 'payment mode',
            'receiveDate'   => 'payment date',
            'receiveRemark' => 'remark',
        ]);

        $structure = $this->receiveStructure();
        if (!$structure) {
            $this->notification()->error('No fee structure is set for this school.');
            return;
        }

        $amount = round((float) $this->receiveAmount, 2);
        $due    = round(array_sum(array_column(SuperAdminFeeAllocator::dues($structure), 'due')), 2);

        if ($due <= 0.005) {
            $this->addError('receiveAmount', 'Nothing is due — the whole fee is already paid.');
            return;
        }
        if ($amount > $due + 0.005) {
            $this->addError('receiveAmount', 'More than the total due (₹' . number_format($due, 0) . ').');
            return;
        }

        $result = SuperAdminFeeAllocator::apply($structure, $amount, $this->receiveMode, $this->receiveDate, $this->receiveRemark);

        $this->receiveAmount = '';
        $this->receiveRemark = '';

        $noun = $result['kind'] === 'student' ? 'student' : ($this->updateOrgFrequency === 'monthly' ? 'month' : 'fee');
        $what = $result['full'] . ' ' . $noun . ($result['full'] === 1 ? '' : 's') . ' paid in full'
            . ($result['part'] > 0 ? ', ' . $result['part'] . ' part paid' : '');

        $this->afterPaymentChange('₹' . number_format($result['applied'], 0) . ' recorded — ' . $what . '.');
    }

    // ─── School view: Payments (View / Edit) ───────────────────────────────────

    /** The school's money received this year, newest first (a receipt above ₹0 — a sum taken back is not a payment). */
    private function schoolPaymentsQuery()
    {
        return SuperAdminFeeReceipt::where('organization_id', $this->selectedSchool?->id ?? 0)
            ->where('academic_year', $this->academicYear)
            ->where('amount', '>', 0);
    }

    /** For the Payments card: the first $paymentsShown rows, how many there are and their total. */
    public function schoolPayments(): array
    {
        $query = $this->schoolPaymentsQuery();

        return [
            'rows'  => (clone $query)->with('organization:id,name')
                ->orderByDesc('payment_date')->orderByDesc('id')
                ->limit($this->paymentsShown)->get(),
            'count' => (clone $query)->count(),
            'total' => round((float) (clone $query)->sum('amount'), 2),
        ];
    }

    public function showMorePayments(): void
    {
        $this->paymentsShown += 25;
    }

    // ─── School view: WhatsApp ──────────────────────────────────────────────

    /** For the WhatsApp card: null until the tables are there (SchoolWhatsApp). */
    public function whatsAppSummary(): ?array
    {
        return $this->selectedSchool && SchoolWhatsApp::tracked()
            ? SchoolWhatsApp::summary((int) $this->selectedSchool->id, $this->waMonth ?: null)
            : null;
    }

    /** Turn one of the school's paid messages (Absent alert, Fee received) on or off. */
    public function toggleWhatsApp(string $feature): void
    {
        if (!$this->selectedSchool || !SchoolWhatsApp::tracked() || !(SchoolWhatsApp::FEATURES[$feature][2] ?? false)) {
            return;
        }

        $orgId = (int) $this->selectedSchool->id;
        $on    = !SchoolWhatsApp::enabled($orgId, $feature);
        SchoolWhatsApp::setEnabled($orgId, $feature, $on);

        $this->notification()->success(SchoolWhatsApp::FEATURES[$feature][0] . ($on ? ' is on' : ' is off') . ' for ' . $this->selectedSchool->name . '.');
    }

    private function findSchoolPayment($id): ?SuperAdminFeeReceipt
    {
        return $id && $this->selectedSchool
            ? SuperAdminFeeReceipt::with(['organization:id,name', 'feeStructure'])
                ->where('organization_id', $this->selectedSchool->id)
                ->where('amount', '>', 0)
                ->find($id)
            : null;
    }

    public function viewPayment($id): void
    {
        if ($this->findSchoolPayment($id)) {
            $this->closeEditPayment();
            $this->viewPaymentId = (int) $id;
        }
    }

    public function closeViewPayment(): void
    {
        $this->viewPaymentId = null;
    }

    public function viewedPayment(): ?SuperAdminFeeReceipt
    {
        return $this->findSchoolPayment($this->viewPaymentId);
    }

    /** Edit opens the Amount Received form with this payment's amount, mode, date and remark. */
    public function openEditPayment($id): void
    {
        $receipt = $this->findSchoolPayment($id);
        if (!$receipt) {
            return;
        }

        $this->resetErrorBag();
        $this->viewPaymentId = null;
        $this->editPaymentId = $receipt->id;
        $this->editPayAmount = rtrim(rtrim(number_format((float) $receipt->amount, 2, '.', ''), '0'), '.');
        $this->editPayMode   = $receipt->payment_mode ?: 'cash';
        $this->editPayDate   = $receipt->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->editPayRemark = (string) $receipt->remark;
    }

    public function closeEditPayment(): void
    {
        $this->editPaymentId = null;
        $this->reset(['editPayAmount', 'editPayMode', 'editPayDate', 'editPayRemark']);
        $this->resetErrorBag(['editPayAmount', 'editPayMode', 'editPayDate', 'editPayRemark']);
    }

    public function editedPayment(): ?SuperAdminFeeReceipt
    {
        return $this->findSchoolPayment($this->editPaymentId);
    }

    /** The modes the edit form offers: the Amount Received form's, and the payment's own if it was another. */
    public function editPaymentModes(): array
    {
        $modes = ['cash' => 'Cash', 'online' => 'Online', 'cheque' => 'Cheque', 'upi' => 'UPI'];
        $own   = (string) $this->editedPayment()?->payment_mode;
        if ($own !== '' && !isset($modes[$own])) {
            $modes[$own] = ucwords(str_replace('_', ' ', $own));
        }

        return $modes;
    }

    /** For the edit panel: the payment's amount, what is still due, and what the new amount would change. */
    public function editPaymentPreview(): array
    {
        $receipt   = $this->editedPayment();
        $structure = $receipt?->feeStructure;
        $old       = round((float) ($receipt?->amount ?? 0), 2);
        $new       = is_numeric($this->editPayAmount) ? max(0, round((float) $this->editPayAmount, 2)) : 0;
        $delta     = round($new - $old, 2);
        $dues      = $receipt && $structure ? SuperAdminFeeAllocator::duesForEdit($receipt, $structure) : [];
        $due       = round(array_sum(array_column($dues, 'due')), 2);

        return [
            'old'         => $old,
            'new'         => $new,
            'delta'       => $delta,
            'due'         => $due,
            'max'         => round($old + $due, 2),
            'over'        => $delta > $due + 0.005,
            'fixed'       => !$structure,
            'lines'       => $delta >= 0.005 && $delta <= $due + 0.005
                ? SuperAdminFeeAllocator::lines(SuperAdminFeeAllocator::plan($dues, $delta))
                : [],
            'per_student' => $structure?->fee_type === 'per_student',
            'monthly'     => $structure?->installment_frequency === 'monthly',
        ];
    }

    /** Save the edited payment: SuperAdminFeeAllocator::editReceipt adds or takes back the difference. */
    public function saveEditPayment(): void
    {
        $receipt = $this->editedPayment();
        if (!$receipt) {
            $this->closeEditPayment();
            return;
        }

        $this->validate([
            'editPayAmount' => 'required|numeric|min:0.01',
            'editPayMode'   => 'required|in:' . implode(',', array_keys($this->editPaymentModes())),
            'editPayDate'   => 'required|date',
            'editPayRemark' => 'nullable|string|max:255',
        ], [], [
            'editPayAmount' => 'amount',
            'editPayMode'   => 'payment mode',
            'editPayDate'   => 'payment date',
            'editPayRemark' => 'remark',
        ]);

        $result = SuperAdminFeeAllocator::editReceipt($receipt, (float) $this->editPayAmount, $this->editPayMode, $this->editPayDate, $this->editPayRemark);
        if (!$result['ok']) {
            $this->addError('editPayAmount', $result['error']);
            return;
        }

        $this->closeEditPayment();
        $this->afterPaymentChange('Payment updated.');
    }

    // ─── Pay / Edit Payment Modal (shared) ─────────────────────────────────────

    public function openPayModal($studentId, $structureId, $totalFee, $collected = 0, $paymentId = null): void
    {
        $student = StudentDetail::with('user')->find($studentId);

        $this->payStudentId      = $studentId;
        $this->payInstallmentKey = null;
        $this->payStructureId    = $structureId;
        $this->payTotalFee       = (float) $totalFee;
        $this->payCollected      = (float) $collected;
        $this->payAmount         = $collected > 0 ? $collected : '';
        $this->payContextLabel   = $student?->full_name ?? $student?->user?->name ?? 'Student';
        $this->payExistingId     = $paymentId;
        $this->isEditPayment     = $collected > 0;
        $this->payDate           = now()->format('Y-m-d');
        $this->payMode           = 'cash';
        $this->payRemark         = '';
        $this->showPayModal      = true;
    }

    public function openInstallmentPayModal($key, $label, $structureId, $totalFee, $collected = 0, $paymentId = null): void
    {
        $this->payStudentId      = null;
        $this->payInstallmentKey = $key;
        $this->payStructureId    = $structureId;
        $this->payTotalFee       = (float) $totalFee;
        $this->payCollected      = (float) $collected;
        $this->payAmount         = $collected > 0 ? $collected : '';
        $this->payContextLabel   = $label;
        $this->payExistingId     = $paymentId;
        $this->isEditPayment     = $collected > 0;
        $this->payDate           = now()->format('Y-m-d');
        $this->payMode           = 'cash';
        $this->payRemark         = '';
        $this->showPayModal      = true;
    }

    /** "Mark Paid" (per-student): open the payment slide-in prefilled to the full fee. */
    public function openMarkPaidModal($studentId, $structureId, $totalFee, $collected = 0, $paymentId = null): void
    {
        $this->openPayModal($studentId, $structureId, $totalFee, $collected, $paymentId);
        $this->payAmount = (float) $totalFee;
    }

    /** "Mark Paid" (one-time installment): open the payment slide-in prefilled to the full amount. */
    public function openMarkInstallmentPaidModal($key, $label, $structureId, $totalFee, $collected = 0, $paymentId = null): void
    {
        $this->openInstallmentPayModal($key, $label, $structureId, $totalFee, $collected, $paymentId);
        $this->payAmount = (float) $totalFee;
    }

    public function closePayModal(): void
    {
        $this->showPayModal = false;
        $this->reset([
            'payStudentId', 'payInstallmentKey', 'payStructureId', 'payAmount', 'payTotalFee',
            'payCollected', 'payContextLabel', 'payMode', 'payRemark', 'payExistingId', 'isEditPayment',
        ]);
        $this->payDate = now()->format('Y-m-d');
    }

    /**
     * Record the total amount collected so far — for a student (per_student
     * flow) or an org-level installment period (one_time flow). `payAmount` is
     * the cumulative collected figure — when it reaches the full fee the
     * record is flagged paid; a smaller value leaves it partial; zero clears
     * it back to pending (the row is removed).
     */
    public function savePayment(): void
    {
        $this->validate([
            // Left blank → treated as 0 → the payment is cleared back to pending.
            'payAmount' => 'nullable|numeric|min:0',
            'payMode'   => 'required|string',
            'payDate'   => 'required|date',
        ]);

        $structure = SuperAdminFeeStructure::find($this->payStructureId);
        $orgId     = $this->activeOrgId();
        if (!$structure || !$orgId) {
            $this->notification()->error('Invalid data.');
            return;
        }

        $collected = round((float) $this->payAmount, 2);

        // ── One-time org-level installment ──
        if ($this->payInstallmentKey) {
            if ($collected <= 0) {
                // One by one so the deleted event notes the money taken off (Reports).
                SuperAdminFeePayment::where('super_admin_fee_structure_id', $this->payStructureId)
                    ->where('installment_period', $this->payInstallmentKey)
                    ->get()->each->delete();

                $this->afterPaymentChange('Installment cleared — marked as pending.');
                return;
            }

            $dueAmount = (float) $this->payTotalFee;
            $isPaid    = $dueAmount > 0 && $collected + 0.01 >= $dueAmount;

            SuperAdminFeePayment::updateOrCreate(
                [
                    'super_admin_fee_structure_id' => $this->payStructureId,
                    'installment_period'           => $this->payInstallmentKey,
                ],
                [
                    'organization_id'    => $orgId,
                    'student_detail_id'  => null,
                    'standard_id'        => null,
                    'section_id'         => null,
                    'amount'             => $collected,
                    'academic_year'      => $this->academicYear,
                    'payment_mode'       => $this->payMode,
                    'payment_date'       => $this->payDate,
                    'remark'             => $this->payRemark,
                    'is_paid'            => $isPaid,
                ]
            );

            $this->afterPaymentChange(
                $isPaid ? 'Installment fully paid!' : 'Partial installment recorded — balance still due.'
            );
            return;
        }

        // ── Per-student flow ──
        $student = StudentDetail::find($this->payStudentId);
        if (!$student) {
            $this->notification()->error('Invalid data.');
            return;
        }

        if ($collected <= 0) {
            // One by one so the deleted event notes the money taken off (Reports).
            SuperAdminFeePayment::where('student_detail_id', $this->payStudentId)
                ->where('super_admin_fee_structure_id', $this->payStructureId)
                ->get()->each->delete();

            // Per-student plan: clearing the fee revokes the student's app access.
            if ($structure->fee_type === 'per_student') {
                \App\Support\FeeAccess::logoutStudent($student);
            }

            $this->afterPaymentChange('Payment cleared — marked as pending.');
            return;
        }

        $feeAmount = (float) $structure->amount;
        $isPaid    = $feeAmount > 0 && $collected + 0.01 >= $feeAmount;

        SuperAdminFeePayment::updateOrCreate(
            [
                'student_detail_id'            => $this->payStudentId,
                'super_admin_fee_structure_id' => $this->payStructureId,
            ],
            [
                'organization_id' => $orgId,
                'standard_id'     => $structure->standard_id, // null for per_student
                'section_id'      => $student->section_id,
                'amount'          => $collected,
                'academic_year'   => $this->academicYear,
                'payment_mode'    => $this->payMode,
                'payment_date'    => $this->payDate,
                'remark'          => $this->payRemark,
                'is_paid'         => $isPaid,
            ]
        );

        // Per-student plan: only a fully-paid student keeps app access — a
        // partial payment (still due) logs them out until they clear it.
        if ($structure->fee_type === 'per_student' && !$isPaid) {
            \App\Support\FeeAccess::logoutStudent($student);
        }

        $this->afterPaymentChange(
            $isPaid ? 'Fee fully paid!' : 'Partial payment recorded — balance still due.'
        );
    }

    /** Quick action: mark a student's fee as fully paid (collected = full fee). */
    public function markFullyPaid($studentId, $structureId): void
    {
        $structure = SuperAdminFeeStructure::find($structureId);
        $student   = StudentDetail::find($studentId);
        $orgId     = $this->activeOrgId();
        if (!$structure || !$student || !$orgId) return;

        SuperAdminFeePayment::updateOrCreate(
            [
                'student_detail_id'            => $studentId,
                'super_admin_fee_structure_id' => $structureId,
            ],
            [
                'organization_id' => $orgId,
                'standard_id'     => $structure->standard_id,
                'section_id'      => $student->section_id,
                'amount'          => $structure->amount,
                'academic_year'   => $this->academicYear,
                'payment_mode'    => 'cash',
                'payment_date'    => now()->format('Y-m-d'),
                'is_paid'         => true,
            ]
        );

        $this->afterPaymentChange('Marked as fully paid!');
    }

    /** Quick action: mark an installment period as fully paid. */
    public function markInstallmentPaid($key, $structureId, $amount): void
    {
        $structure = SuperAdminFeeStructure::find($structureId);
        $orgId     = $this->activeOrgId();
        if (!$structure || !$orgId) return;

        SuperAdminFeePayment::updateOrCreate(
            [
                'super_admin_fee_structure_id' => $structureId,
                'installment_period'           => $key,
            ],
            [
                'organization_id'   => $orgId,
                'student_detail_id' => null,
                'amount'            => $amount,
                'academic_year'     => $this->academicYear,
                'payment_mode'      => 'cash',
                'payment_date'      => now()->format('Y-m-d'),
                'is_paid'           => true,
            ]
        );

        $this->afterPaymentChange('Installment marked as fully paid!');
    }

    private function afterPaymentChange(string $message): void
    {
        $this->closePayModal();

        if ($this->updateOrgFeeType === 'one_time') {
            $this->loadInstallments();
        } elseif ($this->updateOrgFeeType === 'per_student' && $this->updateStandardId) {
            $this->loadStudentFeeList();
        }

        if ($this->selectedSchool) {
            $this->loadSchoolStats();
        }
        $this->loadGlobalStats();
        $this->notification()->success($message);
    }

    // ─── View Fee tab: filtered student status list ────────────────────────────

    public function updatedViewFeeStandardId(): void
    {
        $this->viewFeeSectionId = '';
        $this->viewFeeSections  = $this->viewFeeStandardId
            ? Section::where('standard_id', $this->viewFeeStandardId)->get()
            : [];
    }

    public function clearViewFeeFilters(): void
    {
        $this->reset(['viewFeeStandardId', 'viewFeeSectionId', 'viewFeeStatus', 'viewFeeSections', 'viewFeeSort']);
    }

    /** The active per-student structure for the school open in detail view. */
    public function viewFeeStructure(): ?SuperAdminFeeStructure
    {
        if (!$this->selectedSchool) {
            return null;
        }

        return SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'per_student')
            ->active()
            ->first();
    }

    /**
     * Student rows for the View Fee tab — who has paid and who hasn't — filtered
     * by class, section and status. Only per-student schools track per-student
     * fee status (one-time / monthly fees are collected org-wide).
     */
    public function viewFeeStudents(): array
    {
        $structure = $this->viewFeeStructure();
        if (!$structure) {
            return [];
        }

        $students = StudentDetail::with(['user', 'section', 'standard:id,name'])
            ->where('organization_id', $this->selectedSchool->id)
            ->when($this->viewFeeStandardId, fn ($q) => $q->where('standard_id', $this->viewFeeStandardId))
            ->when($this->viewFeeSectionId, fn ($q) => $q->where('section_id', $this->viewFeeSectionId))
            ->tap(fn ($q) => NameOrder::students($q))
            ->get();

        // Class wise (the default): the order the fee marks students paid in —
        // the school's class order, then section; A to Z stays inside a
        // section, the sort being stable. A to Z keeps the query's order.
        if ($this->viewFeeSort !== 'name') {
            $rank = array_flip(Standard::where('organization_id', $this->selectedSchool->id)
                ->inClassOrder()->pluck('id')->all());
            $students = $students->sortBy(fn ($s) => [$rank[$s->standard_id] ?? PHP_INT_MAX, mb_strtolower((string) $s->section?->name)])->values();
        }

        $payments = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->whereNotNull('student_detail_id')
            ->get()
            ->keyBy('student_detail_id');

        $rows = $students->values()
            ->map(fn ($s, $i) => $this->mapStudentFeeRow($s, $i, $structure, $payments))
            ->toArray();

        if ($this->viewFeeStatus !== '') {
            $rows = array_values(array_filter($rows, fn ($r) => $r['status'] === $this->viewFeeStatus));
        }

        return $rows;
    }

    // ─── School view (home look): the school's own fee, all on one page ───────

    /** The school's active Monthly / One Time / Per Student structure for the year. */
    public function detailStructure(): ?SuperAdminFeeStructure
    {
        if (!$this->selectedSchool) {
            return null;
        }

        return SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->whereIn('fee_type', ['one_time', 'per_student'])
            ->active()
            ->first();
    }

    /** A Monthly / One Time school's periods (as the Update Fee panel lists them). */
    public function detailPeriods(?SuperAdminFeeStructure $structure = null): array
    {
        $structure ??= $this->detailStructure();

        return $structure && $structure->fee_type === 'one_time'
            ? $this->installmentRows($structure)
            : [];
    }

    /**
     * How many students (per student) or months (monthly / one time) are paid,
     * part paid and still to pay — for the whole school, without the filters.
     */
    public function detailCounts(?SuperAdminFeeStructure $structure = null, ?array $periods = null): array
    {
        $structure ??= $this->detailStructure();
        $counts    = ['paid' => 0, 'partial' => 0, 'pending' => 0, 'total' => 0, 'unit' => 'student'];
        if (!$structure) {
            return $counts;
        }

        if ($structure->fee_type === 'per_student') {
            $fee   = (float) $structure->amount;
            $total = StudentDetail::where('organization_id', $this->selectedSchool->id)->count();
            $paid  = $partial = 0;
            foreach (SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
                ->whereNotNull('student_detail_id')->pluck('amount') as $amount) {
                $amount = (float) $amount;
                if ($amount <= 0) {
                    continue;
                }
                $fee > 0 && $amount + 0.01 >= $fee ? $paid++ : $partial++;
            }

            return ['paid' => $paid, 'partial' => $partial, 'pending' => max(0, $total - $paid - $partial), 'total' => $total, 'unit' => 'student'];
        }

        $rows = collect($periods ?? $this->installmentRows($structure))->filter(fn ($r) => $r['amount'] > 0);

        return [
            'paid'    => $rows->where('status', 'paid')->count(),
            'partial' => $rows->where('status', 'partial')->count(),
            'pending' => $rows->where('status', 'pending')->count(),
            'total'   => $rows->count(),
            'unit'    => $structure->installment_frequency === 'monthly' ? 'month' : 'fee',
        ];
    }

    public function render()
    {
        $feeStructures  = collect();
        $currentFeeType = null;
        $schools        = null;
        $boards         = collect();
        $viewStandards  = collect();

        // For the student-style School filter and the Update Fee panel's org picker.
        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        if ($this->activeView === 'list') {
            $schools = $this->schoolsQuery()->paginate(12);
            $boards  = $this->boardOptions();
        }

        if ($this->activeView === 'school' && $this->selectedSchool) {
            $feeStructures = SuperAdminFeeStructure::with('standard')
                ->where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->orderBy('standard_id')
                ->get();

            $currentFeeType = $this->currentFeeType();
            $viewStandards  = Standard::where('organization_id', $this->selectedSchool->id)->inClassOrder()->get();
        }

        return view('livewire.super-admin.fees', compact('feeStructures', 'currentFeeType', 'schools', 'boards', 'organizations', 'viewStandards'));
    }
}
