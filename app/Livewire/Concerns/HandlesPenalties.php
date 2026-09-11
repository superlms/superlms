<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\AccountsNotifier;
use Illuminate\Support\Facades\Auth;

/**
 * The Penalties tab: what each student's fee cycle installments have accrued
 * in late fees, net of anything already waived or paid off, viewable by
 * student or by class — plus the Waiver slide-in that writes a penalty off.
 *
 * The per-student figures (raw accrual, waived, paid, net) all come from
 * App\Support\FeeCycleBreakdown via HandlesStudentFeeView::buildStudentFeeView(),
 * which this trait requires the host to use as well — one accrual rule for
 * the receipt, View Fee, Fee Submission and this tab alike.
 *
 * The host must provide `orgId(): int`, `WireUi\Traits\WireUiActions` (for
 * `notification()`).
 */
trait HandlesPenalties
{
    public string $penaltySubTab = 'by_student'; // by_student | by_class

    // ─── By student ──────────────────────────────────────────────────────────
    public $penaltyViewStandardId = '';
    public $penaltyViewSectionId  = '';
    public $penaltyViewStudentId  = '';
    public array $penaltyStudentView = [];

    // ─── By class ────────────────────────────────────────────────────────────
    public $penaltyClassStandardId = '';
    public $penaltyClassSectionId  = '';
    public array $penaltyClassList = [];

    // ─── Waiver slide-in ─────────────────────────────────────────────────────
    public bool $showPenaltyWaiver = false;
    public $waiverAmount      = '';
    public $waiverFeeType     = 'academic'; // academic | transport — which cycle's penalty
    public $waiverMode        = 'amount';   // amount | percent
    public $waiverCollectedBy = '';

    public function setPenaltySubTab(string $tab): void
    {
        $this->penaltySubTab = in_array($tab, ['by_student', 'by_class'], true) ? $tab : 'by_student';
    }

    // ── By student ──────────────────────────────────────────────────────────

    public function updatedPenaltyViewStandardId(): void
    {
        $this->penaltyViewSectionId = '';
        $this->penaltyViewStudentId = '';
        $this->penaltyStudentView   = [];
    }

    public function updatedPenaltyViewSectionId(): void
    {
        $this->penaltyViewStudentId = '';
        $this->penaltyStudentView   = [];
    }

    /** Picking a student loads the penalty view straight away — no second click. */
    public function updatedPenaltyViewStudentId(): void
    {
        if (!$this->penaltyViewStudentId) {
            $this->penaltyStudentView = [];
            return;
        }

        // Other screens (the class list) jump straight here with only a
        // student id, so backfill the class/section — otherwise the filter
        // band reads "Select Class" over a view that is already on screen.
        $student = StudentDetail::find($this->penaltyViewStudentId);
        if ($student) {
            $this->penaltyViewStandardId = (string) $student->standard_id;
            $this->penaltyViewSectionId  = (string) ($student->section_id ?: '');
        }

        $this->penaltyStudentView = $this->buildStudentFeeView((int) $this->penaltyViewStudentId);
    }

    public function clearPenaltyStudentFilters(): void
    {
        $this->reset(['penaltyViewStandardId', 'penaltyViewSectionId', 'penaltyViewStudentId', 'penaltyStudentView']);
    }

    // ── By class ────────────────────────────────────────────────────────────

    public function updatedPenaltyClassStandardId(): void
    {
        $this->penaltyClassSectionId = '';
        $this->penaltyClassList      = [];
    }

    public function updatedPenaltyClassSectionId(): void
    {
        $this->penaltyClassList = [];
    }

    public function clearPenaltyClassFilters(): void
    {
        $this->reset(['penaltyClassStandardId', 'penaltyClassSectionId', 'penaltyClassList']);
    }

    /** One row per student of the chosen class, with what they still owe in penalty. */
    public function loadPenaltyClassList(): void
    {
        if (!$this->penaltyClassStandardId) {
            return;
        }

        $orgId = $this->orgId();

        $students = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->penaltyClassStandardId)
            ->when($this->penaltyClassSectionId, fn ($q) => $q->where('section_id', $this->penaltyClassSectionId))
            ->orderBy('roll_no')
            ->get();

