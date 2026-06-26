<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ViewFee extends Component
{
    public string $viewSubTab = 'by_student';

    // By Student filters
    public $viewStudentStandardId = '';
    public $viewStudentSectionId = '';
    public $viewStudentId = '';
    public $studentFeeView = [];

    // By Class filters
    public $viewClassStandardId = '';
    public $viewClassSectionId = '';
    public $classFeeList = [];

    // When viewing a student from the class list
    public $classViewStudentId = null;
    public $classStudentFeeView = [];

    // Header stats
    public $headerStats = [];

    public function mount(): void
    {
        $this->loadHeaderStats();
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function setViewSubTab(string $tab): void
    {
        $this->viewSubTab = $tab;
        $this->reset([
            'viewStudentStandardId', 'viewStudentSectionId', 'viewStudentId', 'studentFeeView',
            'viewClassStandardId', 'viewClassSectionId', 'classFeeList',
            'classViewStudentId', 'classStudentFeeView',
        ]);
    }

    private function loadHeaderStats(): void
    {
        $orgId = $this->orgId();

        $academicStructureTotal = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('fee_type', 'academic')
            ->sum('amount');

        $transportStructureTotal = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('fee_type', 'transport')
            ->sum('amount');

        $academicCollected = FeePayment::where('organization_id', $orgId)
            ->where('fee_type', 'academic')
            ->sum('amount');

        $transportCollected = FeePayment::where('organization_id', $orgId)
            ->where('fee_type', 'transport')
            ->sum('amount');

        $totalPenalties = FeePayment::where('organization_id', $orgId)
            ->sum('penalty_amount');

        $totalFee = $academicStructureTotal + $transportStructureTotal;
        $totalCollected = $academicCollected + $transportCollected;

        $this->headerStats = [
            'totalFee' => $totalFee,
            'academicFee' => $academicStructureTotal,
            'transportFee' => $transportStructureTotal,
            'academicCollected' => $academicCollected,
            'transportCollected' => $transportCollected,
            'totalCollected' => $totalCollected,
            'remaining' => max(0, $totalFee - $totalCollected),
            'penalties' => $totalPenalties,
        ];
    }

    // --- By Student ---

    public function updatedViewStudentStandardId(): void
    {
        $this->viewStudentSectionId = '';
        $this->viewStudentId = '';
        $this->studentFeeView = [];
    }

    public function updatedViewStudentSectionId(): void
    {
        $this->viewStudentId = '';
        $this->studentFeeView = [];
    }

    public function updatedViewStudentId(): void
    {
        $this->studentFeeView = [];
    }

    public function loadStudentFeeView(): void
    {
        if (!$this->viewStudentId) return;
        $this->studentFeeView = $this->buildStudentFeeData($this->viewStudentId);
    }

    /**
     * Build the full fee data array for a given student.
     */
    private function buildStudentFeeData(int $studentId): array
    {
        $student = StudentDetail::with(['standard', 'section', 'user'])->find($studentId);
        if (!$student) return [];

        $orgId = $this->orgId();

        // Fee structures for this student's class
        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->get();

        // All payments for this student
        $payments = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->orderByDesc('payment_date')
            ->get();

        $academicStructures = $structures->where('fee_type', 'academic');
        $transportStructures = $structures->where('fee_type', 'transport');

        $academicTotal = $academicStructures->sum('amount');
        $transportTotal = $student->transportation_required ? $transportStructures->sum('amount') : 0;
        $academicPaid = $payments->where('fee_type', 'academic')->sum('amount');
        $transportPaid = $payments->where('fee_type', 'transport')->sum('amount');
        $totalFee = $academicTotal + $transportTotal;
        $totalPaid = $academicPaid + $transportPaid;
        $totalPenalties = $payments->sum('penalty_amount');
        $totalWaivers = $payments->sum('waiver_amount');

        // Get collector names
        $submitterIds = $payments->pluck('submitted_by')->filter()->unique()->values()->toArray();
        $submitters = User::whereIn('id', $submitterIds)->pluck('name', 'id')->toArray();

        $paymentsArray = $payments->map(function ($p) use ($student, $submitters) {
            return [
                'id' => $p->id,
                'student_name' => $student->user->name ?? $student->full_name ?? '-',
                'class_section' => ($student->standard->name ?? '') . ' - ' . ($student->section->name ?? ''),
                'admission_no' => $student->admission_no,
                'fee_type' => $p->fee_type,
                'payment_mode' => $p->payment_mode,
                'amount' => $p->amount,
                'penalty_amount' => $p->penalty_amount,
                'waiver_amount' => $p->waiver_amount,
                'receipt_number' => $p->receipt_number,
                'payment_date' => $p->payment_date?->format('d M Y'),
                'collected_by' => $submitters[$p->submitted_by] ?? '-',
                'remark' => $p->remark,
            ];
        })->values()->toArray();

        return [
            'student' => [
                'id' => $student->id,
                'image' => $student->image,
                'full_name' => $student->user->name ?? $student->full_name ?? '-',
                'class_section' => ($student->standard->name ?? '-') . ' - ' . ($student->section->name ?? '-'),
                'admission_no' => $student->admission_no ?? '-',
                'phone' => $student->phone ?? '-',
                'email' => $student->email ?? '-',
                'father_name' => $student->father_name ?? '-',
                'mother_name' => $student->mother_name ?? '-',
            ],
            'academicStructures' => $academicStructures->values()->toArray(),
            'transportStructures' => $student->transportation_required ? $transportStructures->values()->toArray() : [],
            'payments' => $paymentsArray,
            'academicTotal' => $academicTotal,
            'transportTotal' => $transportTotal,
            'totalFee' => $totalFee,
            'academicPaid' => $academicPaid,
            'transportPaid' => $transportPaid,
            'totalPaid' => $totalPaid,
            'remaining' => max(0, $totalFee - $totalPaid),
            'penalties' => $totalPenalties,
            'waivers' => $totalWaivers,
            'hasTransport' => (bool) $student->transportation_required,
        ];
    }

    // --- By Class ---

    public function updatedViewClassStandardId(): void
    {
        $this->viewClassSectionId = '';
        $this->classFeeList = [];
        $this->classViewStudentId = null;
        $this->classStudentFeeView = [];
    }

    public function updatedViewClassSectionId(): void
    {
        $this->classFeeList = [];
        $this->classViewStudentId = null;
        $this->classStudentFeeView = [];
    }

    public function loadClassFeeView(): void
    {
        if (!$this->viewClassStandardId) return;

        $this->classViewStudentId = null;
        $this->classStudentFeeView = [];

        $orgId = $this->orgId();

        $students = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->viewClassStandardId)
            ->when($this->viewClassSectionId, fn($q) => $q->where('section_id', $this->viewClassSectionId))
            ->get();

        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $this->viewClassStandardId)
            ->where('is_active', true)
            ->get();

        // Batch load all payments for these students
        $studentIds = $students->pluck('id')->toArray();
        $allPayments = FeePayment::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $studentIds)
            ->get()
            ->groupBy('student_detail_id');

        $this->classFeeList = $students->map(function ($student) use ($structures, $allPayments) {
            $studentStructures = $structures->filter(function ($s) use ($student) {
                return is_null($s->section_id) || $s->section_id == $student->section_id;
            });

            $academicFee = $studentStructures->where('fee_type', 'academic')->sum('amount');
            $transportFee = $student->transportation_required
                ? $studentStructures->where('fee_type', 'transport')->sum('amount')
                : 0;

            $studentPayments = $allPayments->get($student->id, collect());
            $academicCollected = $studentPayments->where('fee_type', 'academic')->sum('amount');
            $transportCollected = $studentPayments->where('fee_type', 'transport')->sum('amount');
            $totalCollected = $academicCollected + $transportCollected;
            $totalFee = $academicFee + $transportFee;

            return [
                'id' => $student->id,
                'name' => $student->user->name ?? $student->full_name ?? '-',
                'admission_no' => $student->admission_no,
                'class_section' => ($student->standard->name ?? '-') . ' - ' . ($student->section->name ?? '-'),
                'academicFee' => $academicFee,
                'academicCollected' => $academicCollected,
                'transportFee' => $transportFee,
                'transportCollected' => $transportCollected,
                'hasTransport' => (bool) $student->transportation_required,
                'totalFee' => $totalFee,
                'totalCollected' => $totalCollected,
                'pending' => max(0, $totalFee - $totalCollected),
            ];
        })->values()->toArray();
    }

    /**
     * Open student detail view from the class list.
     */
    public function viewStudentFromClass(int $studentId): void
    {
        $this->classViewStudentId = $studentId;
        $this->classStudentFeeView = $this->buildStudentFeeData($studentId);
    }

    /**
     * Go back to the class list from student detail view.
     */
    public function backToClassList(): void
    {
        $this->classViewStudentId = null;
        $this->classStudentFeeView = [];
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('order')->get();

        $sections = collect();
        $students = collect();

        if ($this->viewSubTab === 'by_student') {
            if ($this->viewStudentStandardId) {
                $sections = Section::where('standard_id', $this->viewStudentStandardId)
                    ->where('organization_id', $orgId)->where('is_active', true)->get();
            }
            if ($this->viewStudentStandardId) {
                $students = StudentDetail::with('user')
                    ->where('organization_id', $orgId)
                    ->where('standard_id', $this->viewStudentStandardId)
                    ->when($this->viewStudentSectionId, fn($q) => $q->where('section_id', $this->viewStudentSectionId))
                    ->get();
            }
        } else {
            if ($this->viewClassStandardId) {
                $sections = Section::where('standard_id', $this->viewClassStandardId)
                    ->where('organization_id', $orgId)->where('is_active', true)->get();
            }
        }

        return view('livewire.accounts.view-fee', [
            'standards' => $standards,
            'sections' => $sections,
            'students' => $students,
        ]);
    }
}
