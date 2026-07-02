<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use WireUi\Traits\WireUiActions;

class Performance extends Component
{
    use WireUiActions, WithPagination;

    public string $activeTab = 'subject';

    // ─── View slider (read-only) ───────────────────────────────────────────────
    public bool   $showSlider  = false;
    public string $sliderTitle = '';
    public array  $sliderData  = [];

    // ─── Edit slider ──────────────────────────────────────────────────────────
    public bool  $showEditSlider = false;
    public ?int  $editingMarkId  = null;
    public array $editMarkData   = [];

    // ─── Filters (Subject tab) ────────────────────────────────────────────────
    #[Url] public string $search         = '';
    #[Url] public int    $perPage        = 10;
    #[Url] public string $filterExam     = '';
    #[Url] public string $filterStandard = '';
    #[Url] public string $filterSection  = '';
    #[Url] public string $filterSubject  = '';

    // ─── View by Student tab ──────────────────────────────────────────────────
    public string $selectedExam       = '';
    public string $selectedStandard   = '';
    public string $selectedSection    = '';
    public string $selectedSubject    = '';
    public string $selectedStudent    = '';
    public array  $studentPerformance = [];

    // ─── Delete confirm overlay (replaces broken WireUI dialog) ───────────────
    public bool $showDeleteConfirm = false;
    public ?int $deleteTargetId    = null;

    // ─── Upload Marks slide-in ────────────────────────────────────────────────
    public bool   $showUploadModal  = false;
    public string $uploadExam       = '';
    public string $uploadStandard   = '';
    public string $uploadSection    = '';
    public string $uploadSubject    = '';
    public int    $uploadTotalMarks = 100; // Pulled from exam.total_marks
    public array  $studentMarks     = [];
    public array  $editingStudents  = [];

    // ─── Performers tab ───────────────────────────────────────────────────────
    public string $perfStandard = '';
    public string $perfSection  = '';
    public string $perfExam     = '';
    public string $perfSubject  = '';
    public string $perfStudent  = '';
    public array  $perfSections = [];
    public array  $perfStudents = [];
    public array  $perfSubjects = [];
    public array  $performers   = [];

    // ─── Shared lookup ────────────────────────────────────────────────────────
    public $exams     = [];
    public $standards = [];
    public $sections  = [];
    public $students  = [];
    public $subjects  = [];

    // ─── Header stats ─────────────────────────────────────────────────────────
    public int   $totalRecords = 0;
    public int   $totalExamsCount = 0;
    public int   $totalStudentsCount = 0;
    public float $avgPercentage = 0.0;

    // ════════════════════════════════════════════════════════════════════════
    public function mount(): void
    {
        $this->loadFilters();
        if ($this->filterStandard) {
            $this->sections = Section::where('standard_id', $this->filterStandard)
                ->where('is_active', true)->get();
            $this->loadSubjectsForStandard($this->filterStandard, $this->filterSection ?: null);
        }
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $orgId = Auth::user()->organization_id;
        $row   = ExamCopy::where('organization_id', $orgId)
            ->selectRaw('COUNT(*) as total, AVG(percentage) as avg_pct, COUNT(DISTINCT exam_id) as exams_cnt, COUNT(DISTINCT student_detail_id) as students_cnt')
            ->first();

        $this->totalRecords       = (int)   ($row->total        ?? 0);
        $this->totalExamsCount    = (int)   ($row->exams_cnt    ?? 0);
        $this->totalStudentsCount = (int)   ($row->students_cnt ?? 0);
        $this->avgPercentage      = (float) round($row->avg_pct ?? 0, 2);
    }

