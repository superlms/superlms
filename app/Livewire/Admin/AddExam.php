<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class AddExam extends Component
{
    use WireUiActions, WithPagination;

    // ─── Tabs ────────────────────────────────────────────────────────────────
    public string $activeTab = 'exams'; // 'exams' | 'syllabus'

    // ─── Exam form ──────────────────────────────────────────────────────────
    public $examName       = '';
    public $term           = ''; // 'Term-1' | 'Term-2'
    public $academicYear   = '';
    public $startDate      = '';
    public $endDate        = '';
    public $description    = '';
    public $isPublished    = false;
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
    public string $deleteTargetType = 'exam'; // 'exam' | 'syllabus'

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
    public $upcomingExams    = 0;
    public $activeExams      = 0;
    public $totalSyllabusRows = 0;

    protected $queryString = [
        'activeTab'                => ['except' => 'exams'],
        'search'                   => ['except' => ''],
        'filterAcademicYear'       => ['except' => ''],
        'filterExamType'           => ['except' => ''],
        'filterTerm'               => ['except' => ''],
        'filterStatus'             => ['except' => ''],
        'syllabusFilterExam'       => ['except' => ''],
        'syllabusFilterStandard'   => ['except' => ''],
        'syllabusFilterSection'    => ['except' => ''],
        'syllabusFilterSubject'    => ['except' => ''],
        'perPage'                  => ['except' => 10],
    ];

    public function mount(): void
    {
        // Homework tab was removed — quietly snap stale bookmarks back to Exams.
        if (!in_array($this->activeTab, ['exams', 'syllabus'], true)) {
            $this->activeTab = 'exams';
        }

        $this->loadAcademicYearOptions();
        $this->loadLookups();
        $this->loadStatistics();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['exams', 'syllabus'], true) ? $tab : 'exams';
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
            ->orderBy('order')
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
        $this->publishedExams = Exam::where('organization_id', $orgId)->where('is_published', true)->count();
        $this->upcomingExams  = Exam::where('organization_id', $orgId)->where('start_date', '>', now())->count();
        $this->activeExams    = Exam::where('organization_id', $orgId)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();
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
            'startDate'     => 'required|date',
            'endDate'       => 'required|date|after_or_equal:startDate',
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
                'start_date'           => $this->startDate,
                'end_date'             => $this->endDate,
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
                Exam::findOrFail($this->editId)->update($examData);
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
                'Start Date'    => $exam->start_date->format('d M Y'),
                'End Date'      => $exam->end_date->format('d M Y'),
                'Exam Type'     => $this->examTypes[$exam->exam_type] ?? $exam->exam_type,
                'Total Marks'   => ($exam->uses_grading_system ?? false) ? 'N/A (Grading)' : $exam->total_marks,
                'Passing Marks' => ($exam->uses_grading_system ?? false) ? 'N/A (Grading)' : $exam->passing_marks,
                'Status'        => $exam->is_published ? 'Published' : 'Draft',
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
        $this->deleteTargetType = 'exam';
        $this->showDeleteConfirm = true;
    }

    public function onDeleteSyllabusGroup($examId, $standardId, $subjectId): void
    {
        $this->deleteTargetId    = ['exam_id' => $examId, 'standard_id' => $standardId, 'subject_id' => $subjectId];
        $this->deleteTargetType  = 'syllabus';
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
            if ($this->deleteTargetType === 'exam') {
                $exam = Exam::find($this->deleteTargetId);
                if ($exam) {
                    // Cascade delete syllabus rows for this exam
                    ExamSyllabusChapter::where('exam_id', $exam->id)->delete();
                    $exam->delete();
                    $this->notification()->success('Exam deleted successfully!');
                }
            } elseif ($this->deleteTargetType === 'syllabus' && is_array($this->deleteTargetId)) {
                $t = $this->deleteTargetId;
                ExamSyllabusChapter::where('exam_id', $t['exam_id'])
                    ->where('standard_id', $t['standard_id'])
                    ->where('subject_id', $t['subject_id'])
                    ->where('organization_id', Auth::user()->organization_id)
                    ->delete();
                $this->notification()->success('Syllabus removed successfully!');
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

        // Re-run the cascading loaders so dependent option lists are populated.
        $this->updatedSylModalStandardId($this->sylModalStandardId);
        $this->updatedSylModalSectionId($this->sylModalSectionId);
        $this->updatedSylModalSubjectId($this->sylModalSubjectId);

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

    public function updatedSylModalStandardId($value): void
    {
        // Reset everything downstream when class changes.
        $this->sylModalSectionId   = '';
        $this->sylModalSubjectId   = '';
        $this->sylModalChapterIds  = [];
        $this->sylModalSubjects    = [];
        $this->sylModalChapters    = [];

        if (!$value) {
            $this->sylModalSections = [];
            return;
        }

        $orgId = Auth::user()->organization_id;
        $this->sylModalSections = Section::where('organization_id', $orgId)
            ->where('standard_id', $value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function updatedSylModalSectionId($value): void
    {
        // Reset subject + chapters when section changes.
        $this->sylModalSubjectId  = '';
        $this->sylModalChapterIds = [];
        $this->sylModalChapters   = [];

        if (!$value || !$this->sylModalStandardId) {
            $this->sylModalSubjects = [];
            return;
        }

        $orgId = Auth::user()->organization_id;

        // Subjects mapped to THIS (class + section) via the section_subjects
        // pivot. Fall back to the standard_subjects pivot if the section has
        // no specific mapping so the dropdown is never silently empty.
        $sectionSubjectIds = DB::table('section_subjects')
            ->where('section_id', $value)
            ->where('standard_id', $this->sylModalStandardId)
            ->pluck('subject_id')
            ->toArray();

        if (empty($sectionSubjectIds)) {
            $sectionSubjectIds = DB::table('standard_subjects')
                ->where('standard_id', $this->sylModalStandardId)
                ->pluck('subject_id')
                ->toArray();
        }

        $this->sylModalSubjects = Subject::where('organization_id', $orgId)
            ->whereIn('id', $sectionSubjectIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function updatedSylModalSubjectId($value): void
    {
        $this->sylModalChapterIds = [];

        if (!$value || !$this->sylModalStandardId) {
            $this->sylModalChapters = [];
            return;
        }

        $orgId = Auth::user()->organization_id;

        // Chapters for this class + subject (section-scoped if section provided
        // AND chapters carry a section_id; otherwise show class-wide chapters).
        $chapterQuery = Chapter::with('topics:id,chapter_id,topic_name')
            ->where('organization_id', $orgId)
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
            ->get(['id', 'name', 'description', 'order'])
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
            'sylModalChapterIds' => 'required|array|min:1',
        ], [
            'sylModalExamId.required'     => 'Please select an exam.',
            'sylModalStandardId.required' => 'Please select a class.',
            'sylModalSubjectId.required'  => 'Please select a subject.',
            'sylModalChapterIds.required' => 'Please select at least one chapter.',
            'sylModalChapterIds.min'      => 'Please select at least one chapter.',
        ]);

        $orgId = Auth::user()->organization_id;

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

            $this->notification()->success(
                $this->sylModalIsEdit ? 'Syllabus updated successfully!' : 'Syllabus saved successfully!'
            );
            $this->loadStatistics();
            $this->closeSyllabusModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error saving syllabus', $e->getMessage());
        }
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
                ->orderBy('name')
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
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }

        return view('livewire.admin.add-exam', compact('exams', 'syllabus', 'filterSections', 'filterSubjects'));
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
            match ($this->filterStatus) {
                'published' => $query->where('is_published', true),
                'draft'     => $query->where('is_published', false),
                'active'    => $query->where('start_date', '<=', now())->where('end_date', '>=', now()),
                'upcoming'  => $query->where('start_date', '>', now()),
                'completed' => $query->where('end_date', '<', now()),
                default     => null,
            };
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
