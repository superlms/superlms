<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;

/**
 * The Fee Concession feature — shared between Admin\Fee's "Concession" tab
 * and Accounts\FeeConcessions (its own page), so the two never have a
 * chance to drift the way Fee Cycle once did. Edit this trait plus
 * `livewire.partials.fee-concession-panel` and both pages update together.
 *
 * The host component must provide `orgId(): int`, `WireUi\Traits\WireUiActions`
 * (for `notification()`), and `Livewire\WithPagination` (for `resetPage()`).
 */
trait HandlesFeeConcessions
{
    // ─── Listing filters — the page's own "Filter by:" band ─────────────────
    public string $filterConcStandardId = '';
    public string $filterConcSectionId  = '';
    public string $filterConcStudentId  = '';
    public string $filterConcDate       = ''; // Y-m-d — the day the concession was granted
    public int $concPerPage = 10;

    // ─── Add / Edit modal ────────────────────────────────────────────────────
    // Class/section picked here only cascade the Student dropdown inside the
    // modal — independent of the listing's own filters above.
    public $concFilterStandard = '';
    public $concFilterSection  = '';
    public $concStudentId      = '';
    public $concType           = 'amount'; // amount | percent
    public $concValue          = '';
    public $concFeeType        = 'all';    // academic | transport | all
    public $concReason         = '';
    public $concYear           = '2026-27';
    public $editConcessionId   = null;
    public bool $concModalOpen = false;
    public $concStudents       = [];

    // ─── View ────────────────────────────────────────────────────────────────
    public $viewingConcession = null;

    // ── Listing filters ─────────────────────────────────────────────────────

    public function updatedFilterConcStandardId(): void
    {
        $this->filterConcSectionId = '';
        $this->filterConcStudentId = '';
        $this->resetPage();
    }

    public function updatedFilterConcSectionId(): void
    {
        $this->filterConcStudentId = '';
        $this->resetPage();
    }

    public function updatedFilterConcStudentId(): void { $this->resetPage(); }
    public function updatedFilterConcDate(): void      { $this->resetPage(); }

    public function clearConcFilters(): void
    {
        $this->reset(['filterConcStandardId', 'filterConcSectionId', 'filterConcStudentId', 'filterConcDate']);
        $this->resetPage();
    }

    // ── Add / Edit modal ────────────────────────────────────────────────────

    public function updatedConcFilterStandard(): void
    {
        $this->concFilterSection = '';
        $this->concStudentId     = '';
        $this->loadConcessionStudents();
    }

    public function updatedConcFilterSection(): void
    {
        $this->concStudentId = '';
        $this->loadConcessionStudents();
    }

