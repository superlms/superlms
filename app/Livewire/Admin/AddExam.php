<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamPaper;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class AddExam extends Component
{
    use WireUiActions, WithPagination, WithFileUploads;

    /** Subject dropdown escape hatch — a paper that belongs to no class subject. */
    public const PAPER_SUBJECT_OTHER = 'other';

    /** An exam paper's PDF may be up to 2 MB. */
    public const PAPER_MAX_KB = 2048;

    // ─── Tabs ────────────────────────────────────────────────────────────────
    public string $activeTab = 'exams'; // 'exams' | 'syllabus' | 'papers'

    // ─── Exam form ──────────────────────────────────────────────────────────
    public $examName       = '';
    public $term           = ''; // 'Term-1' | 'Term-2'
    public $academicYear   = '';
    public $startDate      = '';
    public $endDate        = '';
    public $description    = '';
    public $isPublished    = true; // a new exam is published unless unticked
    public $examType       = '';
    public $totalMarks     = '';
    public $passingMarks   = '';
    public $usesGradingSystem = false;
    public $editId         = null;

    // ─── Modal states ───────────────────────────────────────────────────────
    public $open               = false;
    public $showViewModal      = false;
    public $viewModalTitle     = '';
    public $viewData           = [];

    // Custom delete overlay (replaces broken WireUI dialog)
    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    // ─── Exam filters ───────────────────────────────────────────────────────
    public $search             = '';
    public $perPage            = 10;
    public $filterAcademicYear = '';
    public $filterExamType     = '';
    public $filterTerm         = '';
    public $filterStatus       = '';

    // ─── Syllabus filters (Exam → Class → Section → Subject) ────────────────
    public $syllabusFilterExam     = '';
    public $syllabusFilterStandard = '';
    public $syllabusFilterSection  = '';
    public $syllabusFilterSubject  = '';

    // ─── Exam Papers tab ─────────────────────────────────────────────────────
    // Upload / edit modal
    public bool $showPaperModal   = false;
    public bool $paperIsEdit      = false;
    public $paperId               = null;
    public string $paperExam      = '';
    public string $paperStandard  = '';
    public string $paperSection   = '';
    public string $paperSubject   = ''; // subject id, or self::PAPER_SUBJECT_OTHER
    public string $paperTitle     = '';
    public string $paperDescription = '';
    public $paperFile             = null;
    public array $paperModalSections = []; // sections for the class chosen in the modal
    public array $paperModalSubjects = []; // subjects for the class (+ section) chosen in the modal

    // Filters (Exam → Class → Section → Subject)
    public string $filterPaperExam     = '';
    public string $filterPaperStandard = '';
    public string $filterPaperSection  = '';
    public string $filterPaperSubject  = '';

    // Delete confirm
    public bool $showPaperDeleteConfirm = false;
    public $paperDeleteId               = null;

    // Exam Syllabus → a subject's View: its chapters for the exam in a slide-in.
    public array $sylViewChapters = [];
    public string $sylViewTitle   = '';
    public string $sylViewSub     = '';

    // A PDF picked straight from the subjects list: a subject's Add ('add:<subject id>')
    // or a paper's Edit ('edit:<paper id>') opens the file picker, and the file is
    // saved as soon as it is uploaded (updatedQuickPaperFile).
    public $quickPaperFile       = null;
    public string $quickPaperFor = '';

    // Paper view (slide-in with the PDF) — the link is made once on opening,
    // so a refresh of the page does not reload the frame.
    public $viewPaperId           = null;
    public string $viewPaperTitle = '';
    public string $viewPaperUrl   = '';

    // ─── Syllabus modal ─────────────────────────────────────────────────────
    public bool $openSyllabusModal = false;
    public bool $sylModalIsEdit    = false; // false = add (taken chapters disabled), true = edit (taken chapters selectable + transferred on save)
    public $sylModalExamId         = '';
    public $sylModalStandardId     = '';
    public $sylModalSectionId      = '';
    public $sylModalSubjectId      = '';
    public array $sylModalChapterIds  = []; // selected chapter ids
    public array $sylModalSections = [];    // sections for selected class
    public array $sylModalSubjects = [];    // subjects for selected class+section
    public array $sylModalChapters = [];    // chapters for selected class+section+subject (each row carries owning_exam_id/name)

    // ─── Data options ───────────────────────────────────────────────────────
    public $academicYearOptions = [];
    public $examTypes = [
        'quarterly'  => 'Quarterly',
        'half_yearly' => 'Half Yearly',
        'annual'     => 'Annual',
        'unit_test'  => 'Unit Test',
        'pre_board'  => 'Pre Board',
    ];

    public $termOptions = [
        'Term-1' => 'Term-1',
        'Term-2' => 'Term-2',
    ];

    public $allStandards = [];
    public $allSubjects  = [];
    public $allExams     = [];

    // ─── Statistics ─────────────────────────────────────────────────────────
    public $totalExams       = 0;
    public $publishedExams   = 0;
    public $completedExams   = 0;
    public $upcomingExams    = 0;
    public $activeExams      = 0;
    public $totalSyllabusRows = 0;

    protected $queryString = [
        'activeTab'                => ['except' => 'exams'],
        'search'                   => ['except' => '', 'history' => false],
        'filterAcademicYear'       => ['except' => '', 'history' => false],
        'filterExamType'           => ['except' => '', 'history' => false],
        'filterTerm'               => ['except' => '', 'history' => false],
        'filterStatus'             => ['except' => '', 'history' => false],
        'syllabusFilterExam'       => ['except' => '', 'history' => false],
        'syllabusFilterStandard'   => ['except' => '', 'history' => false],
        'syllabusFilterSection'    => ['except' => '', 'history' => false],
        'syllabusFilterSubject'    => ['except' => '', 'history' => false],
        'filterPaperExam'          => ['except' => '', 'history' => false],
        'filterPaperStandard'      => ['except' => '', 'history' => false],
        'filterPaperSection'       => ['except' => '', 'history' => false],
        'filterPaperSubject'       => ['except' => '', 'history' => false],
        'perPage'                  => ['except' => 10, 'history' => false],
    ];

    public function mount(): void
    {
        // Homework tab was removed — quietly snap stale bookmarks back to Exams.
        if (!in_array($this->activeTab, ['exams', 'syllabus', 'papers'], true)) {
            $this->activeTab = 'exams';
        }

        $this->loadAcademicYearOptions();
        $this->loadLookups();
        $this->loadStatistics();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['exams', 'syllabus', 'papers'], true) ? $tab : 'exams';
        $this->resetPage();
    }

    // ─── Lookups ────────────────────────────────────────────────────────────

    public function loadAcademicYearOptions(): void
    {
        $currentYear = date('Y');
        $nextYear    = $currentYear + 1;

        $this->academicYearOptions = [
            $currentYear . '-' . $nextYear,
            $nextYear . '-' . ($nextYear + 1),
        ];
        $this->academicYear = $currentYear . '-' . $nextYear;
    }

    public function loadLookups(): void
    {
        $orgId = Auth::user()->organization_id;

        $this->allStandards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)
            ->inClassOrder()
            ->get(['id', 'name', 'code'])
            ->toArray();

        $this->allSubjects = Subject::where('organization_id', $orgId)
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->toArray();

        // Earliest exams first — matches the new default sort on the list.
        $this->allExams = Exam::where('organization_id', $orgId)
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderBy('id', 'asc')
            ->get(['id', 'exam_name', 'academic_year'])
            ->toArray();
    }

    public function loadStatistics(): void
    {
        $orgId = Auth::user()->organization_id;

        $this->totalExams     = Exam::where('organization_id', $orgId)->count();
        $this->publishedExams = Exam::where('organization_id', $orgId)
            ->withStatus(Exam::STATUS_PUBLISHED)->count();
        $this->upcomingExams  = Exam::where('organization_id', $orgId)
            ->withStatus(Exam::STATUS_UPCOMING)->count();
        $this->activeExams    = Exam::where('organization_id', $orgId)
            ->withStatus(Exam::STATUS_ACTIVE)->count();
        $this->completedExams = Exam::where('organization_id', $orgId)
            ->withStatus(Exam::STATUS_COMPLETED)->count();
        $this->totalSyllabusRows = ExamSyllabusChapter::where('organization_id', $orgId)->count();
    }

    // ─── Exam filter watchers ───────────────────────────────────────────────

    public function updatedSearch(): void             { $this->resetPage(); $this->loadStatistics(); }
    public function updatedFilterAcademicYear(): void { $this->resetPage(); $this->loadStatistics(); }
    public function updatedFilterExamType(): void     { $this->resetPage(); $this->loadStatistics(); }
    public function updatedFilterTerm(): void          { $this->resetPage(); $this->loadStatistics(); }
    public function updatedFilterStatus(): void       { $this->resetPage(); $this->loadStatistics(); }
    public function updatedPerPage(): void            { $this->resetPage(); }

    public function clearExamFilters(): void
    {
        $this->reset(['search', 'filterAcademicYear', 'filterExamType', 'filterTerm', 'filterStatus']);
        $this->resetPage();
    }

    // ─── Syllabus filter cascading (Exam → Class → Section → Subject) ───────

    public function updatedSyllabusFilterExam($value): void
    {
        // Reset downstream
        $this->syllabusFilterStandard = '';
        $this->syllabusFilterSection  = '';
        $this->syllabusFilterSubject  = '';
    }

    public function updatedSyllabusFilterStandard($value): void
    {
        $this->syllabusFilterSection = '';
        $this->syllabusFilterSubject = '';
    }

    public function updatedSyllabusFilterSection($value): void
    {
        $this->syllabusFilterSubject = '';
    }

    public function clearSyllabusFilters(): void
    {
        $this->reset(['syllabusFilterExam', 'syllabusFilterStandard', 'syllabusFilterSection', 'syllabusFilterSubject']);
    }

    // ─── Exam: Add / Edit ───────────────────────────────────────────────────

    public function onAddExam(): void
    {
        $this->resetExamForm();
        $this->open   = true;
        $this->editId = null;
    }

    public function onSave(): void
    {
        $rules = [
            'examName'      => 'required|string|max:255',
            'term'          => 'required|in:Term-1,Term-2',
            'academicYear'  => 'required|string|max:9',
            'startDate'     => 'nullable|date',
            // after_or_equal only makes sense when a start date is present;
            // applying it against an empty start date always fails validation.
            'endDate'       => 'nullable|date' . ($this->startDate ? '|after_or_equal:startDate' : ''),
            'examType'      => 'required|string',
            'usesGradingSystem' => 'boolean',
        ];

        if (!$this->usesGradingSystem) {
            $rules['totalMarks']   = 'required|integer|min:1';
            $rules['passingMarks'] = 'required|integer|min:1|lt:totalMarks';
        }

        $this->validate($rules);

        try {
            $examData = [
                'organization_id'      => Auth::user()->organization_id,
                'exam_name'            => $this->examName,
                'term'                 => $this->term,
                'academic_year'        => $this->academicYear,
                'start_date'           => $this->startDate ?: null,
                'end_date'             => $this->endDate ?: null,
                'description'          => $this->description,
                'is_published'         => $this->isPublished,
                'exam_type'            => $this->examType,
                'total_marks'          => $this->usesGradingSystem ? null : $this->totalMarks,
                'passing_marks'        => $this->usesGradingSystem ? null : $this->passingMarks,
                'created_by'           => Auth::id(),
                'updated_by'           => Auth::id(),
            ];

            if (Schema::hasColumn('exams', 'uses_grading_system')) {
                $examData['uses_grading_system'] = $this->usesGradingSystem;
            }

            if ($this->editId) {
                $exam = Exam::findOrFail($this->editId);
                $exam->fill($examData);
                $exam->save();

                $this->notification()->success('Exam updated successfully!');
            } else {
                Exam::create($examData);
                $this->notification()->success('Exam created successfully!');
            }

            $this->resetExamForm();
            $this->loadLookups();
            $this->loadStatistics();
        } catch (\Exception $e) {
            $this->notification()->error('Error saving exam', $e->getMessage());
        }
    }

    public function onEditExam($id): void
    {
        $exam = Exam::findOrFail($id);

        $this->editId          = $exam->id;
        $this->examName        = $exam->exam_name;
        $this->term            = $exam->term ?? '';
        $this->academicYear    = $exam->academic_year;
        $this->startDate       = $exam->start_date ? \Carbon\Carbon::parse($exam->start_date)->format('Y-m-d') : '';
        $this->endDate         = $exam->end_date ? \Carbon\Carbon::parse($exam->end_date)->format('Y-m-d') : '';
        $this->description     = $exam->description;
        $this->isPublished     = (bool) $exam->is_published;
        $this->examType        = $exam->exam_type;
        $this->totalMarks      = $exam->total_marks;
        $this->passingMarks    = $exam->passing_marks;
        $this->usesGradingSystem = (bool) ($exam->uses_grading_system ?? false);
        $this->open            = true;
    }

    public function resetExamForm(): void
    {
        $this->reset([
            'examName', 'term', 'academicYear', 'startDate', 'endDate', 'description',
            'isPublished', 'examType', 'totalMarks', 'passingMarks',
            'usesGradingSystem', 'editId',
        ]);
        $this->loadAcademicYearOptions();
        $this->open = false;
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetExamForm();
    }

    public function onViewExam($id): void
    {
        $exam = Exam::with(['createdBy', 'updatedBy'])->findOrFail($id);

        $this->viewModalTitle = 'Exam Details — ' . $exam->exam_name;
        $this->viewData = [
            'exam'    => $exam,
            'details' => [
                'Exam Name'     => $exam->exam_name,
                'Term'          => $exam->term ?? 'N/A',
                'Academic Year' => $exam->academic_year,
                'Start Date'    => $exam->start_date?->format('d M Y') ?? 'N/A',
                'End Date'      => $exam->end_date?->format('d M Y') ?? 'N/A',
                'Exam Type'     => $this->examTypes[$exam->exam_type] ?? $exam->exam_type,
                'Total Marks'   => ($exam->uses_grading_system ?? false) ? 'N/A (Grading)' : $exam->total_marks,
                'Passing Marks' => ($exam->uses_grading_system ?? false) ? 'N/A (Grading)' : $exam->passing_marks,
                'Status'        => $exam->statusLabel(),
                'Created By'    => $exam->createdBy->name ?? 'N/A',
                'Created'       => $exam->created_at->format('d M Y, g:i A'),
                'Last Updated'  => $exam->updated_at->format('d M Y, g:i A'),
            ],
        ];
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewData      = [];
    }

    public function onTogglePublish($id): void
    {
        try {
            $exam = Exam::findOrFail($id);
            $exam->update([
                'is_published' => !$exam->is_published,
                'updated_by'   => Auth::id(),
            ]);
            $this->notification()->success(
                'Exam ' . ($exam->is_published ? 'published' : 'unpublished') . ' successfully!'
            );
            $this->loadStatistics();
        } catch (\Exception $e) {
            $this->notification()->error('Error updating exam status', $e->getMessage());
        }
    }

    // ─── Delete (custom overlay) ────────────────────────────────────────────

    public function onDeleteExam($id): void
    {
        $this->deleteTargetId   = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        try {
            $exam = Exam::find($this->deleteTargetId);
            if ($exam) {
                // Its teachers hear first — its syllabus says who they are.
                app(\App\Services\TeacherPushNotifier::class)->examDeleting($exam);
                // Cascade delete syllabus rows for this exam
                ExamSyllabusChapter::where('exam_id', $exam->id)->delete();
                $exam->delete();
                $this->notification()->success('Exam deleted successfully!');
            }
            $this->loadStatistics();
        } catch (\Exception $e) {
            $this->notification()->error('Error deleting', $e->getMessage());
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    // ─── Syllabus modal ─────────────────────────────────────────────────────

    public function onAddSyllabus(): void
    {
        $this->resetSyllabusModal();
        $this->sylModalIsEdit    = false;
        $this->openSyllabusModal = true;
    }

    /**
     * Drill into the detail view of a specific syllabus row by setting all
     * four filters at once. The next render() then takes the
     * "mode === detail" branch and shows the selected chapters + topics.
     */
    public function onViewSyllabus($examId, $standardId, $sectionId, $subjectId): void
    {
        $this->syllabusFilterExam     = (string) $examId;
        $this->syllabusFilterStandard = (string) $standardId;
        $this->syllabusFilterSection  = $sectionId !== null ? (string) $sectionId : '';
        $this->syllabusFilterSubject  = (string) $subjectId;
        $this->activeTab              = 'syllabus';
    }

    /**
     * Open the syllabus modal pre-populated for editing the existing
     * (exam, class, section, subject) syllabus group. In edit mode chapters
     * already owned by *other* exams remain selectable — saving transfers
     * them over.
     */
    public function onEditSyllabus($examId, $standardId, $subjectId, $sectionId = null): void
    {
        $this->resetSyllabusModal();
        $this->sylModalIsEdit    = true;
        $this->sylModalExamId    = (string) $examId;
        $this->sylModalStandardId = (string) $standardId;
        $this->sylModalSectionId  = $sectionId !== null ? (string) $sectionId : '';
        $this->sylModalSubjectId  = (string) $subjectId;

        // Load the option lists directly: the updated* hooks clear everything
        // downstream, which would wipe the very selection we're restoring.
        $this->loadSylModalSections($standardId);
        $this->loadSylModalSubjects();
        $this->loadSylModalChapters(); // also re-ticks the chapters already saved

        $this->openSyllabusModal = true;
    }

    public function closeSyllabusModal(): void
    {
        $this->openSyllabusModal = false;
        $this->resetSyllabusModal();
    }

    protected function resetSyllabusModal(): void
    {
        $this->reset([
            'sylModalExamId',
            'sylModalStandardId',
            'sylModalSectionId',
            'sylModalSubjectId',
            'sylModalChapterIds',
            'sylModalSections',
            'sylModalSubjects',
            'sylModalChapters',
            'sylModalIsEdit',
        ]);
    }

    /** Sections of a class, for the syllabus modal's Section dropdown. */
    private function loadSylModalSections($standardId): void
    {
        if (!$standardId) {
            $this->sylModalSections = [];
            return;
        }

        $this->sylModalSections = Section::where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $standardId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name'])
            ->toArray();
    }

    /** Subjects of the chosen class (+ section), for the Subject dropdown. */
    private function loadSylModalSubjects(): void
    {
        $this->sylModalSubjects = $this->sylModalStandardId
            ? $this->subjectsForClass($this->sylModalStandardId, $this->sylModalSectionId ?: null)
            : [];
    }

    public function updatedSylModalStandardId($value): void
    {
        // Reset everything downstream when class changes.
        $this->sylModalSectionId   = '';
        $this->sylModalSubjectId   = '';
        $this->sylModalChapterIds  = [];
        $this->sylModalSubjects    = [];
        $this->sylModalChapters    = [];

        $this->loadSylModalSections($value);

        // Exam, class and subject are what is asked: a class with one section
        // takes it by itself (its box is not shown), and the subjects come at
        // once — a class with several sections can still narrow by one.
        if (count($this->sylModalSections) === 1) {
            $this->sylModalSectionId = (string) $this->sylModalSections[0]['id'];
        }
        $this->loadSylModalSubjects();
    }

    /** A subject's View in the syllabus list: its chapters, numbered, in a slide-in. */
    public function viewSyllabusChapters($subjectId): void
    {
        foreach ($this->getSyllabusBoard() ?? [] as $row) {
            if ((int) $row['subject_id'] === (int) $subjectId) {
                $exam  = collect($this->allExams)->firstWhere('id', (int) $this->syllabusFilterExam);
                $class = collect($this->allStandards)->firstWhere('id', (int) $this->syllabusFilterStandard);

                $this->sylViewChapters = $row['chapters'];
                $this->sylViewTitle    = $row['name'];
                $this->sylViewSub      = trim(($exam['exam_name'] ?? '') . ' · ' . ($class['name'] ?? ''), ' ·');
                return;
            }
        }
    }

    public function closeSyllabusChapters(): void
    {
        $this->reset(['sylViewChapters', 'sylViewTitle', 'sylViewSub']);
    }

    /**
     * A subject's Add in the syllabus list: the panel opens on the exam, class
     * and section the filter shows, with that subject's chapters to tick.
     */
    public function openSyllabusFor($subjectId): void
    {
        $this->resetSyllabusModal();
        $this->sylModalIsEdit     = false;
        $this->sylModalExamId     = (string) $this->syllabusFilterExam;
        $this->sylModalStandardId = (string) $this->syllabusFilterStandard;
        $this->loadSylModalSections($this->sylModalStandardId);
        $this->sylModalSectionId  = (string) $this->syllabusFilterSection;
        if ($this->sylModalSectionId === '' && count($this->sylModalSections) === 1) {
            $this->sylModalSectionId = (string) $this->sylModalSections[0]['id'];
        }
        $this->loadSylModalSubjects();
        $this->sylModalSubjectId  = (string) $subjectId;
        $this->loadSylModalChapters();

        $this->openSyllabusModal = true;
    }

    public function updatedSylModalSectionId($value): void
    {
        // Reset subject + chapters when section changes.
        $this->sylModalSubjectId  = '';
        $this->sylModalChapterIds = [];
        $this->sylModalChapters   = [];

        $this->loadSylModalSubjects();
    }

    public function updatedSylModalSubjectId($value): void
    {
        $this->sylModalChapterIds = [];
        $this->loadSylModalChapters();
    }

    /**
     * Chapters available for the chosen class (+ section) and subject, each
     * annotated with the exam that currently owns it, plus the chapters this
     * exam already has saved (pre-ticked).
     */
    private function loadSylModalChapters(): void
    {
        $value = $this->sylModalSubjectId;

        if (!$value || !$this->sylModalStandardId) {
            $this->sylModalChapters = [];
            return;
        }

        $orgId = Auth::user()->organization_id;

        // Chapters for this class + subject (section-scoped if section provided
        // AND chapters carry a section_id; otherwise show class-wide chapters).
        // The picker lists chapter names only, so topics/description stay out of it.
        $chapterQuery = Chapter::where('organization_id', $orgId)
            ->where('standard_id', $this->sylModalStandardId)
            ->where('subject_id', $value);

        if ($this->sylModalSectionId) {
            $chapterQuery->where(function ($q) {
                $q->where('section_id', $this->sylModalSectionId)
                  ->orWhereNull('section_id'); // class-wide chapter
            });
        }

        $chapters = $chapterQuery
            ->orderBy('order')
            ->get(['id', 'name', 'order'])
            ->toArray();

        // Find which chapters are already owned by ANOTHER exam's syllabus.
        // We annotate each chapter row with owning_exam_id / owning_exam_name
        // so the blade can disable (add mode) or label (edit mode) them.
        $chapterIds = array_column($chapters, 'id');
        $ownership  = [];

        if (!empty($chapterIds)) {
            $ownership = ExamSyllabusChapter::with('exam:id,exam_name,academic_year')
                ->where('organization_id', $orgId)
                ->whereIn('chapter_id', $chapterIds)
                ->get()
                ->keyBy('chapter_id');
        }

        foreach ($chapters as &$row) {
            $row['owning_exam_id']   = null;
            $row['owning_exam_name'] = null;

            $owner = $ownership[$row['id']] ?? null;
            if ($owner) {
                $row['owning_exam_id']   = $owner->exam_id;
                $row['owning_exam_name'] = $owner->exam?->exam_name;
            }
        }
        unset($row);

        $this->sylModalChapters = $chapters;

        // Pre-select chapters that already belong to the chosen exam (so the
        // edit flow opens with current selections ticked). Works in both
        // add and edit modes for convenience.
        if ($this->sylModalExamId) {
            $this->sylModalChapterIds = ExamSyllabusChapter::where('organization_id', $orgId)
                ->where('exam_id', $this->sylModalExamId)
                ->where('standard_id', $this->sylModalStandardId)
                ->where('subject_id', $value)
                ->pluck('chapter_id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        }
    }

    public function updatedSylModalExamId($value): void
    {
        // Refresh existing selections if subject already chosen
        if ($value && $this->sylModalSubjectId && $this->sylModalStandardId) {
            $this->updatedSylModalSubjectId($this->sylModalSubjectId);
        }
    }

    public function toggleAllChapters($selectAll): void
    {
        $this->sylModalChapterIds = $selectAll
            ? collect($this->sylModalChapters)->pluck('id')->map(fn($id) => (string) $id)->toArray()
            : [];
    }

    public function saveSyllabus(): void
    {
        $this->validate([
            'sylModalExamId'     => 'required|integer|exists:exams,id',
            'sylModalStandardId' => 'required|integer',
            'sylModalSectionId'  => 'nullable|integer',
            'sylModalSubjectId'  => 'required|integer',
            // Editing may legitimately end with nothing ticked — that is how a
            // syllabus is removed now that the list has no delete button.
            'sylModalChapterIds' => $this->sylModalIsEdit ? 'array' : 'required|array|min:1',
        ], [
            'sylModalExamId.required'     => 'Please select an exam.',
            'sylModalStandardId.required' => 'Please select a class.',
            'sylModalSubjectId.required'  => 'Please select a subject.',
            'sylModalChapterIds.required' => 'Please select at least one chapter.',
            'sylModalChapterIds.min'      => 'Please select at least one chapter.',
        ]);

        $orgId = Auth::user()->organization_id;
        // The syllabus as it was — its teachers hear when it changes (not when it is first set).
        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->examSyllabusSnapshot((int) $orgId, (int) $this->sylModalExamId, (int) $this->sylModalStandardId,
            (int) $this->sylModalSubjectId, array_map('intval', $this->sylModalChapterIds));

        try {
            DB::transaction(function () use ($orgId) {
                $chapterIds = array_map('intval', $this->sylModalChapterIds);

                // ── Chapter-exclusivity / transfer-on-edit ──
                // A chapter can only live in ONE syllabus row at a time. So
                // for every chapter we're about to attach to THIS exam, drop
                // any rows that mapped it to a different exam (or to this
                // exam under a different class/subject). This handles both
                // re-runs in add mode and the edit mode where the admin
                // re-claims chapters that were sitting in another exam.
                if (!empty($chapterIds)) {
                    ExamSyllabusChapter::where('organization_id', $orgId)
                        ->whereIn('chapter_id', $chapterIds)
                        ->where(function ($q) {
                            $q->where('exam_id', '!=', $this->sylModalExamId)
                              ->orWhere('standard_id', '!=', $this->sylModalStandardId)
                              ->orWhere('subject_id',  '!=', $this->sylModalSubjectId);
                        })
                        ->delete();
                }

                // Replace the current (exam, class, subject) bucket with the
                // fresh selection so unticked chapters get removed too.
                ExamSyllabusChapter::where('organization_id', $orgId)
                    ->where('exam_id', $this->sylModalExamId)
                    ->where('standard_id', $this->sylModalStandardId)
                    ->where('subject_id', $this->sylModalSubjectId)
                    ->delete();

                foreach ($chapterIds as $chapterId) {
                    ExamSyllabusChapter::create([
                        'organization_id' => $orgId,
                        'exam_id'         => (int) $this->sylModalExamId,
                        'standard_id'     => (int) $this->sylModalStandardId,
                        'subject_id'      => (int) $this->sylModalSubjectId,
                        'section_id'      => $this->sylModalSectionId ? (int) $this->sylModalSectionId : null,
                        'chapter_id'      => $chapterId,
                    ]);
                }
            });
            $push->examSyllabusSaved($before);

            $this->notification()->success(
                empty($this->sylModalChapterIds)
                    ? 'Syllabus removed — every chapter was deselected.'
                    : ($this->sylModalIsEdit ? 'Syllabus updated successfully!' : 'Syllabus saved successfully!')
            );
            $this->loadStatistics();
            $this->closeSyllabusModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error saving syllabus', $e->getMessage());
        }
    }

    // ═════════════════════════════════════════════════════════════════
    //  EXAM PAPERS TAB — upload a question paper per (exam, class, section)
    // ═════════════════════════════════════════════════════════════════

    /** Load sections for a class into the paper-modal section dropdown. */
    private function loadPaperModalSections($standardId): void
    {
        if (!$standardId) {
            $this->paperModalSections = [];
            return;
        }

        $this->paperModalSections = Section::where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $standardId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Subjects taught in a class — narrowed to the section when one is given.
     * Falls back to the class-level pivot so the dropdown is never silently
     * empty, and to every active subject when no class is chosen yet.
     */
    private function subjectsForClass($standardId, $sectionId = null): array
    {
        $orgId = Auth::user()->organization_id;

        if (!$standardId) {
            return Subject::where('organization_id', $orgId)
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name'])
                ->toArray();
        }

        $subjectIds = [];

        if ($sectionId) {
            $subjectIds = DB::table('section_subjects')
                ->where('section_id', $sectionId)
                ->where('standard_id', $standardId)
                ->pluck('subject_id')
                ->toArray();
        }

        if (empty($subjectIds)) {
            $subjectIds = DB::table('standard_subjects')
                ->where('standard_id', $standardId)
                ->pluck('subject_id')
                ->toArray();
        }

        return Subject::where('organization_id', $orgId)
            ->whereIn('id', $subjectIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name'])
            ->toArray();
    }

    private function loadPaperModalSubjects(): void
    {
        $this->paperModalSubjects = $this->paperStandard
            ? $this->subjectsForClass($this->paperStandard, $this->paperSection ?: null)
            : [];
    }

    public function updatedPaperStandard($value): void
    {
        $this->paperSection = '';
        $this->paperSubject = '';
        $this->loadPaperModalSections($value);
        $this->loadPaperModalSubjects();
    }

    // Section narrows the subject list (section_subjects → standard_subjects).
    public function updatedPaperSection($value): void
    {
        $this->paperSubject = '';
        $this->loadPaperModalSubjects();
    }

    // Filter cascade (Exam → Class → Section → Subject) resets downstream selections.
    public function updatedFilterPaperStandard($value): void
    {
        $this->filterPaperSection = '';
        $this->filterPaperSubject = '';
        $this->resetPage();
    }

    public function updatedFilterPaperExam(): void    { $this->resetPage(); }
    public function updatedFilterPaperSubject(): void { $this->resetPage(); }

    public function updatedFilterPaperSection(): void
    {
        $this->filterPaperSubject = '';
        $this->resetPage();
    }

    public function clearPaperFilters(): void
    {
        $this->reset(['filterPaperExam', 'filterPaperStandard', 'filterPaperSection', 'filterPaperSubject']);
        $this->resetPage();
    }

    public function openPaperModal(): void
    {
        $this->reset(['paperId', 'paperExam', 'paperStandard', 'paperSection', 'paperSubject', 'paperTitle',
            'paperDescription', 'paperFile', 'paperModalSections', 'paperModalSubjects']);
        $this->paperIsEdit    = false;
        $this->showPaperModal = true;
        $this->resetValidation();
    }

    /**
     * A subject's Add in the papers list: the upload form opens on the exam,
     * class and section the filter shows, with that subject (and its name as
     * the title) already chosen.
     */
    public function openPaperModalFor(string $subject): void
    {
        $this->openPaperModal();

        $this->paperExam     = (string) $this->filterPaperExam;
        $this->paperStandard = (string) $this->filterPaperStandard;
        $this->loadPaperModalSections($this->paperStandard);
        $this->paperSection  = (string) $this->filterPaperSection;
        $this->loadPaperModalSubjects();
        $this->paperSubject  = $subject;

        foreach ($this->paperModalSubjects as $s) {
            if ((string) $s['id'] === $subject) {
                $this->paperTitle = (string) $s['name'];
            }
        }
    }

    /**
     * The file picked from the subjects list. Add: a new paper for that
     * subject on the exam, class and section the filter shows, titled with the
     * subject's name. Edit: the paper keeps its details and gets the new file
     * (the old one is taken off storage). PDF only, up to 2 MB.
     */
    public function updatedQuickPaperFile(): void
    {
        $for  = $this->quickPaperFor;
        $file = $this->quickPaperFile;
        $this->reset(['quickPaperFile', 'quickPaperFor']);

        if (!$file || $for === '') {
            return;
        }

        $check = \Illuminate\Support\Facades\Validator::make(
            ['file' => $file],
            ['file' => 'required|file|mimes:pdf|max:' . self::PAPER_MAX_KB],
            ['file.mimes' => 'The paper must be a PDF file.', 'file.max' => 'The PDF must be 2 MB or smaller.']
        );
        if ($check->fails()) {
            $this->notification()->error('Not uploaded', $check->errors()->first('file'));
            return;
        }

        $orgId = Auth::user()->organization_id;

        try {
            if (str_starts_with($for, 'edit:')) {
                $paper = ExamPaper::where('id', (int) substr($for, 5))
                    ->where('organization_id', $orgId)
                    ->first();
                if (!$paper) {
                    $this->notification()->error('Not found', 'Exam paper not found.');
                    return;
                }

                $old = $paper->file_path;
                $paper->update([
                    'file_path'   => $file->store('admin/exam-papers/' . $orgId, 's3'),
                    'uploaded_by' => Auth::id(),
                ]);
                if ($old) {
                    Storage::disk('s3')->delete($old);
                }
                $this->notification()->success('Updated', 'The paper\'s file is replaced.');
                return;
            }

            if (!str_starts_with($for, 'add:') || !$this->filterPaperExam || !$this->filterPaperStandard) {
                return;
            }

            $subjectName = Subject::where('organization_id', $orgId)->where('id', (int) substr($for, 4))->value('name');
            if ($subjectName === null) {
                $this->notification()->error('Not uploaded', 'Please pick a valid subject.');
                return;
            }

            ExamPaper::create([
                'organization_id' => $orgId,
                'exam_id'         => (int) $this->filterPaperExam,
                'standard_id'     => (int) $this->filterPaperStandard,
                'section_id'      => $this->filterPaperSection ? (int) $this->filterPaperSection : null,
                'subject_id'      => (int) substr($for, 4),
                'title'           => $subjectName,
                'description'     => null,
                'file_path'       => $file->store('admin/exam-papers/' . $orgId, 's3'),
                'uploaded_by'     => Auth::id(),
            ]);
            $this->notification()->success('Uploaded', 'Exam paper uploaded successfully.');
        } catch (\Throwable $e) {
            logger()->error('ExamPaper quick upload error: ' . $e->getMessage());
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    /** View: the paper's PDF in a slide-in, Download and Close at its foot. */
    public function viewPaper(int $id): void
    {
        $paper = ExamPaper::where('id', $id)
            ->where('organization_id', Auth::user()->organization_id)
            ->first();

        if (!$paper || !$paper->file_path) {
            $this->notification()->error('Not found', 'Paper file not found.');
            return;
        }

        if (!Storage::disk('s3')->exists($paper->file_path)) {
            $this->notification()->error('File missing', 'File missing on storage. Please re-upload this paper.');
            return;
        }

        $filename = str_replace('"', '', $paper->title ?: 'exam-paper') . '.pdf';
        $this->viewPaperUrl = Storage::disk('s3')->temporaryUrl(
            $paper->file_path,
            now()->addMinutes(30),
            [
                'ResponseContentDisposition' => 'inline; filename="' . $filename . '"',
                'ResponseContentType'        => 'application/pdf',
            ]
        );
        $this->viewPaperId    = $paper->id;
        $this->viewPaperTitle = (string) ($paper->title ?: 'Exam paper');
    }

    public function closePaperView(): void
    {
        $this->reset(['viewPaperId', 'viewPaperTitle', 'viewPaperUrl']);
    }

    public function openEditPaperModal(int $id): void
    {
        $paper = ExamPaper::where('id', $id)
            ->where('organization_id', Auth::user()->organization_id)
            ->first();
        if (!$paper) {
            $this->notification()->error('Not found', 'Exam paper not found.');
            return;
        }

        $this->paperId       = $paper->id;
        $this->paperExam     = (string) $paper->exam_id;
        $this->paperStandard = (string) $paper->standard_id;
        $this->loadPaperModalSections($paper->standard_id);
        $this->paperSection  = $paper->section_id ? (string) $paper->section_id : '';
        $this->loadPaperModalSubjects();
        // A saved paper with no subject was filed under "Other".
        $this->paperSubject  = $paper->subject_id
            ? (string) $paper->subject_id
            : self::PAPER_SUBJECT_OTHER;
        $this->paperTitle       = (string) $paper->title;
        $this->paperDescription = (string) ($paper->description ?? '');
        $this->paperFile     = null;
        $this->paperIsEdit   = true;
        $this->showPaperModal = true;
        $this->resetValidation();
    }

    public function closePaperModal(): void
    {
        $this->showPaperModal = false;
        $this->reset(['paperId', 'paperExam', 'paperStandard', 'paperSection', 'paperSubject', 'paperTitle',
            'paperDescription', 'paperFile', 'paperModalSections', 'paperModalSubjects']);
        $this->resetValidation();
    }

    public function savePaper(): void
    {
        $rules = [
            'paperExam'     => 'required|exists:exams,id',
            'paperStandard' => 'required|exists:standards,id',
            'paperSection'  => 'nullable|exists:sections,id',
            // Either one of the class's subjects, or the explicit "Other" bucket.
            'paperSubject'  => $this->paperSubject === self::PAPER_SUBJECT_OTHER
                ? 'required|string'
                : 'required|exists:subjects,id',
            'paperTitle'    => 'required|string|max:255',
            'paperDescription' => 'nullable|string|max:3000',
            'paperFile'     => ($this->paperIsEdit ? 'nullable' : 'required') . '|file|mimes:pdf|max:' . self::PAPER_MAX_KB, // 2 MB
        ];

        $this->validate($rules, [
            'paperExam.required'     => 'Please select an exam.',
            'paperStandard.required' => 'Please select a class.',
            'paperSubject.required'  => 'Please select a subject (or choose Other).',
            'paperSubject.exists'    => 'Please select a valid subject.',
            'paperTitle.required'    => 'Please enter a title.',
            'paperDescription.max'   => 'Description may not be longer than 3000 characters.',
            'paperFile.required'     => 'Please choose a PDF file.',
            'paperFile.mimes'        => 'The paper must be a PDF file.',
            'paperFile.max'          => 'The PDF must be 2 MB or smaller.',
        ]);

        try {
            $orgId = Auth::user()->organization_id;

            $data = [
                'exam_id'     => $this->paperExam,
                'standard_id' => $this->paperStandard,
                'section_id'  => $this->paperSection ?: null,
                'subject_id'  => $this->paperSubject === self::PAPER_SUBJECT_OTHER
                    ? null
                    : $this->paperSubject,
                'title'       => $this->paperTitle,
                'description' => $this->paperDescription ?: null,
            ];

            if ($this->paperIsEdit) {
                $paper = ExamPaper::where('id', $this->paperId)
                    ->where('organization_id', $orgId)
                    ->firstOrFail();

                if ($this->paperFile) {
                    if ($paper->file_path) {
                        Storage::disk('s3')->delete($paper->file_path);
                    }
                    $data['file_path']   = $this->paperFile->store('admin/exam-papers/' . $orgId, 's3');
                    $data['uploaded_by'] = Auth::id();
                }

                $paper->update($data);
                $this->notification()->success('Updated', 'Exam paper updated successfully.');
            } else {
                $data['organization_id'] = $orgId;
                $data['uploaded_by']     = Auth::id();
                $data['file_path']       = $this->paperFile->store('admin/exam-papers/' . $orgId, 's3');
                ExamPaper::create($data);
                $this->notification()->success('Uploaded', 'Exam paper uploaded successfully.');
            }

            $this->closePaperModal();
        } catch (\Throwable $e) {
            logger()->error('ExamPaper save error: ' . $e->getMessage());
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    public function onDeletePaper(int $id): void
    {
        $this->paperDeleteId          = $id;
        $this->showPaperDeleteConfirm = true;
    }

    public function cancelDeletePaper(): void
    {
        $this->paperDeleteId          = null;
        $this->showPaperDeleteConfirm = false;
    }

    public function confirmDeletePaper(): void
    {
        $paper = ExamPaper::where('id', $this->paperDeleteId)
            ->where('organization_id', Auth::user()->organization_id)
            ->first();

        if ($paper) {
            if ($paper->file_path) {
                Storage::disk('s3')->delete($paper->file_path);
            }
            $paper->delete();
            $this->notification()->success('Deleted', 'Exam paper deleted.');

            if ((int) $this->viewPaperId === (int) $paper->id) {
                $this->closePaperView();
            }
        }

        $this->paperDeleteId          = null;
        $this->showPaperDeleteConfirm = false;
    }

    public function downloadPaper(int $id): mixed
    {
        $paper = ExamPaper::where('id', $id)
            ->where('organization_id', Auth::user()->organization_id)
            ->first();

        if (!$paper || !$paper->file_path) {
            $this->notification()->error('Not found', 'Paper file not found.');
            return null;
        }

        if (!Storage::disk('s3')->exists($paper->file_path)) {
            $this->notification()->error('File missing', 'File missing on storage. Please re-upload this paper.');
            return null;
        }

        $filename = ($paper->title ?: 'exam-paper') . '.pdf';
        $url = Storage::disk('s3')->temporaryUrl(
            $paper->file_path,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $filename . '"',
                'ResponseContentType'        => 'application/octet-stream',
            ]
        );
        return $this->redirect($url);
    }

    /** Papers listing for the current org, filtered by Exam → Class → Section. */
    private function getExamPapers()
    {
        $orgId = Auth::user()->organization_id;

        return ExamPaper::with([
                'exam:id,exam_name,academic_year', 'standard:id,name', 'section:id,name', 'subject:id,name',
            ])
            ->where('organization_id', $orgId)
            ->when($this->filterPaperExam,     fn($q) => $q->where('exam_id', $this->filterPaperExam))
            ->when($this->filterPaperStandard, fn($q) => $q->where('standard_id', $this->filterPaperStandard))
            ->when($this->filterPaperSection,  fn($q) => $q->where('section_id', $this->filterPaperSection))
            ->when($this->filterPaperSubject, fn($q) => $this->filterPaperSubject === self::PAPER_SUBJECT_OTHER
                ? $q->whereNull('subject_id')
                : $q->where('subject_id', $this->filterPaperSubject))
            ->orderByDesc('created_at')
            ->paginate($this->perPage, ['*'], 'papersPage');
    }

    /**
     * With an exam and a class picked, every subject of the class (or of the
     * section picked) is a row holding the papers added for it — a subject
     * with none is still listed, with its Add. A whole-class paper shows
     * under each section too; papers of a subject the class no longer lists,
     * and "Other" papers, come last so nothing added is hidden. The Subject
     * box narrows the rows to that subject. Null when exam or class is not
     * picked (the papers table shows then, as before).
     */
    private function getPaperBoard(): ?array
    {
        if (!$this->filterPaperExam || !$this->filterPaperStandard) {
            return null;
        }

        $subjects = $this->subjectsForClass($this->filterPaperStandard, $this->filterPaperSection ?: null);

        $papers = ExamPaper::with(['section:id,name', 'subject:id,name'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('exam_id', $this->filterPaperExam)
            ->where('standard_id', $this->filterPaperStandard)
            ->when($this->filterPaperSection, fn ($q) => $q->where(fn ($w) => $w
                ->where('section_id', $this->filterPaperSection)
                ->orWhereNull('section_id')))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($p) => $p->subject_id ? (string) $p->subject_id : self::PAPER_SUBJECT_OTHER);

        $rows = [];
        foreach ($subjects as $s) {
            $key    = (string) $s['id'];
            $rows[] = ['key' => $key, 'name' => $s['name'], 'papers' => $papers->get($key, collect())];
        }

        $listed = array_column($rows, 'key');
        foreach ($papers as $key => $list) {
            if ($key !== self::PAPER_SUBJECT_OTHER && !in_array((string) $key, $listed, true)) {
                $rows[] = ['key' => (string) $key, 'name' => $list->first()->subjectLabel(), 'papers' => $list];
            }
        }
        if ($papers->has(self::PAPER_SUBJECT_OTHER)) {
            $rows[] = ['key' => self::PAPER_SUBJECT_OTHER, 'name' => 'Other', 'papers' => $papers->get(self::PAPER_SUBJECT_OTHER)];
        }

        if ($this->filterPaperSubject !== '') {
            $rows = array_values(array_filter($rows, fn ($r) => $r['key'] === (string) $this->filterPaperSubject));
        }

        return $rows;
    }

    /**
     * Exam Syllabus with an exam and a class picked: every subject of the
     * class (or of the section picked) is a row with its chapters for that
     * exam, in chapter order — a subject with none is still listed, with its
     * Add. A syllabus is kept per exam, class and subject (saveSyllabus), so
     * its chapters show whichever section it was saved under. Subjects the
     * class no longer lists but that have a syllabus come last; the Subject
     * box narrows the rows. Null when exam or class is not picked.
     */
    private function getSyllabusBoard(): ?array
    {
        if (!$this->syllabusFilterExam || !$this->syllabusFilterStandard) {
            return null;
        }

        $subjects = $this->subjectsForClass($this->syllabusFilterStandard, $this->syllabusFilterSection ?: null);

        $saved = ExamSyllabusChapter::with(['chapter:id,name,order', 'subject:id,name'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('exam_id', $this->syllabusFilterExam)
            ->where('standard_id', $this->syllabusFilterStandard)
            ->get()
            ->groupBy(fn ($r) => (string) $r->subject_id);

        $row = function (string $key, string $name) use ($saved): array {
            $list = ($saved->get($key) ?? collect())->filter(fn ($r) => $r->chapter)
                ->sortBy(fn ($r) => [(int) $r->chapter->order, (int) $r->chapter->id])->values();

            return [
                'subject_id' => (int) $key,
                'name'       => $name,
                'section_id' => $list->first()?->section_id,
                'chapters'   => $list->map(fn ($r) => $r->chapter->name)->all(),
            ];
        };

        $rows = [];
        foreach ($subjects as $s) {
            $rows[] = $row((string) $s['id'], (string) $s['name']);
        }
        $listed = array_map(fn ($r) => (string) $r['subject_id'], $rows);
        foreach ($saved as $key => $list) {
            if (!in_array((string) $key, $listed, true)) {
                $rows[] = $row((string) $key, (string) ($list->first()->subject->name ?? 'Subject'));
            }
        }

        if ($this->syllabusFilterSubject !== '') {
            $rows = array_values(array_filter($rows, fn ($r) => (string) $r['subject_id'] === (string) $this->syllabusFilterSubject));
        }

        return $rows;
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        $exams    = $this->getExams();
        $syllabus = $this->getSyllabusView();
        $orgId    = Auth::user()->organization_id;

        // Cascading dropdown options for syllabus filter (Exam → Class → Section → Subject)
        $filterSections = [];
        $filterSubjects = [];

        if ($this->syllabusFilterStandard) {
            $filterSections = Section::where('organization_id', $orgId)
                ->where('standard_id', $this->syllabusFilterStandard)
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name'])
                ->toArray();
        }

        if ($this->syllabusFilterStandard && $this->syllabusFilterSection) {
            $subjectIds = DB::table('section_subjects')
                ->where('section_id', $this->syllabusFilterSection)
                ->where('standard_id', $this->syllabusFilterStandard)
                ->pluck('subject_id')->toArray();

            if (empty($subjectIds)) {
                $subjectIds = DB::table('standard_subjects')
                    ->where('standard_id', $this->syllabusFilterStandard)
                    ->pluck('subject_id')->toArray();
            }

            $filterSubjects = Subject::where('organization_id', $orgId)
                ->whereIn('id', $subjectIds)
                ->orderBy('id')
                ->get(['id', 'name'])
                ->toArray();
        } elseif ($this->syllabusFilterStandard) {
            // No section picked (a class with several): the class's subjects.
            $filterSubjects = Subject::where('organization_id', $orgId)
                ->whereIn('id', DB::table('standard_subjects')
                    ->where('standard_id', $this->syllabusFilterStandard)
                    ->pluck('subject_id')->toArray())
                ->orderBy('id')
                ->get(['id', 'name'])
                ->toArray();
        }

        // Exam Papers tab data
        $examPapers        = $this->getExamPapers();
        $paperFilterSections = [];
        if ($this->filterPaperStandard) {
            $paperFilterSections = Section::where('organization_id', $orgId)
                ->where('standard_id', $this->filterPaperStandard)
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name'])
                ->toArray();
        }

        // Subject filter options follow the chosen class (+ section); with no
        // class picked it lists every active subject in the school.
        $paperFilterSubjects = $this->subjectsForClass(
            $this->filterPaperStandard ?: null,
            $this->filterPaperSection ?: null
        );

        $paperBoard    = $this->activeTab === 'papers' ? $this->getPaperBoard() : null;
        $syllabusBoard = $this->activeTab === 'syllabus' ? $this->getSyllabusBoard() : null;

        return view('livewire.admin.add-exam', compact(
            'exams', 'syllabus', 'filterSections', 'filterSubjects',
            'examPapers', 'paperFilterSections', 'paperFilterSubjects', 'paperBoard', 'syllabusBoard'
        ));
    }

    private function getExams()
    {
        $query = Exam::with(['createdBy', 'updatedBy'])
            ->where('organization_id', Auth::user()->organization_id);

        if ($this->search) {
            $query->where('exam_name', 'like', '%' . $this->search . '%');
        }
        if ($this->filterAcademicYear) {
            $query->where('academic_year', $this->filterAcademicYear);
        }
        if ($this->filterExamType) {
            $query->where('exam_type', $this->filterExamType);
        }
        if ($this->filterTerm) {
            $query->where('term', $this->filterTerm);
        }
        if ($this->filterStatus) {
            $query->withStatus($this->filterStatus);
        }

        // Default sort: by exam start date, earliest first. Exams without a
        // start date sink to the bottom; ties break by oldest-added first so
        // "the exam added first appears at the top" as requested.
        return $query
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);
    }

    /**
     * When the full chain (Exam → Class → Section → Subject) is selected,
     * return the chapters (with topics) selected as syllabus for that
     * combination. Otherwise return a grouped overview of (exam, class,
     * section, subject) syllabus groups filtered by whatever the admin
     * narrowed down.
     */
    private function getSyllabusView(): array
    {
        $orgId = Auth::user()->organization_id;

        // Chapter detail — needs Exam, Class and Subject. Section is shown in
        // the chain but optional because legacy syllabus rows may have a null
        // section_id (chapters keyed only on exam+class+subject). The chapter
        // query itself doesn't filter by section so dropping that requirement
        // lets the View button on those rows still drill into the detail.
        if (
            $this->syllabusFilterExam
            && $this->syllabusFilterStandard
            && $this->syllabusFilterSubject
        ) {
            $chapterIds = ExamSyllabusChapter::where('organization_id', $orgId)
                ->where('exam_id', $this->syllabusFilterExam)
                ->where('standard_id', $this->syllabusFilterStandard)
                ->where('subject_id', $this->syllabusFilterSubject)
                ->pluck('chapter_id')->toArray();

            $chapters = Chapter::with('topics:id,chapter_id,topic_name')
                ->whereIn('id', $chapterIds)
                ->orderBy('order')
                ->get(['id', 'name', 'description', 'order'])
                ->toArray();

            // Pull additional context (names + the section row, if any) so the
            // detail header can show what's being viewed.
            $exam     = Exam::find((int) $this->syllabusFilterExam, ['id', 'exam_name', 'academic_year']);
            $standard = Standard::find((int) $this->syllabusFilterStandard, ['id', 'name']);
            $subject  = Subject::find((int) $this->syllabusFilterSubject,  ['id', 'name']);
            $section  = $this->syllabusFilterSection
                ? Section::find((int) $this->syllabusFilterSection, ['id', 'name'])
                : null;

            return [
                'mode'        => 'detail',
                'chapters'    => $chapters,
                'exam_id'     => (int) $this->syllabusFilterExam,
                'exam_name'   => $exam?->exam_name,
                'standard_id' => (int) $this->syllabusFilterStandard,
                'standard_name' => $standard?->name,
                'section_id'  => $this->syllabusFilterSection ? (int) $this->syllabusFilterSection : null,
                'section_name' => $section?->name,
                'subject_id'  => (int) $this->syllabusFilterSubject,
                'subject_name' => $subject?->name,
            ];
        }

        // Otherwise grouped overview — keyed by exam+class+section+subject so
        // the same trio in different sections shows up as distinct rows.
        $rows = ExamSyllabusChapter::with(['exam:id,exam_name,academic_year', 'standard:id,name', 'subject:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->when($this->syllabusFilterExam,     fn($q) => $q->where('exam_id',     $this->syllabusFilterExam))
            ->when($this->syllabusFilterStandard, fn($q) => $q->where('standard_id', $this->syllabusFilterStandard))
            ->when($this->syllabusFilterSection,  fn($q) => $q->where('section_id',  $this->syllabusFilterSection))
            ->when($this->syllabusFilterSubject,  fn($q) => $q->where('subject_id',  $this->syllabusFilterSubject))
            ->get()
            ->groupBy(fn($r) => $r->exam_id . '-' . $r->standard_id . '-' . ($r->section_id ?? 0) . '-' . $r->subject_id)
            ->map(fn($group) => [
                'exam_id'      => $group->first()->exam_id,
                'exam_name'    => $group->first()->exam->exam_name ?? 'N/A',
                'standard_id'  => $group->first()->standard_id,
                'standard_name' => $group->first()->standard->name ?? 'N/A',
                'section_id'   => $group->first()->section_id,
                'section_name' => $group->first()->section->name ?? null,
                'subject_id'   => $group->first()->subject_id,
                'subject_name' => $group->first()->subject->name ?? 'N/A',
                'chapter_count' => $group->count(),
            ])
            ->values()
            ->toArray();

        return [
            'mode'   => 'list',
            'groups' => $rows,
        ];
    }
}
