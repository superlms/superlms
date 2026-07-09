<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Fees extends Component
{
    use WireUiActions;

    // ─── View State ───────────────────────────────────────────────────────────
    public string $activeView     = 'list';
    public        $selectedSchool = null;
    public string $activeTab      = 'view_fee';

    // ─── Academic Year ────────────────────────────────────────────────────────
    public string $academicYear = '';

    // ─── Global Analytics (header chips) ─────────────────────────────────────
    public int   $totalStudentsAll  = 0;
    public float $totalFeeToCollect = 0;
    public float $totalFeeCollected = 0;
    public float $totalFeeRemaining = 0;
    public float $avgFeePerStudent  = 0;

    // ─── Schools + Filters ────────────────────────────────────────────────────
    public        $schools       = [];
    public string $search        = '';
    public string $filterFeeType = ''; // '' | 'one_time' | 'per_student'

    // ─── School Stats ─────────────────────────────────────────────────────────
    public array $schoolStats = [];

    // ─── Add/Edit Fee slide-in panel ──────────────────────────────────────────
    public bool   $showFeePanel = false;
    public        $standards    = [];
    public string $feeType              = 'one_time'; // 'one_time' | 'per_student'
    public string $oneTimeTotalAmount    = '';
    public string $oneTimeLabel          = 'Annual Platform Fee';
    public string $installmentFrequency  = 'yearly';   // monthly | quarterly | yearly
    public string $perStudentAmount      = '';
    public string $perStudentLabel       = 'Annual Platform Fee';

    // ─── Legacy per-class fee edit modal (only for old 'class_wise' rows) ─────
    public bool   $showEditModal = false;
    public        $editFeeId     = null;
    public        $editAmount    = '';
    public string $editLabel     = '';

    // ─── Update Tab: per_student flow ─────────────────────────────────────────
    public string $updateStandardId = '';
    public string $updateSectionId  = '';
    public        $updateSections   = [];
    public array  $studentFeeList   = [];

    // ─── Update Tab: one_time flow (org-level installments) ───────────────────
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
        $this->loadSchools();
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
     *   - one_time: the whole-school total (falls back to amount × students for
     *     rows saved before installments existed, where amount was the derived
     *     per-student total-÷-students figure).
     *   - per_student: flat rate × total students in the school.
     *   - class_wise (legacy): per-class rate × students in that class.
     */
    private function expectedForStructure(SuperAdminFeeStructure $fs): float
    {
        if ($fs->fee_type === 'one_time') {
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
        $this->loadSchools();
    }

    public function updatedFilterFeeType(): void
    {
        $this->loadSchools();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterFeeType']);
        $this->loadSchools();
    }

    public function loadSchools(): void
    {
        $this->schools = Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])
            ->when($this->search, fn($q) => $q->where(
                fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('school_code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterFeeType, fn($q) => $q->whereHas('feeStructures', function ($q) {
                $q->where('academic_year', $this->academicYear)
                    ->where('fee_type', $this->filterFeeType)
                    ->where('is_active', true);
            }))
            ->latest()
            ->get();
    }

    public function selectSchool($id): void
    {
        $this->selectedSchool = Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])->find($id);

        if (!$this->selectedSchool) return;

        $this->standards        = Standard::where('organization_id', $id)->orderBy('id')->get();
        $this->updateStandardId = '';
        $this->updateSectionId  = '';
        $this->updateSections   = [];
        $this->studentFeeList   = [];
        $this->installments     = [];

        $this->loadSchoolStats();
        $this->activeTab  = 'view_fee';
        $this->activeView = 'school';
    }

    public function backToList(): void
    {
        $this->activeView     = 'list';
        $this->selectedSchool = null;
        $this->standards      = [];
        $this->studentFeeList = [];
        $this->installments   = [];
        $this->schoolStats    = [];
        $this->search         = '';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'update') {
            if ($this->currentFeeType() === 'one_time') {
                $this->loadInstallments();
            } else {
                $this->updateStandardId = '';
                $this->updateSectionId  = '';
                $this->studentFeeList   = [];
                $this->updateSections   = [];
            }
        }
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

    // ─── Add/Edit Fee Panel ───────────────────────────────────────────────────

    public function openFeePanel(): void
    {
        $existing = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->whereIn('fee_type', ['one_time', 'per_student'])
            ->active()
            ->first();

        if ($existing?->fee_type === 'per_student') {
            $this->feeType             = 'per_student';
            $this->perStudentAmount    = (string) $existing->amount;
            $this->perStudentLabel     = $existing->fee_label ?? 'Annual Platform Fee';
            $this->oneTimeTotalAmount  = '';
            $this->oneTimeLabel        = 'Annual Platform Fee';
            $this->installmentFrequency = 'yearly';
        } elseif ($existing?->fee_type === 'one_time') {
            $this->feeType              = 'one_time';
            $this->oneTimeTotalAmount   = (string) ($existing->total_amount ?? '');
            $this->oneTimeLabel         = $existing->fee_label ?? 'Annual Platform Fee';
            $this->installmentFrequency = $existing->installment_frequency ?? 'yearly';
            $this->perStudentAmount     = '';
            $this->perStudentLabel      = 'Annual Platform Fee';
        } else {
            $this->feeType              = 'one_time';
            $this->oneTimeTotalAmount   = '';
            $this->oneTimeLabel         = 'Annual Platform Fee';
            $this->installmentFrequency = 'yearly';
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

        if ($this->feeType === 'one_time') {
            $this->validate([
                'oneTimeTotalAmount'   => 'required|numeric|min:1',
                'oneTimeLabel'         => 'required|string|max:100',
                'installmentFrequency' => 'required|in:monthly,quarterly,yearly',
            ]);

            $divisor        = match ($this->installmentFrequency) {
                'monthly'   => 12,
                'quarterly' => 4,
                default     => 1,
            };
            $perInstallment = round((float) $this->oneTimeTotalAmount / $divisor, 2);

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
                    'amount'                => $perInstallment,
                    'total_amount'          => $this->oneTimeTotalAmount,
                    'installment_frequency' => $this->installmentFrequency,
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
                    'fee_label'             => $this->perStudentLabel,
                    'is_active'             => true,
                ]
            );
        }

        $this->closeFeePanel();
        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->loadSchools();
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

        SuperAdminFeeStructure::where('id', $this->editFeeId)->update([
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
        $this->loadSchools();
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
    }

    public function loadStudentFeeList(): void
    {
        if (!$this->updateStandardId) {
            $this->notification()->error('Please select a class.');
            return;
        }

        $structure = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'per_student')
            ->active()
            ->first();

        $students = StudentDetail::with(['user', 'section'])
            ->where('organization_id', $this->selectedSchool->id)
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
        $structure = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'one_time')
            ->active()
            ->first();

        if (!$structure) {
            $this->installments = [];
            return;
        }

        $payments = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->get()
            ->keyBy('installment_period');

        $amount = (float) $structure->amount;

        $this->installments = collect($structure->installmentPeriods())->map(function ($period) use ($structure, $payments, $amount) {
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
        if (!$structure) {
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

            $isPaid = (float) $structure->amount > 0 && $collected + 0.01 >= (float) $structure->amount;

            SuperAdminFeePayment::updateOrCreate(
                [
                    'super_admin_fee_structure_id' => $this->payStructureId,
                    'installment_period'           => $this->payInstallmentKey,
                ],
                [
                    'organization_id'    => $this->selectedSchool->id,
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
                'organization_id' => $this->selectedSchool->id,
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
        if (!$structure || !$student) return;

        SuperAdminFeePayment::updateOrCreate(
            [
                'student_detail_id'            => $studentId,
                'super_admin_fee_structure_id' => $structureId,
            ],
            [
                'organization_id' => $this->selectedSchool->id,
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
        if (!$structure) return;

        SuperAdminFeePayment::updateOrCreate(
            [
                'super_admin_fee_structure_id' => $structureId,
                'installment_period'           => $key,
            ],
            [
                'organization_id'   => $this->selectedSchool->id,
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

        if ($this->currentFeeType() === 'one_time') {
            $this->loadInstallments();
        } else {
            $this->loadStudentFeeList();
        }

        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->notification()->success($message);
    }

    public function render()
    {
        $feeStructures  = collect();
        $currentFeeType = null;

        if ($this->activeView === 'school' && $this->selectedSchool) {
            $feeStructures = SuperAdminFeeStructure::with('standard')
                ->where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->orderBy('standard_id')
                ->get();

            $currentFeeType = $this->currentFeeType();
        }

        return view('livewire.super-admin.fees', compact('feeStructures', 'currentFeeType'));
    }
}
