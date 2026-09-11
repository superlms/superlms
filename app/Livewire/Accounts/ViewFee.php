<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesStudentFeeView;
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
    use HandlesStudentFeeView;

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
        return $this->buildStudentFeeView($studentId);
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
            ->where('is_active', true)->orderBy('id')->get();

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
