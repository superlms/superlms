<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Fees extends Component
{
    use WireUiActions, WithPagination;

    // ─── View State ───────────────────────────────────────────────────────────
    public string $activeView     = 'list';
    public        $selectedSchool = null;
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
    public string $feeType              = 'one_time'; // 'one_time' | 'per_student'
    public string $oneTimeLabel          = 'Annual Platform Fee';
    public string $installmentFrequency  = 'yearly';   // monthly | quarterly | yearly
    public array  $periodAmounts         = [];         // period key => amount (string)
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

    // ─── Update panel: one_time flow (org-level installments) ─────────────────
    public array $installments = [];

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
    }

    public function backToList(): void
    {
        $this->activeView     = 'list';
        $this->selectedSchool = null;
        $this->schoolStats    = [];
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
            $this->standards = Standard::where('organization_id', $org->id)->orderBy('id')->get();
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

        // FY April 2026 → March 2027
        $fyChart = SuperAdminFeePayment::forOrg($orgId)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereYear('payment_date', 2026)
                        ->whereMonth('payment_date', '>=', 4);
                })->orWhere(function ($q) {
                    $q->whereYear('payment_date', 2027)
                        ->whereMonth('payment_date', '<=', 3);
                });
            })
            ->selectRaw('MONTH(payment_date) as month, YEAR(payment_date) as year, SUM(amount) as total')
            ->groupBy('month', 'year')
            ->get()
            ->mapWithKeys(fn($row) => ["{$row->year}-{$row->month}" => (float) $row->total])
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
        $this->feeType              = 'one_time';
        $this->oneTimeLabel         = 'Annual Platform Fee';
        $this->installmentFrequency = 'yearly';
        $this->periodAmounts        = $this->blankPeriodAmounts();
        $this->perStudentAmount     = '';
        $this->perStudentLabel      = 'Annual Platform Fee';
        $this->showFeePanel         = true;
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
            $this->feeType              = 'per_student';
            $this->perStudentAmount     = (string) $existing->amount;
            $this->perStudentLabel      = $existing->fee_label ?? 'Annual Platform Fee';
            $this->installmentFrequency = 'yearly';
            $this->periodAmounts        = $this->blankPeriodAmounts();
        } else {
            $this->feeType              = 'one_time';
            $this->oneTimeLabel         = $existing->fee_label ?? 'Annual Platform Fee';
            $this->installmentFrequency = $existing->installment_frequency ?? 'yearly';

            $stored = (array) ($existing->period_amounts ?? []);
            $amounts = [];
            foreach ($this->currentPeriodsList() as $period) {
                $amounts[$period['key']] = array_key_exists($period['key'], $stored)
                    ? (string) $stored[$period['key']]
                    : '';
            }
            $this->periodAmounts    = $amounts;
            $this->perStudentAmount = '';
            $this->perStudentLabel  = 'Annual Platform Fee';
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

        if ($this->feeType === 'one_time') {
            $this->validate([
                'oneTimeLabel'         => 'required|string|max:100',
                'installmentFrequency' => 'required|in:monthly,quarterly,yearly',
            ]);

            $periods = $this->currentPeriodsList();
            $amounts = [];
            $total   = 0;

            foreach ($periods as $period) {
                $val = $this->periodAmounts[$period['key']] ?? '';
                if ($val === '' || $val === null) {
                    $amounts[$period['key']] = 0;
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
                $this->notification()->error('Enter at least one period amount.');
                return;
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
                    'amount'                => round($total / max(1, count($periods)), 2),
                    'total_amount'          => $total,
                    'installment_frequency' => $this->installmentFrequency,
                    'period_amounts'        => $amounts,
                    'fee_label'             => $this->oneTimeLabel,
                    'is_active'             => true,
                ]
            );
        } else {
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

        $students = StudentDetail::with(['user', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->updateStandardId)
            ->when($this->updateSectionId, fn($q) => $q->where('section_id', $this->updateSectionId))
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
    private function mapStudentFeeRow($s, int $i, ?SuperAdminFeeStructure $structure): array
    {
        $feeAmount = (float) ($structure?->amount ?? 0);

        $payment = $structure
            ? SuperAdminFeePayment::where('student_detail_id', $s->id)
                ->where('super_admin_fee_structure_id', $structure->id)
                ->first()
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

        $payments      = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->get()
            ->keyBy('installment_period');
        $periodAmounts = (array) ($structure->period_amounts ?? []);

        $this->installments = collect($structure->installmentPeriods())->map(function ($period) use ($structure, $payments, $periodAmounts) {
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
            'payAmount' => 'required|numeric|min:0',
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
                SuperAdminFeePayment::where('super_admin_fee_structure_id', $this->payStructureId)
                    ->where('installment_period', $this->payInstallmentKey)
                    ->delete();

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
            SuperAdminFeePayment::where('student_detail_id', $this->payStudentId)
                ->where('super_admin_fee_structure_id', $this->payStructureId)
                ->delete();

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

    public function render()
    {
        $feeStructures  = collect();
        $currentFeeType = null;
        $schools        = null;
        $boards         = collect();

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
        }

        return view('livewire.super-admin.fees', compact('feeStructures', 'currentFeeType', 'schools', 'boards', 'organizations'));
    }
}