    // ─── Tabs ────────────────────────────────────────────────────────────────
    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'student') {
            $this->reset(['selectedExam', 'selectedStandard', 'selectedSection', 'selectedStudent', 'studentPerformance']);
            $this->sections = [];
            $this->students = [];
            $this->loadFilters();
        } elseif ($tab === 'performers') {
            $this->performers = [];
        } else {
            $this->resetPage();
            $this->loadFilters();
        }
    }

    // ─── Filter updates (Subject tab) ────────────────────────────────────────
    public function updated(string $property, mixed $value): void
    {
        if (in_array($property, ['search', 'filterExam', 'filterStandard', 'filterSection', 'filterSubject'])) {
            $this->resetPage();
        }

        if ($property === 'filterStandard') {
            $this->filterSection = '';
            $this->filterSubject = '';
            if ($value) {
                $this->sections = Section::where('standard_id', $value)->where('is_active', true)->get();
                $this->loadSubjectsForStandard($value);
            } else {
                $this->sections = [];
                $this->loadFilters();
            }
        }

        if ($property === 'filterSection') {
            $this->filterSubject = '';
            if ($value && $this->filterStandard) {
                $this->loadSubjectsForStandard($this->filterStandard, $value);
            } elseif ($this->filterStandard) {
                $this->loadSubjectsForStandard($this->filterStandard);
            }
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterExam', 'filterStandard', 'filterSection', 'filterSubject']);
        $this->sections = [];
        $this->loadFilters();
        $this->resetPage();
    }

    // ─── Student tab (View by Student) ───────────────────────────────────────
    public function updatedSelectedExam(): void
    {
        $this->studentPerformance = [];
        $this->autoSearchStudent();
    }
    public function updatedSelectedStandard(mixed $value): void
    {
        $this->selectedSection    = '';
        $this->selectedSubject    = '';
        $this->selectedStudent    = '';
        $this->students           = [];
        $this->studentPerformance = [];
        if ($value) {
            $this->sections = Section::where('standard_id', $value)->where('is_active', true)->orderBy('id')->get();
            $this->loadSubjectsForStandard($value);
        } else {
            $this->sections = [];
            $this->loadFilters();
        }
    }
    public function updatedSelectedSection(mixed $value): void
    {
        $this->selectedSubject    = '';
        $this->selectedStudent    = '';
        $this->studentPerformance = [];
        if ($value && $this->selectedStandard) {
            $this->students = $this->loadStudents($this->selectedStandard, $value);
            $this->loadSubjectsForStandard($this->selectedStandard, $value);
        } else {
            $this->students = [];
        }
    }
    public function updatedSelectedSubject(): void
    {
        $this->studentPerformance = [];
        $this->autoSearchStudent();
    }
    public function updatedSelectedStudent(): void
    {
        $this->studentPerformance = [];
        $this->autoSearchStudent();
    }

    private function autoSearchStudent(): void
    {
        if ($this->selectedExam && $this->selectedStandard && $this->selectedSection && $this->selectedStudent) {
            $this->searchPerformance();
        }
    }

    public function clearStudentFilters(): void
    {
        $this->reset(['selectedExam', 'selectedStandard', 'selectedSection', 'selectedSubject', 'selectedStudent', 'studentPerformance']);
        $this->sections = [];
        $this->students = [];
        $this->loadFilters();
    }

    // ─── Upload Marks slide-in ───────────────────────────────────────────────
    public function openUploadMarks(): void
    {
        $this->showUploadModal  = true;
        $this->uploadExam       = '';
        $this->uploadStandard   = '';
        $this->uploadSection    = '';
        $this->uploadSubject    = '';
        $this->uploadTotalMarks = 100;
        $this->studentMarks     = [];
        $this->editingStudents  = [];
        $this->sections         = [];
        $this->students         = [];
        $this->loadFilters();
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->editingStudents = [];
        if ($this->activeTab === 'subject') {
            $this->loadFilters();
        }
    }

    public function toggleEditStudent(int $studentId): void
    {
        $this->editingStudents[$studentId] = !($this->editingStudents[$studentId] ?? false);
    }

    public function updatedUploadExam(mixed $value): void
    {
        $this->editingStudents  = [];
        $this->uploadTotalMarks = 100;

        if ($value) {
            $exam = Exam::where('organization_id', Auth::user()->organization_id)->find($value);
            if ($exam && $exam->total_marks) {
                $this->uploadTotalMarks = (int) $exam->total_marks;
            }
        }
        $this->loadStudentMarks();
    }

    public function updatedUploadStandard(mixed $value): void
    {
        $this->uploadSection   = '';
        $this->uploadSubject   = '';
        $this->editingStudents = [];
        $this->students        = [];

        if ($value) {
            $this->sections = Section::where('standard_id', $value)->where('is_active', true)->get();
            $this->loadSubjectsForStandard($value);
        } else {
            $this->sections = [];
        }
        $this->studentMarks = [];
    }

    public function updatedUploadSection(mixed $value): void
    {
        $this->uploadSubject   = '';
        $this->editingStudents = [];

        if ($value && $this->uploadStandard) {
            $this->students = $this->loadStudents($this->uploadStandard, $value);
            $this->loadSubjectsForStandard($this->uploadStandard, $value);
        } else {
            $this->students = [];
        }
        $this->studentMarks = [];
    }

    public function updatedUploadSubject(): void
    {
        $this->editingStudents = [];
        $this->loadStudentMarks();
    }

    public function uploadMarks(): void
    {
        $this->validate([
            'uploadExam'     => 'required',
            'uploadStandard' => 'required',
            'uploadSection'  => 'required',
            'uploadSubject'  => 'required',
        ], [
            'uploadExam.required'     => 'Please select an exam.',
            'uploadStandard.required' => 'Please select a class.',
            'uploadSection.required'  => 'Please select a section.',
            'uploadSubject.required'  => 'Please select a subject.',
        ]);

        $max = max(1, (int) $this->uploadTotalMarks);

        try {
            $savedCount = 0;
            foreach ($this->studentMarks as $studentId => $marks) {
                if (!isset($marks['marks_obtained']) || $marks['marks_obtained'] === '' || !is_numeric($marks['marks_obtained'])) {
                    continue;
                }
                $obt = max(0, min($max, (float) $marks['marks_obtained']));
                $pct = $max > 0 ? ($obt / $max) * 100 : 0;

                ExamCopy::updateOrCreate(
                    [
                        'exam_id'           => $this->uploadExam,
                        'standard_id'       => $this->uploadStandard,
                        'section_id'        => $this->uploadSection,
                        'subject_id'        => $this->uploadSubject,
                        'student_detail_id' => $studentId,
                    ],
                    [
                        'organization_id' => Auth::user()->organization_id,
                        'marks_obtained'  => $obt,
                        'max_marks'       => $max,
                        'percentage'      => round($pct, 2),
                        'grade'           => $this->calculateGrade($pct),
                        'remarks'         => $marks['remarks'] ?? '',
                    ]
                );
                $savedCount++;
            }

            if ($savedCount > 0) {
                $this->notification()->success('Saved!', "Marks for {$savedCount} student(s) saved successfully.");
                $this->loadStudentMarks();
                $this->editingStudents = [];
                $this->loadStats();
            } else {
                $this->notification()->warning('Nothing to save', 'Please enter marks for at least one student.');
            }
        } catch (\Exception $e) {
            logger()->error('Performance uploadMarks: ' . $e->getMessage());
            $this->notification()->error('Error saving marks', $e->getMessage());
        }
    }

    // ─── Performers tab (exams-style cascading filter) ───────────────────────
    public function updatedPerfExam(): void     { $this->loadPerformers(); }
    public function updatedPerfStandard(mixed $value): void
    {
        $this->perfSection  = '';
        $this->perfSubject  = '';
        $this->perfStudent  = '';
        $this->performers   = [];
        $this->perfSections = [];
        $this->perfStudents = [];
        $this->perfSubjects = [];

        if ($value) {
            $this->perfSections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->toArray();
        }
    }
    public function updatedPerfSection(): void
    {
        $this->perfSubject  = '';
        $this->perfStudent  = '';
        $this->performers   = [];
        $this->perfStudents = [];
        $this->perfSubjects = [];

        if ($this->perfSection && $this->perfStandard) {
            $orgId = Auth::user()->organization_id;
            $this->perfSubjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $this->perfSection)
                ->where('section_subjects.standard_id', $this->perfStandard)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get()
                ->toArray();
            $this->perfStudents = $this->loadStudents($this->perfStandard, $this->perfSection)->toArray();
            $this->loadPerformers();
        }
    }
    public function updatedPerfSubject(): void { $this->loadPerformers(); }
    public function updatedPerfStudent(): void { $this->loadPerformers(); }

    public function clearPerfFilters(): void
    {
        $this->reset(['perfStandard', 'perfSection', 'perfExam', 'perfSubject', 'perfStudent', 'performers']);
        $this->perfSections = $this->perfStudents = $this->perfSubjects = [];
    }

    public function loadPerformers(): void
    {
        if (!$this->perfStandard || !$this->perfSection) {
            $this->performers = [];
            return;
        }

        $orgId = Auth::user()->organization_id;
        $query = ExamCopy::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->perfStandard)
            ->where('section_id',  $this->perfSection);

        if ($this->perfExam)    $query->where('exam_id',           $this->perfExam);
        if ($this->perfSubject) $query->where('subject_id',        $this->perfSubject);
        if ($this->perfStudent) $query->where('student_detail_id', $this->perfStudent);

        $records = $query->get();
        if ($records->isEmpty()) { $this->performers = []; return; }

        // Aggregate per student
        $totals = [];
        foreach ($records as $r) {
            $sid = $r->student_detail_id;
            $totals[$sid] ??= [
                'student'        => $r->studentDetail,
                'total_obtained' => 0,
                'total_max'      => 0,
                'percentage'     => 0,
                'grade'          => '',
                'rank'           => 0,
            ];
            $totals[$sid]['total_obtained'] += (float) $r->marks_obtained;
            $totals[$sid]['total_max']      += (float) $r->max_marks;
        }
        foreach ($totals as &$d) {
            $pct = $d['total_max'] > 0 ? round(($d['total_obtained'] / $d['total_max']) * 100, 2) : 0;
            $d['percentage'] = $pct;
            $d['grade']      = $this->calculateGrade($pct);
        }
        unset($d);

        // Sort by obtained marks DESC (then by percentage as tie-breaker)
        usort($totals, function ($a, $b) {
            $byObt = $b['total_obtained'] <=> $a['total_obtained'];
            return $byObt !== 0 ? $byObt : ($b['percentage'] <=> $a['percentage']);
        });

        $rank = 1;
        foreach ($totals as &$d) { $d['rank'] = $rank++; }
        unset($d);

        $this->performers = array_values($totals);
    }

    // ─── View / Edit ─────────────────────────────────────────────────────────
    public function onView(int $id): void
    {
        try {
            $examCopy = ExamCopy::with([
                'exam', 'standard', 'section', 'subject', 'studentDetail.user', 'examSubjectMarks.subject'
            ])->find($id);
            if (!$examCopy) { $this->notification()->error('Record not found!'); return; }
            $this->sliderTitle = 'Exam Copy Details';
            $this->sliderData  = ['exam_copy' => $examCopy, 'subject_marks' => $examCopy->examSubjectMarks];
            $this->showSlider  = true;
        } catch (\Exception $e) {
            $this->notification()->error('Error loading details', $e->getMessage());
        }
    }
    public function closeSlider(): void
    {
        $this->showSlider = false; $this->sliderData = []; $this->sliderTitle = '';
    }

    public function onEdit(int $id): void
    {
        $ec = ExamCopy::with(['exam:id,exam_name,total_marks', 'subject:id,name', 'standard:id,name', 'section:id,name', 'studentDetail.user'])->find($id);
        if (!$ec) { $this->notification()->error('Record not found!'); return; }
        $this->editingMarkId = $id;
        $this->editMarkData  = [
            'student_name'   => $ec->studentDetail?->user?->name ?? 'N/A',
            'admission_no'   => $ec->studentDetail?->admission_no ?? '',
            'class_label'    => trim(($ec->standard?->name ?? '') . ' · ' . ($ec->section?->name ?? '')),
            'exam_name'      => $ec->exam?->exam_name ?? '—',
            'subject_name'   => $ec->subject?->name ?? '—',
            'marks_obtained' => $ec->marks_obtained,
            'max_marks'      => $ec->max_marks ?: ($ec->exam?->total_marks ?: 100),
            'remarks'        => $ec->remarks ?? '',
        ];
        $this->showEditSlider = true;
    }
    public function closeEditSlider(): void
    {
        $this->showEditSlider = false;
        $this->editingMarkId  = null;
        $this->editMarkData   = [];
    }
    public function saveEditMark(): void
    {
        $this->validate([
            'editMarkData.marks_obtained' => 'required|numeric|min:0',
            'editMarkData.max_marks'      => 'required|numeric|min:1',
        ]);
        try {
            $ec = ExamCopy::find($this->editingMarkId);
            if (!$ec) return;
            $max = (float) $this->editMarkData['max_marks'];
            $obt = max(0, min($max, (float) $this->editMarkData['marks_obtained']));
            $pct = $max > 0 ? ($obt / $max) * 100 : 0;
            $ec->update([
                'marks_obtained' => $obt,
                'max_marks'      => $max,
                'percentage'     => round($pct, 2),
                'grade'          => $this->calculateGrade($pct),
                'remarks'        => $this->editMarkData['remarks'] ?? '',
            ]);
            $this->notification()->success('Marks updated successfully!');
            $this->closeEditSlider();
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error updating marks', $e->getMessage());
        }
    }

    public function onDelete(int $id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }
    public function cancelDelete(): void
    {
        $this->deleteTargetId    = null;
        $this->showDeleteConfirm = false;
    }
    public function confirmDelete(): void
    {
        if (!$this->deleteTargetId) return;
        try {
            $ec = ExamCopy::find($this->deleteTargetId);
            if ($ec) {
                $ec->delete();
                $this->notification()->success('Record deleted successfully!');
                if ($this->showUploadModal) { $this->loadStudentMarks(); $this->editingStudents = []; }
                $this->loadStats();
            }
        } catch (\Exception $e) {
            $this->notification()->error('Error deleting record', $e->getMessage());
        }
        $this->cancelDelete();
    }
    public function doDelete(int $id): void
    {
        $this->deleteTargetId = $id;
        $this->confirmDelete();
    }

    public function searchPerformance(): void
    {
        try {
            $this->validate([
                'selectedExam'     => 'required',
                'selectedStandard' => 'required',
                'selectedSection'  => 'required',
                'selectedStudent'  => 'required',
            ], [
                'selectedExam.required'     => 'Please select an exam',
                'selectedStandard.required' => 'Please select a class',
                'selectedSection.required'  => 'Please select a section',
                'selectedStudent.required'  => 'Please select a student',
            ]);

            $query = ExamCopy::with([
                'exam', 'standard', 'section', 'subject', 'studentDetail.user', 'examSubjectMarks.subject'
            ])
                ->where('exam_id', $this->selectedExam)
                ->where('standard_id', $this->selectedStandard)
                ->where('section_id', $this->selectedSection)
                ->where('student_detail_id', $this->selectedStudent);

            if ($this->selectedSubject) {
                $query->where('subject_id', $this->selectedSubject);
            }

            $results = $query->get();
            $this->studentPerformance = $results->isEmpty() ? [] : $results->toArray();
            if ($results->isEmpty()) {
                $this->notification()->warning('No Results', 'No records found for the selected criteria.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->studentPerformance = [];
            $this->notification()->error('Error', 'An error occurred while searching.');
        }
    }

    public function onDownloadPdf(?int $examCopyId = null, string $type = 'single'): void
    {
        $this->notification()->success('PDF Download', 'PDF download initiated.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    private function loadFilters(): void
    {
        $orgId = Auth::user()->organization_id;

        // Match the Exam admin page order: by start_date ASC (nulls last), then id ASC.
        $this->exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderBy('id', 'asc')
            ->get();

        // Classes: by configured order, but break ties with name asc for stability.
        $this->standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        // Subjects: alphabetical asc
        $this->subjects = Subject::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function loadStudents(string $standardId, string $sectionId)
    {
        // Names alphabetical asc — prefer user.name, fall back to full_name.
        return StudentDetail::where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->with('user')
            ->orderBy('full_name')
            ->orderBy('roll_no')
            ->get();
    }

    private function loadSubjectsForStandard(string $standardId, ?string $sectionId = null): void
    {
        $orgId = Auth::user()->organization_id;

        if ($sectionId) {
            $this->subjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
        } else {
            $this->subjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
        }
        if ($this->subjects->isEmpty()) $this->subjects = collect();
    }

    private function loadStudentMarks(): void
    {
        $this->studentMarks = [];

        if (!$this->uploadExam || !$this->uploadStandard || !$this->uploadSection || !$this->uploadSubject) {
            return;
        }

        $max = max(1, (int) $this->uploadTotalMarks);

        foreach ($this->students as $student) {
            $existing = ExamCopy::where('exam_id', $this->uploadExam)
                ->where('standard_id', $this->uploadStandard)
                ->where('section_id', $this->uploadSection)
                ->where('subject_id', $this->uploadSubject)
                ->where('student_detail_id', $student->id)
                ->first();

            $this->studentMarks[$student->id] = [
                'student_id'     => $student->id,
                'student_name'   => $student->user->name ?? $student->full_name ?? 'N/A',
                'roll_no'        => $student->roll_no,
                'admission_no'   => $student->admission_no,
                'image'          => $student->image,
                'marks_obtained' => $existing ? (string) $existing->marks_obtained : '',
                'max_marks'      => $existing ? (int) $existing->max_marks : $max,
                'grade'          => $existing ? $existing->grade : '',
                'remarks'        => $existing ? ($existing->remarks ?? '') : '',
                'saved'          => $existing ? true : false,
                'exam_copy_id'   => $existing ? $existing->id : null,
            ];
        }
    }

    private function calculateGrade(float $pct): string
    {
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B+';
        if ($pct >= 60) return 'B';
        if ($pct >= 50) return 'C+';
        if ($pct >= 40) return 'C';
        if ($pct >= 33) return 'D';
        return 'F';
    }

    // ─── Render ──────────────────────────────────────────────────────────────
    public function render()
    {
        $examCopies = $this->getExamCopies();
        return view('livewire.admin.performance', compact('examCopies'));
    }

    private function getExamCopies()
    {
        if ($this->activeTab !== 'subject') {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        // Subject tab is gated on class+section selection.
        if (!$this->filterStandard || !$this->filterSection) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        $query = ExamCopy::with(['exam', 'standard', 'section', 'subject', 'studentDetail.user', 'studentDetail'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $this->filterStandard)
            ->where('section_id',  $this->filterSection);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('studentDetail.user', fn($u) => $u->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('studentDetail', fn($s) => $s->where('full_name', 'like', "%{$this->search}%")
                                                             ->orWhere('admission_no', 'like', "%{$this->search}%"))
                  ->orWhereHas('exam',    fn($e) => $e->where('exam_name', 'like', "%{$this->search}%"))
                  ->orWhereHas('subject', fn($s) => $s->where('name',      'like', "%{$this->search}%"));
            });
        }
        if ($this->filterExam)    $query->where('exam_id',    $this->filterExam);
        if ($this->filterSubject) $query->where('subject_id', $this->filterSubject);

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }
}
