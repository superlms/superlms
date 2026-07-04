<?php

namespace App\Livewire\Admin;

use App\Models\Student\AdmitCard as ModelAdmitCard;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Admit Cards — student-centric.
 *
 * Pick an exam + class (+ section) in the filter bar to list every student in
 * that class, coloured by whether an admit card has been issued. Non-issued
 * students get an "Issue" button; issued students can be viewed, printed or
 * deleted. The subject schedule (subject, date, time), seat and room on each
 * card all come from the seating-plan module (exam datesheet + seating plan) —
 * nothing is entered by hand here.
 */
class AdmitCard extends Component
{
    use WithPagination, WireUiActions;

    // ─── Filters ────────────────────────────────────────────────────────────────
    public string $search         = '';
    public string $examFilter     = '';
    public string $standardFilter = '';
    public string $sectionFilter  = '';
    public string $statusFilter   = ''; // '' | issued | not_issued
    public int    $perPage        = 15;

    // ─── Generate modal (criteria only) ──────────────────────────────────────────
    public bool   $showGenerateModal = false;
    public string $genExam       = '';
    public string $genStandard   = '';
    public string $genSection    = '';
    public string $genCriteria   = 'none'; // attendance | fee | none
    public        $genPercentage = 75;

    // ─── Print-selection modal ───────────────────────────────────────────────────
    public bool  $showPrintModal = false;
    public array $printSelected  = [];

    // ─── Delete ─────────────────────────────────────────────────────────────────
    public bool  $showDeleteModal  = false;
    public ?int  $pendingDeleteId  = null;

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    private function orgSlug(): string
    {
        // The admin routes require the URL's organization segment to equal the
        // signed-in user's organization_id (see EnsureIsAdmin) — anything else
        // gets redirected to quick-links.
        return (string) Auth::user()->organization_id;
    }

