<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;

/**
 * The View Fee screen — shared verbatim between Admin\Fee's "View Fee" tab and
 * Accounts\ViewFee (its own page). The two were hand-copied and had drifted
 * badly; this trait, `livewire.partials.view-fee-header` (sub-tabs + filter
 * band) and `livewire.partials.view-fee-panel` (the body) are now the one
 * place either needs to change for both to update together.
 *
 * The per-student ledger itself comes from HandlesStudentFeeView, which this
 * trait requires the host to use as well.
 *
 * The host must provide `orgId(): int`.
 */
trait HandlesViewFee
{
    // ─── Sub-tab ─────────────────────────────────────────────────────────────
    public string $viewSubTab = 'by_student'; // by_student | by_class

    // ─── By student ──────────────────────────────────────────────────────────
    public $viewStudentStandardId = '';
    public $viewStudentSectionId  = '';
    public $viewStudentId         = '';
    public $studentFeeView        = [];

    // ─── By class ────────────────────────────────────────────────────────────
    public $viewClassStandardId = '';
    public $viewClassSectionId  = '';
    public $classFeeList        = [];
    // Drilling into one student from the class list, without losing the list.
    public $classViewStudentId  = null;
    public $classStudentFeeView = [];

    public function setViewSubTab(string $tab): void
    {
        $this->viewSubTab = in_array($tab, ['by_student', 'by_class'], true) ? $tab : 'by_student';
    }

    // ── By student ──────────────────────────────────────────────────────────

    public function updatedViewStudentStandardId(): void
    {
        $this->viewStudentSectionId = '';
        $this->viewStudentId        = '';
        $this->studentFeeView       = [];
    }

    public function updatedViewStudentSectionId(): void
    {
        $this->viewStudentId  = '';
        $this->studentFeeView = [];
    }

    /** Picking a student loads the ledger straight away — no second click. */
    public function updatedViewStudentId(): void
    {
        if (!$this->viewStudentId) {
            $this->studentFeeView = [];
            return;
        }

        // Other screens jump straight here with only a student id, so backfill
        // the class and section — otherwise the filter band reads "Select Class"
        // over a ledger that is already on screen.
        $student = StudentDetail::find($this->viewStudentId);
        if ($student) {
            $this->viewStudentStandardId = (string) $student->standard_id;
            $this->viewStudentSectionId  = (string) ($student->section_id ?: '');
        }

        $this->studentFeeView = $this->buildStudentFeeView((int) $this->viewStudentId);
    }

    public function loadStudentFeeView(): void
    {
        $this->updatedViewStudentId();
    }

    public function clearViewStudentFilters(): void
    {
        $this->reset(['viewStudentStandardId', 'viewStudentSectionId', 'viewStudentId', 'studentFeeView']);
    }

    // ── By class ────────────────────────────────────────────────────────────

    public function updatedViewClassStandardId(): void
    {
        $this->viewClassSectionId  = '';
        $this->classFeeList        = [];
        $this->classViewStudentId  = null;
        $this->classStudentFeeView = [];
    }

    public function updatedViewClassSectionId(): void
    {
        $this->classFeeList        = [];
        $this->classViewStudentId  = null;
        $this->classStudentFeeView = [];
    }

    public function clearViewClassFilters(): void
    {
        $this->reset(['viewClassStandardId', 'viewClassSectionId', 'classFeeList',
                      'classViewStudentId', 'classStudentFeeView']);
    }

    /**
     * One row per student of the chosen class: what each owes on either side
     * of the ledger, what has come in, and what is still pending.
     */
    public function loadClassFeeView(): void
    {
        if (!$this->viewClassStandardId) {
            return;
        }

        $orgId = $this->orgId();

        $students = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->viewClassStandardId)
            ->when($this->viewClassSectionId, fn ($q) => $q->where('section_id', $this->viewClassSectionId))
            ->orderBy('roll_no')
            ->get();

        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $this->viewClassStandardId)
            ->where('is_active', true)
            ->get();

        // One query for the whole class, then split per student — a payments
        // query per row turns a 60-student class into 60 round trips.
        $payments = FeePayment::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_detail_id');

        $this->classFeeList = $students->map(function (StudentDetail $student) use ($structures, $payments) {
            $own = $structures->filter(
                fn ($s) => is_null($s->section_id) || $s->section_id == $student->section_id
            );

            $hasTransport  = (bool) $student->transportation_required;
            $academicFee   = (float) $own->where('fee_type', 'academic')->sum('amount');
            $transportFee  = $hasTransport ? (float) $own->where('fee_type', 'transport')->sum('amount') : 0.0;

            $paid               = $payments->get($student->id, collect());
            $academicCollected  = (float) $paid->where('fee_type', 'academic')->sum('amount');
            $transportCollected = (float) $paid->where('fee_type', 'transport')->sum('amount');
            $totalFee           = $academicFee + $transportFee;
            $totalCollected     = $academicCollected + $transportCollected;

            return [
                'id'                 => $student->id,
                'name'               => $student->user->name ?? $student->full_name ?? '—',
                'admission_no'       => $student->admission_no ?: '—',
                'class_section'      => ($student->standard->name ?? '—')
                    . ($student->section ? ' · ' . $student->section->name : ''),
                'hasTransport'       => $hasTransport,
                'academicFee'        => round($academicFee, 2),
                'academicCollected'  => round($academicCollected, 2),
                'transportFee'       => round($transportFee, 2),
                'transportCollected' => round($transportCollected, 2),
                'totalFee'           => round($totalFee, 2),
                'totalCollected'     => round($totalCollected, 2),
                'pending'            => round(max(0, $totalFee - $totalCollected), 2),
            ];
        })->values()->all();

        $this->classViewStudentId  = null;
        $this->classStudentFeeView = [];
    }

    /** Open one student's full ledger without leaving the class list behind. */
    public function viewStudentFromClass(int $studentId): void
    {
        $this->classViewStudentId  = $studentId;
        $this->classStudentFeeView = $this->buildStudentFeeView($studentId);
    }

    public function backToClassList(): void
    {
        $this->classViewStudentId  = null;
        $this->classStudentFeeView = [];
    }

    /**
     * The dropdowns the filter band needs. Deliberately its own copies rather
     * than the host's shared $sections / $students, which other tabs also use.
     */
    protected function viewFeeViewData(): array
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $activeStandard = $this->viewSubTab === 'by_student'
            ? $this->viewStudentStandardId
            : $this->viewClassStandardId;

        $vfSections = $activeStandard
            ? Section::where('organization_id', $orgId)
                ->where('standard_id', $activeStandard)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();

        $vfStudents = $this->viewStudentStandardId
            ? StudentDetail::with('user')
                ->where('organization_id', $orgId)
                ->where('standard_id', $this->viewStudentStandardId)
                ->when($this->viewStudentSectionId, fn ($q) => $q->where('section_id', $this->viewStudentSectionId))
                ->orderBy('roll_no')->get()
            : collect();

        return [
            'vfStandards' => $standards,
            'vfSections'  => $vfSections,
            'vfStudents'  => $vfStudents,
        ];
    }
}