    private function loadConcessionStudents(): void
    {
        if (!$this->concFilterStandard) {
            $this->concStudents = [];
            return;
        }
        $this->concStudents = StudentDetail::with('user')
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $this->concFilterStandard)
            ->when($this->concFilterSection, fn ($q) => $q->where('section_id', $this->concFilterSection))
            ->orderBy('roll_no')->get();
    }

    public function openConcessionModal(?int $id = null): void
    {
        $this->resetConcessionForm();
        $this->editConcessionId = $id;

        if ($id) {
            $c = FeeConcession::where('organization_id', $this->orgId())->find($id);
            if (!$c) return;
            $this->concFilterStandard = $c->standard_id;
            $this->loadConcessionStudents();
            $this->concFilterSection = $c->section_id;
            $this->concStudentId     = $c->student_detail_id;
            $this->concType          = $c->concession_type;
            $this->concValue         = $c->value;
            $this->concFeeType       = $c->fee_type;
            $this->concReason        = $c->reason;
            $this->concYear          = $c->academic_year;
        }
        $this->concModalOpen = true;
    }

    public function closeConcessionModal(): void
    {
        $this->concModalOpen = false;
        $this->resetConcessionForm();
    }

    private function resetConcessionForm(): void
    {
        $this->reset([
            'editConcessionId', 'concFilterStandard', 'concFilterSection',
            'concStudentId', 'concType', 'concValue', 'concReason', 'concStudents',
        ]);
        $this->concType    = 'amount';
        $this->concFeeType = 'all';
        $this->concYear    = $this->currentConcessionYear();
        $this->resetValidation();
    }

    /** The academic year running now — April(this year) → March(next), e.g. "2026-27". */
    private function currentConcessionYear(): string
    {
        $now       = now();
        $startYear = $now->month >= 4 ? $now->year : $now->year - 1;
        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    public function saveConcession(): void
    {
        $this->validate([
            'concStudentId' => 'required|exists:student_details,id',
            'concType'      => 'required|in:amount,percent',
            'concValue'     => 'required|numeric|min:0.01' . ($this->concType === 'percent' ? '|max:100' : ''),
            'concFeeType'   => 'required|in:academic,transport,all',
            'concReason'    => 'nullable|string|max:255',
            'concYear'      => 'required|string|max:20',
        ]);

        $student = StudentDetail::find($this->concStudentId);

        $payload = [
            'organization_id'   => $this->orgId(),
            'student_detail_id' => $this->concStudentId,
            'standard_id'       => $student->standard_id,
            'section_id'        => $student->section_id,
            'concession_type'   => $this->concType,
            'value'             => $this->concValue,
            'fee_type'          => $this->concFeeType,
            'reason'            => $this->concReason,
            'academic_year'     => $this->concYear,
            'created_by'        => Auth::id(),
        ];

        if ($this->editConcessionId) {
            FeeConcession::where('organization_id', $this->orgId())
                ->where('id', $this->editConcessionId)->update($payload);
            $this->notification()->success('Concession updated successfully!');
        } else {
            FeeConcession::create($payload);
            $this->notification()->success('Concession added successfully!');
        }

        $this->closeConcessionModal();
    }

    // ── View ────────────────────────────────────────────────────────────────

    public function viewConcession(int $id): void
    {
        $this->viewingConcession = FeeConcession::with(['studentDetail.user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->find($id);
    }

    public function closeConcessionView(): void
    {
        $this->viewingConcession = null;
    }

    /**
     * Everything the `livewire.partials.fee-concession-panel` view needs.
     * Call from render() and merge the result into whatever view data the
     * host component returns.
     */
    protected function feeConcessionViewData(): array
    {
        $orgId = $this->orgId();

        $filterConcSections = $this->filterConcStandardId
            ? Section::where('standard_id', $this->filterConcStandardId)->where('is_active', true)->orderBy('id')->get()
            : collect();

        $filterConcStudents = $this->filterConcStandardId
            ? StudentDetail::with('user')->where('organization_id', $orgId)
                ->where('standard_id', $this->filterConcStandardId)
                ->when($this->filterConcSectionId, fn ($q) => $q->where('section_id', $this->filterConcSectionId))
                ->orderBy('roll_no')->get()
            : collect();

        $concessions = FeeConcession::with(['studentDetail.user', 'standard', 'section'])
            ->where('organization_id', $orgId)
            ->when($this->filterConcStandardId, fn ($q) => $q->where('standard_id', $this->filterConcStandardId))
            ->when($this->filterConcSectionId, fn ($q) => $q->where('section_id', $this->filterConcSectionId))
            ->when($this->filterConcStudentId, fn ($q) => $q->where('student_detail_id', $this->filterConcStudentId))
            ->when($this->filterConcDate, fn ($q) => $q->whereDate('created_at', $this->filterConcDate))
            ->orderByDesc('created_at')
            ->paginate($this->concPerPage, ['*'], 'concPage');

        $concModalSections = $this->concFilterStandard
            ? Section::where('standard_id', $this->concFilterStandard)->where('is_active', true)->orderBy('id')->get()
            : collect();

        return [
            'concessions'        => $concessions,
            'concStudents'       => $this->concStudents,
            'concModalSections'  => $concModalSections,
            'filterConcSections' => $filterConcSections,
            'filterConcStudents' => $filterConcStudents,
        ];
    }
}