    // ─── Filter watchers ─────────────────────────────────────────────────────────
    public function updatedSearch(): void          { $this->resetPage(); }
    public function updatedExamFilter(): void       { $this->resetPage(); }
    public function updatedStandardFilter(): void   { $this->sectionFilter = ''; $this->resetPage(); }
    public function updatedSectionFilter(): void    { $this->resetPage(); }
    public function updatedStatusFilter(): void     { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'examFilter', 'standardFilter', 'sectionFilter', 'statusFilter']);
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  GENERATE (bulk, by criteria)
    // ═══════════════════════════════════════════════════════════════════════════
    public function openGenerateModal(): void
    {
        $this->resetValidation();
        $this->genExam       = $this->examFilter;
        $this->genStandard   = $this->standardFilter;
        $this->genSection    = $this->sectionFilter;
        $this->genCriteria   = 'none';
        $this->genPercentage = 75;
        $this->showGenerateModal = true;
    }

    public function closeGenerateModal(): void
    {
        $this->showGenerateModal = false;
        $this->resetValidation();
    }

    public function updatedGenStandard(): void
    {
        $this->genSection = '';
    }

    public function generateAdmitCards(): void
    {
        $this->validate([
            'genExam'       => 'required|exists:exams,id',
            'genStandard'   => 'required|exists:standards,id',
            'genPercentage' => 'required_unless:genCriteria,none|integer|min:1|max:100',
        ], [], [
            'genExam'       => 'exam',
            'genStandard'   => 'class',
            'genPercentage' => 'percentage',
        ]);

        try {
            $exam = Exam::findOrFail($this->genExam);

            $students = StudentDetail::with(['standard', 'section'])
                ->where('organization_id', $this->orgId())
                ->where('standard_id', $this->genStandard)
                ->when($this->genSection, fn ($q) => $q->where('section_id', $this->genSection))
                ->whereDoesntHave('admitCards', fn ($q) => $q->where('exam_id', $this->genExam))
                ->get();

            $eligible = $students->filter(function ($student) {
                return match ($this->genCriteria) {
                    'attendance' => $this->meetsAttendanceCriteria($student->id),
                    'fee'        => $this->meetsFeeCriteria($student),
                    default      => true,
                };
            });

            $generated = 0;
            foreach ($eligible as $student) {
                if ($this->createCardFor($student, $exam)) {
                    $generated++;
                }
            }

            $skipped = $students->count() - $generated;
            $this->closeGenerateModal();
            $this->notification()->success('Done!', "Issued {$generated} admit card(s)." . ($skipped > 0 ? " {$skipped} did not meet the criteria." : ''));
            $this->resetPage();
        } catch (\Exception $e) {
            $this->notification()->error('Error!', 'Failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  ISSUE A SINGLE STUDENT (from the listing)
    // ═══════════════════════════════════════════════════════════════════════════
    public function issueOne(int $studentId): void
    {
        if (!$this->examFilter) {
            $this->notification()->error('Pick an exam', 'Select an exam in the filter first.');
            return;
        }

        try {
            $exam    = Exam::where('organization_id', $this->orgId())->findOrFail($this->examFilter);
            $student = StudentDetail::where('organization_id', $this->orgId())->findOrFail($studentId);

            if ($this->createCardFor($student, $exam)) {
                $this->notification()->success('Issued!', "Admit card issued for {$student->full_name}.");
            } else {
                $this->notification()->info('Already issued', 'This student already has an admit card for this exam.');
            }
        } catch (\Exception $e) {
            $this->notification()->error('Error!', $e->getMessage());
        }
    }

    /**
     * Create one admit card, pulling the subject schedule from the exam datesheet.
     * Seat / room are resolved live from the seating plan at print time.
     */
    private function createCardFor(StudentDetail $student, Exam $exam): bool
    {
        if (ModelAdmitCard::where('student_detail_id', $student->id)->where('exam_id', $exam->id)->exists()) {
            return false;
        }

        $subjects = $this->subjectsFromDatesheet($exam->id, (int) $student->standard_id, $student->section_id ? (int) $student->section_id : null);

        ModelAdmitCard::create([
            'student_detail_id'   => $student->id,
            'exam_id'             => $exam->id,
            'organization_id'     => $this->orgId(),
            'admit_card_number'   => ModelAdmitCard::generateAdmitCardNumber($this->orgId(), $exam->id),
            'student_name'        => $student->full_name,
            'father_name'         => $student->father_name,
            'mother_name'         => $student->mother_name,
            'roll_number'         => $student->roll_no ?? 'N/A',
            'standard_id'         => $student->standard_id,
            'section_id'          => $student->section_id,
            'exam_name'           => $exam->exam_name,
            'academic_year'       => $exam->academic_year,
            'reporting_time'      => null,
            'exam_center'         => '',
            'exam_center_address' => '',
            'instructions'        => '',
            'allowed_items'       => [],
            'prohibited_items'    => [],
            'subjects'            => $subjects,
            'status'              => 'active',
            'issue_date'          => now(),
            'created_by'          => Auth::id(),
        ]);

        return true;
    }

    /**
     * Build the admit-card subject rows from the exam datesheet for this class.
     * Prefers a section-specific datesheet, then a class-wide one; falls back to
     * the class's mapped subjects (no schedule) if no datesheet exists yet.
     *
     * @return array<int,array<string,mixed>>
     */
    private function subjectsFromDatesheet(int $examId, int $standardId, ?int $sectionId): array
    {
        $base = ExamDatesheet::with('papers.subject')
            ->where('organization_id', $this->orgId())
            ->where('exam_id', $examId)
            ->where('standard_id', $standardId);

        $ds = null;
        if ($sectionId) {
            $ds = (clone $base)->where('section_id', $sectionId)->first();
        }
        $ds ??= (clone $base)->whereNull('section_id')->first()
            ?? (clone $base)->first();

        if ($ds && $ds->papers->isNotEmpty()) {
            return $ds->papers->map(fn ($p) => [
                'subject_id'    => (string) $p->subject_id,
                'subject_name'  => $p->subject->name ?? '',
                'exam_date'     => optional($p->exam_date)->format('Y-m-d') ?? '',
                'exam_time'     => $p->start_time ? Carbon::parse($p->start_time)->format('H:i') : '',
                'exam_end_time' => $p->end_time ? Carbon::parse($p->end_time)->format('H:i') : '',
                'exam_duration' => $this->durationLabel($p->start_time, $p->end_time),
                'shift'         => $p->shift,
                'status'        => 'eligible',
            ])->values()->toArray();
        }

        // Fallback — list the class's subjects with a blank schedule.
        $q = SectionSubject::where('organization_id', $this->orgId())->where('standard_id', $standardId);
        if ($sectionId) {
            $q->where('section_id', $sectionId);
        }
        $subjects = Subject::whereIn('id', $q->pluck('subject_id')->unique())
            ->where('is_active', true)->orderBy('id')->get();

        return $subjects->map(fn ($s) => [
            'subject_id'    => (string) $s->id,
            'subject_name'  => $s->name,
            'exam_date'     => '',
            'exam_time'     => '',
            'exam_end_time' => '',
            'exam_duration' => '',
            'shift'         => 1,
            'status'        => 'eligible',
        ])->toArray();
    }

    private function durationLabel($start, $end): string
    {
        if (!$start || !$end) {
            return '';
        }
        try {
            $mins = Carbon::parse($end)->diffInMinutes(Carbon::parse($start));
            if ($mins <= 0) {
                return '';
            }
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            $label = $h ? $h . ($h === 1 ? ' Hour' : ' Hours') : '';
            return trim($label . ($m ? " {$m} Min" : ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function meetsAttendanceCriteria(int $studentId): bool
    {
        $total = StudentAttendance::where('student_detail_id', $studentId)->count();
        if ($total === 0) {
            return true;
        }
        $present = StudentAttendance::where('student_detail_id', $studentId)->where('status', 1)->count();
        return ($present / $total * 100) >= $this->genPercentage;
    }

    private function meetsFeeCriteria(StudentDetail $student): bool
    {
        $structures = FeeStructure::where('organization_id', $this->orgId())
            ->where('is_active', true)
            ->where('standard_id', $student->standard_id)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $student->section_id))
            ->get();

        $academic  = $structures->where('fee_type', 'academic')->sum('amount');
        $transport = $student->transportation_required
            ? $structures->where('fee_type', 'transport')->sum('amount')
            : 0;
        $totalFee = $academic + $transport;

        if ($totalFee <= 0) {
            return true;
        }

        $paid = FeePayment::where('organization_id', $this->orgId())
            ->where('student_detail_id', $student->id)
            ->sum('amount');

        return ($paid / $totalFee * 100) >= $this->genPercentage;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  PRINT-SELECTION MODAL
    // ═══════════════════════════════════════════════════════════════════════════
    public function openPrintModal(): void
    {
        if (!$this->examFilter || !$this->standardFilter) {
            $this->notification()->error('Pick exam & class', 'Select an exam and a class in the filter first.');
            return;
        }
        $this->printSelected  = $this->printableCards->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->showPrintModal = true;
    }

    public function closePrintModal(): void
    {
        $this->showPrintModal = false;
    }

    public function selectAllPrint(): void
    {
        $this->printSelected = $this->printableCards->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function deselectAllPrint(): void
    {
        $this->printSelected = [];
    }

    public function printSelectedCards(): void
    {
        $ids = array_filter($this->printSelected);
        if (empty($ids)) {
            $this->notification()->error('Nothing selected', 'Select at least one student to print.');
            return;
        }

        $url = route('admin.admit-card.print-all', $this->orgSlug()) . '?' . http_build_query(['ids' => implode(',', $ids)]);
        $this->showPrintModal = false;
        $this->dispatch('open-in-new-tab', url: $url);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  DELETE
    // ═══════════════════════════════════════════════════════════════════════════
    public function confirmDelete(int $id): void
    {
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }

    public function deleteAdmitCard(): void
    {
        try {
            ModelAdmitCard::where('id', $this->pendingDeleteId)
                ->where('organization_id', $this->orgId())
                ->delete();
            $this->notification()->success('Deleted!', 'Admit card removed — student is back in the not-issued list.');
        } catch (\Exception $e) {
            $this->notification()->error('Error!', $e->getMessage());
        } finally {
            $this->cancelDelete();
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  COMPUTED
    // ═══════════════════════════════════════════════════════════════════════════
    #[\Livewire\Attributes\Computed]
    public function analytics(): array
    {
        $orgId = $this->orgId();

        $studentQuery = StudentDetail::where('organization_id', $orgId);
        if ($this->standardFilter) $studentQuery->where('standard_id', $this->standardFilter);
        if ($this->sectionFilter)  $studentQuery->where('section_id', $this->sectionFilter);
        $total = $studentQuery->count();

        $issued = 0;
        if ($this->examFilter) {
            $cardQuery = ModelAdmitCard::where('organization_id', $orgId)->where('exam_id', $this->examFilter);
            if ($this->standardFilter) $cardQuery->where('standard_id', $this->standardFilter);
            if ($this->sectionFilter)  $cardQuery->where('section_id', $this->sectionFilter);
            $issued = $cardQuery->count();
        }

        return [
            'total'     => $total,
            'issued'    => $issued,
            'remaining' => max(0, $total - $issued),
        ];
    }

    #[\Livewire\Attributes\Computed]
    public function exams()
    {
        return Exam::where('organization_id', $this->orgId())
            ->orderByDesc('start_date')->get();
    }

    #[\Livewire\Attributes\Computed]
    public function standards()
    {
        return Standard::where('organization_id', $this->orgId())->orderBy('id')->get();
    }

    #[\Livewire\Attributes\Computed]
    public function filterSections()
    {
        if (!$this->standardFilter) return collect();
        return Section::where('standard_id', $this->standardFilter)
            ->where('organization_id', $this->orgId())->orderBy('id')->get();
    }

    #[\Livewire\Attributes\Computed]
    public function genSections()
    {
        if (!$this->genStandard) return collect();
        return Section::where('standard_id', $this->genStandard)
            ->where('organization_id', $this->orgId())->orderBy('id')->get();
    }

    /** Issued cards for the current exam + class (+ section) — used by print modal. */
    #[\Livewire\Attributes\Computed]
    public function printableCards()
    {
        if (!$this->examFilter || !$this->standardFilter) return collect();
        return ModelAdmitCard::with('studentDetail:id,full_name,admission_no')
            ->where('organization_id', $this->orgId())
            ->where('exam_id', $this->examFilter)
            ->where('standard_id', $this->standardFilter)
            ->when($this->sectionFilter, fn ($q) => $q->where('section_id', $this->sectionFilter))
            ->orderBy('roll_number')
            ->get();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  RENDER
    // ═══════════════════════════════════════════════════════════════════════════
    public function render()
    {
        $org       = Auth::user()->organization;
        $ready     = $this->examFilter && $this->standardFilter;
        $students  = null;
        $issued    = collect();

        if ($ready) {
            $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
                ->where('organization_id', $this->orgId())
                ->where('standard_id', $this->standardFilter)
                ->when($this->sectionFilter, fn ($q) => $q->where('section_id', $this->sectionFilter))
                ->when($this->search, fn ($q) => $q->where(fn ($s) =>
                    $s->where('full_name', 'like', "%{$this->search}%")
                      ->orWhere('roll_no', 'like', "%{$this->search}%")
                      ->orWhere('admission_no', 'like', "%{$this->search}%")
                ))
                ->when($this->statusFilter === 'issued', fn ($q) =>
                    $q->whereHas('admitCards', fn ($a) => $a->where('exam_id', $this->examFilter)))
                ->when($this->statusFilter === 'not_issued', fn ($q) =>
                    $q->whereDoesntHave('admitCards', fn ($a) => $a->where('exam_id', $this->examFilter)))
                ->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')
                ->paginate($this->perPage);

            $issued = ModelAdmitCard::where('organization_id', $this->orgId())
                ->where('exam_id', $this->examFilter)
                ->whereIn('student_detail_id', collect($students->items())->pluck('id'))
                ->get()->keyBy('student_detail_id');
        }

        return view('livewire.admin.admit-card', [
            'students' => $students,
            'issued'   => $issued,
            'ready'    => $ready,
            'org'      => $org,
        ]);
    }
}
