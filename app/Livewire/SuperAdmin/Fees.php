<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\Teacher\TeacherDetail;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Fees extends Component
{
    use WireUiActions;

    // ─── View State ───────────────────────────────────────────────────────────
    public string $activeView     = 'list';
    public        $selectedSchool = null;
    public string $activeTab      = 'add_fee';

    // ─── Academic Year ────────────────────────────────────────────────────────
    public string $academicYear = '';

    // ─── Global Analytics ─────────────────────────────────────────────────────
    public int   $totalStudentsAll  = 0;
    public float $totalFeeToCollect = 0;
    public float $totalFeeCollected = 0;
    public float $totalFeeRemaining = 0;
    public float $avgFeePerStudent  = 0;

    // ─── Schools ──────────────────────────────────────────────────────────────
    public        $schools = [];
    public string $search  = '';

    // ─── School Stats ─────────────────────────────────────────────────────────
    public array $schoolStats = [];

    // ─── Fee Structure Form ───────────────────────────────────────────────────
    public        $standards    = [];
    public array  $feeInputs    = [];
    public string $feeType           = 'class_wise';   // 'class_wise' | 'one_time'
    public string $oneTimeTotalAmount = '';
    public string $oneTimeLabel       = 'Annual Platform Fee';

    // ─── Edit Fee Modal ───────────────────────────────────────────────────────
    public bool   $showEditModal = false;
    public        $editFeeId     = null;
    public        $editAmount    = '';
    public string $editLabel     = '';

    // ─── Update Tab ───────────────────────────────────────────────────────────
    public string $updateStandardId = '';
    public string $updateSectionId  = '';
    public        $updateSections   = [];
    public array  $studentFeeList   = [];

    // ─── Mark Paid / Edit Payment Modal ──────────────────────────────────────
    public bool   $showPayModal    = false;
    public        $payStudentId    = null;
    public        $payStructureId  = null;
    public        $payAmount       = '';     // total collected so far (cumulative)
    public        $payTotalFee     = 0;      // full fee due for this student
    public        $payCollected    = 0;      // amount already collected before this edit
    public string $payStudentName  = '';
    public string $payMode         = 'cash';
    public string $payDate         = '';
    public string $payRemark       = '';
    public        $payExistingId   = null;
    public bool   $isEditPayment   = false;

    public function mount(): void
    {
        $this->academicYear = now()->year . '-' . (now()->year + 1);
        $this->payDate      = now()->format('Y-m-d');
        $this->loadGlobalStats();
        $this->loadSchools();
    }

    public function loadGlobalStats(): void
    {
        $this->totalStudentsAll = StudentDetail::count();

        $structures = SuperAdminFeeStructure::active()
            ->forYear($this->academicYear)
            ->get();

        $expected = 0;
        foreach ($structures as $fs) {
            if ($fs->fee_type === 'one_time') {
                $count = StudentDetail::where('organization_id', $fs->organization_id)->count();
            } else {
                $count = StudentDetail::where('organization_id', $fs->organization_id)
                    ->where('standard_id', $fs->standard_id)
                    ->count();
            }
            $expected += $fs->amount * $count;
        }

        $this->totalFeeToCollect = $expected;
        // Each payment row's `amount` is money actually received, so partial
        // collections count toward the collected total too.
        $this->totalFeeCollected = (float) SuperAdminFeePayment::forYear($this->academicYear)->sum('amount');
        $this->totalFeeRemaining = max(0, $this->totalFeeToCollect - $this->totalFeeCollected);
        $this->avgFeePerStudent  = $this->totalStudentsAll > 0
            ? round($this->totalFeeToCollect / $this->totalStudentsAll)
            : 0;
    }

    public function updatedSearch(): void
    {
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

        $this->standards        = Standard::where('organization_id', $id)->orderBy('name')->get();
        $this->updateStandardId = '';
        $this->updateSectionId  = '';
        $this->updateSections   = [];
        $this->studentFeeList   = [];

        $this->loadFeeInputs();
        $this->loadSchoolStats();
        $this->activeTab  = 'add_fee';
        $this->activeView = 'school';
    }

    public function backToList(): void
    {
        $this->activeView     = 'list';
        $this->selectedSchool = null;
        $this->feeInputs          = [];
        $this->standards          = [];
        $this->studentFeeList     = [];
        $this->schoolStats        = [];
        $this->feeType            = 'class_wise';
        $this->oneTimeTotalAmount = '';
        $this->oneTimeLabel       = 'Annual Platform Fee';
        $this->search         = '';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'update') {
            $this->updateStandardId = '';
            $this->updateSectionId  = '';
            $this->studentFeeList   = [];
            $this->updateSections   = [];
        }
    }

    // ─── Fee Structure ────────────────────────────────────────────────────────

    private function loadFeeInputs(): void
    {
        // Check if there is an existing one_time structure
        $oneTime = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('academic_year', $this->academicYear)
            ->where('fee_type', 'one_time')
            ->first();

        if ($oneTime) {
            $this->feeType            = 'one_time';
            $totalStudents            = StudentDetail::where('organization_id', $this->selectedSchool->id)->count();
            $this->oneTimeTotalAmount = (string) ($oneTime->amount * $totalStudents);
            $this->oneTimeLabel       = $oneTime->fee_label ?? 'Annual Platform Fee';
            $this->feeInputs          = [];
            return;
        }

        $this->feeType            = 'class_wise';
        $this->oneTimeTotalAmount = '';
        $this->oneTimeLabel       = 'Annual Platform Fee';
        $this->feeInputs     = [];

        foreach ($this->standards as $standard) {
            $existing = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('standard_id', $standard->id)
                ->where('academic_year', $this->academicYear)
                ->first();

            $this->feeInputs[$standard->id] = [
                'amount'    => $existing?->amount ?? '',
                'label'     => $existing?->fee_label ?? 'Annual Platform Fee',
                'exists'    => $existing ? true : false,
                'struct_id' => $existing?->id,
            ];
        }
    }

    private function loadSchoolStats(): void
    {
        $orgId         = $this->selectedSchool->id;
        $totalStudents = StudentDetail::where('organization_id', $orgId)->count();

        $structures = SuperAdminFeeStructure::where('organization_id', $orgId)
            ->where('academic_year', $this->academicYear)
            ->active()
            ->get();

        $expected = 0;
        foreach ($structures as $fs) {
            if ($fs->fee_type === 'one_time') {
                $count = $totalStudents;
            } else {
                $count = StudentDetail::where('organization_id', $orgId)
                    ->where('standard_id', $fs->standard_id)
                    ->count();
            }
            $expected += $fs->amount * $count;
        }

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

    public function saveFeeStructures(): void
    {
        if ($this->feeType === 'one_time') {
            $this->validate([
                'oneTimeTotalAmount' => 'required|numeric|min:1',
                'oneTimeLabel'       => 'required|string|max:100',
            ]);

            $totalStudents = StudentDetail::where('organization_id', $this->selectedSchool->id)->count();

            if ($totalStudents <= 0) {
                $this->notification()->error('Cannot set One Time fee', 'This school has no students.');
                return;
            }

            $perStudentAmount = round((float) $this->oneTimeTotalAmount / $totalStudents, 2);

            // Remove any class_wise structures for this org/year
            SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->where('fee_type', 'class_wise')
                ->delete();

            // Upsert the single one_time record (match on org + fee_type + year)
            $existing = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->where('fee_type', 'one_time')
                ->first();

            if ($existing) {
                $existing->update([
                    'amount'    => $perStudentAmount,
                    'fee_label' => $this->oneTimeLabel,
                    'is_active' => true,
                ]);
            } else {
                SuperAdminFeeStructure::create([
                    'organization_id' => $this->selectedSchool->id,
                    'fee_type'        => 'one_time',
                    'standard_id'     => null,
                    'academic_year'   => $this->academicYear,
                    'amount'          => $perStudentAmount,
                    'fee_label'       => $this->oneTimeLabel,
                    'is_active'       => true,
                ]);
            }
        } else {
            // Remove any one_time structure for this org/year
            SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->where('fee_type', 'one_time')
                ->delete();

            foreach ($this->feeInputs as $standardId => $data) {
                $amount = $data['amount'] ?? '';
                if ($amount === '' || $amount === null) continue;

                SuperAdminFeeStructure::updateOrCreate(
                    [
                        'organization_id' => $this->selectedSchool->id,
                        'standard_id'     => $standardId,
                        'academic_year'   => $this->academicYear,
                    ],
                    [
                        'fee_type'  => 'class_wise',
                        'amount'    => $amount,
                        'fee_label' => $data['label'] ?? 'Annual Platform Fee',
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->loadFeeInputs();
        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->notification()->success('Fee structure saved successfully!');
    }

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
        $this->loadFeeInputs();
        $this->loadSchoolStats();
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
            'description' => 'This will remove the fee structure for this class.',
            'icon'        => 'exclamation-circle',
            'iconColor'   => 'text-red-500',
            'accept'      => ['label' => 'Yes, delete', 'method' => 'doDeleteFee', 'params' => $id, 'color' => 'negative'],
            'reject'      => ['label' => 'No'],
        ]);
    }

    public function doDeleteFee($id): void
    {
        SuperAdminFeeStructure::find($id)?->delete();
        $this->loadFeeInputs();
        $this->loadSchoolStats();
        $this->notification()->success('Fee deleted!');
    }

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
        if ($this->feeType === 'one_time') {
            $structure = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->where('fee_type', 'one_time')
                ->active()
                ->first();

            $students = StudentDetail::with(['user', 'section', 'standard'])
                ->where('organization_id', $this->selectedSchool->id)
                ->get();

            $this->studentFeeList = $students->map(
                fn($s, $i) => $this->mapStudentFeeRow($s, $i, $structure, $s->standard?->name ?? '—')
            )->toArray();

            return;
        }

        // class_wise
        if (!$this->updateStandardId) {
            $this->notification()->error('Please select a class.');
            return;
        }

        $structure = SuperAdminFeeStructure::where('organization_id', $this->selectedSchool->id)
            ->where('standard_id', $this->updateStandardId)
            ->where('academic_year', $this->academicYear)
            ->active()
            ->first();

        $students = StudentDetail::with(['user', 'section'])
            ->where('organization_id', $this->selectedSchool->id)
            ->where('standard_id', $this->updateStandardId)
            ->when($this->updateSectionId, fn($q) => $q->where('section_id', $this->updateSectionId))
            ->get();

        $this->studentFeeList = $students->map(
            fn($s, $i) => $this->mapStudentFeeRow($s, $i, $structure, '—')
        )->toArray();
    }

    /**
     * Build one student's fee-status row. `collected` is the actual amount on
     * the payment record (partial collections included); the status is derived
     * from collected vs. the full fee — paid / partial / pending.
     */
    private function mapStudentFeeRow($s, int $i, ?SuperAdminFeeStructure $structure, string $standardLabel): array
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
            'standard'      => $standardLabel,
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

    // ─── Pay / Edit Payment Modal ─────────────────────────────────────────────

    public function openPayModal($studentId, $structureId, $totalFee, $collected = 0, $paymentId = null): void
    {
        $student = StudentDetail::with('user')->find($studentId);

        $this->payStudentId    = $studentId;
        $this->payStructureId  = $structureId;
        $this->payTotalFee     = (float) $totalFee;
        $this->payCollected    = (float) $collected;
        $this->payAmount       = $collected > 0 ? $collected : '';
        $this->payStudentName  = $student?->full_name ?? $student?->user?->name ?? 'Student';
        $this->payExistingId   = $paymentId;
        $this->isEditPayment   = $collected > 0;
        $this->payDate         = now()->format('Y-m-d');
        $this->payMode         = 'cash';
        $this->payRemark       = '';
        $this->showPayModal    = true;
    }

    public function openEditPayModal($paymentId): void
    {
        $payment = SuperAdminFeePayment::with(['feeStructure', 'studentDetail.user'])->find($paymentId);
        if (!$payment) return;

        $this->payStudentId   = $payment->student_detail_id;
        $this->payStructureId = $payment->super_admin_fee_structure_id;
        $this->payTotalFee    = (float) ($payment->feeStructure?->amount ?? 0);
        $this->payCollected   = (float) $payment->amount;
        $this->payAmount      = (float) $payment->amount;
        $this->payStudentName = $payment->studentDetail?->full_name ?? $payment->studentDetail?->user?->name ?? 'Student';
        $this->payExistingId  = $paymentId;
        $this->payMode        = $payment->payment_mode ?? 'cash';
        $this->payDate        = $payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->payRemark      = $payment->remark ?? '';
        $this->isEditPayment  = true;
        $this->showPayModal   = true;
    }

    public function closePayModal(): void
    {
        $this->showPayModal = false;
        $this->reset(['payStudentId', 'payStructureId', 'payAmount', 'payTotalFee', 'payCollected', 'payStudentName', 'payMode', 'payRemark', 'payExistingId', 'isEditPayment']);
        $this->payDate = now()->format('Y-m-d');
    }

    /**
     * Record the total amount collected so far for a student. `payAmount` is the
     * cumulative collected figure — when it reaches the full fee the record is
     * flagged paid; a smaller value leaves it partial; zero clears it back to
     * pending (the row is removed).
     */
    public function savePayment(): void
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:0',
            'payMode'   => 'required|string',
            'payDate'   => 'required|date',
        ]);

        $structure = SuperAdminFeeStructure::find($this->payStructureId);
        $student   = StudentDetail::find($this->payStudentId);

        if (!$structure || !$student) {
            $this->notification()->error('Invalid data.');
            return;
        }

        $collected = round((float) $this->payAmount, 2);

        // Zero collected → clear any record back to "pending".
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
                'standard_id'     => $structure->standard_id, // null for one_time
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

    private function afterPaymentChange(string $message): void
    {
        $this->closePayModal();
        $this->loadStudentFeeList();
        $this->loadSchoolStats();
        $this->loadGlobalStats();
        $this->notification()->success($message);
    }

    public function render()
    {
        $feeStructures = collect();

        if ($this->activeView === 'school' && $this->selectedSchool) {
            $feeStructures = SuperAdminFeeStructure::with('standard')
                ->where('organization_id', $this->selectedSchool->id)
                ->where('academic_year', $this->academicYear)
                ->orderBy('standard_id')
                ->get();
        }

        return view('livewire.super-admin.fees', compact('feeStructures'));
    }
}