        $this->penaltyClassList = $students->map(function (StudentDetail $student) {
            $cycles    = collect($this->buildStudentFeeView($student->id)['cycles'] ?? []);
            $academic  = (float) ($cycles->firstWhere('fee_type', 'academic')['penalty_net'] ?? 0);
            $transport = (float) ($cycles->firstWhere('fee_type', 'transport')['penalty_net'] ?? 0);

            return [
                'id'                => $student->id,
                'name'              => $student->user->name ?? $student->full_name ?? '—',
                'admission_no'      => $student->admission_no ?: '—',
                'class_section'     => ($student->standard->name ?? '—')
                    . ($student->section ? ' · ' . $student->section->name : ''),
                'academic_penalty'  => round($academic, 2),
                'transport_penalty' => round($transport, 2),
                'total_penalty'     => round($academic + $transport, 2),
            ];
        })->values()->all();
    }

    /** Open one student's penalty view straight from the class list. */
    public function viewPenaltyFromClass(int $studentId): void
    {
        $this->penaltySubTab        = 'by_student';
        $this->penaltyViewStudentId = $studentId;
        $this->updatedPenaltyViewStudentId();
    }

    // ── Waiver ───────────────────────────────────────────────────────────────

    public function openPenaltyWaiver(): void
    {
        if (!$this->penaltyViewStudentId) {
            $this->notification()->error('Select a student first.');
            return;
        }
        $this->waiverAmount      = '';
        $this->waiverFeeType     = 'academic';
        $this->waiverMode        = 'amount';
        $this->waiverCollectedBy = Auth::user()->name ?? '';
        $this->resetValidation();
        $this->showPenaltyWaiver = true;
    }

    public function closePenaltyWaiver(): void
    {
        $this->showPenaltyWaiver = false;
    }

    public function savePenaltyWaiver(): void
    {
        $this->validate([
            'penaltyViewStudentId' => 'required|exists:student_details,id',
            'waiverAmount'         => 'required|numeric|min:0.01' . ($this->waiverMode === 'percent' ? '|max:100' : ''),
            'waiverFeeType'        => 'required|in:academic,transport',
            'waiverMode'           => 'required|in:amount,percent',
            'waiverCollectedBy'    => 'required|string|max:255',
        ]);

        $student = StudentDetail::find($this->penaltyViewStudentId);

        FeeConcession::create([
            'organization_id'   => $this->orgId(),
            'student_detail_id' => $this->penaltyViewStudentId,
            'standard_id'       => $student->standard_id,
            'section_id'        => $student->section_id,
            'concession_type'   => $this->waiverMode,
            'value'             => $this->waiverAmount,
            'fee_type'          => $this->waiverFeeType,
            'is_penalty'        => true,
            'reason'            => 'Penalty waiver',
            'academic_year'     => $this->penaltyAcademicYear(),
            'created_by'        => Auth::id(),
            'collected_by'      => $this->waiverCollectedBy,
        ]);

        AccountsNotifier::concession(
            'granted',
            $this->orgId(),
            $this->penaltyViewStudentId,
            $this->waiverMode,
            $this->waiverAmount,
            ucfirst($this->waiverFeeType) . ' penalty'
        );

        $this->notification()->success('Penalty waived successfully!');
        $this->showPenaltyWaiver = false;

        // Refresh whatever is on screen.
        $this->updatedPenaltyViewStudentId();
        if ($this->penaltySubTab === 'by_class' && $this->penaltyClassStandardId) {
            $this->loadPenaltyClassList();
        }
    }

    /** The academic year running now — April(this year) → March(next), e.g. "2026-27". */
    private function penaltyAcademicYear(): string
    {
        $now       = now();
        $startYear = $now->month >= 4 ? $now->year : $now->year - 1;
        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    /**
     * The dropdowns the filter band needs. Deliberately its own copies rather
     * than the host's shared $sections / $students, which other tabs also use.
     */
    protected function penaltyViewData(): array
    {
        $orgId = $this->orgId();

        $pStandards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $activeStandard = $this->penaltySubTab === 'by_student'
            ? $this->penaltyViewStandardId
            : $this->penaltyClassStandardId;

        $pSections = $activeStandard
            ? Section::where('organization_id', $orgId)
                ->where('standard_id', $activeStandard)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();

        $pStudents = $this->penaltyViewStandardId
            ? StudentDetail::with('user')
                ->where('organization_id', $orgId)
                ->where('standard_id', $this->penaltyViewStandardId)
                ->when($this->penaltyViewSectionId, fn ($q) => $q->where('section_id', $this->penaltyViewSectionId))
                ->orderBy('roll_no')->get()
            : collect();

        return [
            'pStandards' => $pStandards,
            'pSections'  => $pSections,
            'pStudents'  => $pStudents,
        ];
    }
}
