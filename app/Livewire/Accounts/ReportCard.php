<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ReportCard as ReportCardModel;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ReportCard extends Component
{
    use WithPagination, WireUiActions;

    // View mode: 'list' or 'issue'
    public $viewMode = 'list';

    // Filters for list view
    public $search = '';
    public $filterStandard = '';
    public $filterSection = '';
    public $filterStatus = '';
    public $perPage = 10;

    // Issue report card screen
    public $issueStandard = '';
    public $issueSection = '';
    public $selectedStudents = [];
    public $issueStudentsLoaded = false;

    public function mount()
    {
        //
    }

    /**
     * Navigate to the issue report card screen.
     */
    public function openIssueScreen()
    {
        $this->viewMode = 'issue';
        $this->issueStandard = '';
        $this->issueSection = '';
        $this->selectedStudents = [];
        $this->issueStudentsLoaded = false;
    }

    /**
     * Go back to list view.
     */
    public function backToList()
    {
        $this->viewMode = 'list';
        $this->issueStandard = '';
        $this->issueSection = '';
        $this->selectedStudents = [];
        $this->issueStudentsLoaded = false;
    }

    /**
     * When issue standard changes, reset section and students.
     */
    public function updatedIssueStandard()
    {
        $this->issueSection = '';
        $this->selectedStudents = [];
        $this->issueStudentsLoaded = false;
    }

    /**
     * When issue section changes, reset students.
     */
    public function updatedIssueSection()
    {
        $this->selectedStudents = [];
        $this->issueStudentsLoaded = false;
    }

    /**
     * Load students for the selected class/section.
     */
    public function loadStudents()
    {
        if (!$this->issueStandard || !$this->issueSection) {
            $this->notification()->warning(
                $title = 'Warning',
                $description = 'Please select both class and section.'
            );
            return;
        }

        $this->issueStudentsLoaded = true;
        $this->selectedStudents = [];
    }

    /**
     * Get students with their marks-complete status for the issue screen.
     */
    #[\Livewire\Attributes\Computed]
    public function issueStudents()
    {
        if (!$this->issueStudentsLoaded || !$this->issueStandard || !$this->issueSection) {
            return collect();
        }

        $orgId = Auth::user()->organization_id;

        $students = StudentDetail::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->issueStandard)
            ->where('section_id', $this->issueSection)
            ->orderBy('full_name')
            ->get();

        // Get all active/published exams for this organization
        $exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->get();

        if ($exams->isEmpty()) {
            return $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'admission_no' => $student->admission_no,
                    'roll_no' => $student->roll_no ?? 'N/A',
                    'marks_complete' => false,
                    'already_issued' => false,
                    'missing_info' => 'No published exams found',
                ];
            });
        }

        // Get all subjects for this standard+section via SectionSubject
        $subjectIds = SectionSubject::where('section_id', $this->issueSection)
            ->where('standard_id', $this->issueStandard)
            ->where('organization_id', $orgId)
            ->pluck('subject_id')
            ->toArray();

        if (empty($subjectIds)) {
            return $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'admission_no' => $student->admission_no,
                    'roll_no' => $student->roll_no ?? 'N/A',
                    'marks_complete' => false,
                    'already_issued' => false,
                    'missing_info' => 'No subjects assigned to this section',
                ];
            });
        }

        $examIds = $exams->pluck('id')->toArray();
        $totalRequired = count($examIds) * count($subjectIds);

        // Get already issued report cards for these students
        $issuedStudentIds = ReportCardModel::where('organization_id', $orgId)
            ->where('standard_id', $this->issueStandard)
            ->where('section_id', $this->issueSection)
            ->where('status', 'issued')
            ->pluck('student_detail_id')
            ->toArray();

        // Batch load all exam copies for these students
        $examCopyCounts = ExamCopy::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->whereIn('exam_id', $examIds)
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('student_detail_id, COUNT(DISTINCT CONCAT(exam_id, "-", subject_id)) as marks_count')
            ->groupBy('student_detail_id')
            ->pluck('marks_count', 'student_detail_id')
            ->toArray();

        return $students->map(function ($student) use ($totalRequired, $examCopyCounts, $issuedStudentIds, $examIds, $subjectIds) {
            $studentMarksCount = $examCopyCounts[$student->id] ?? 0;
            $marksComplete = $studentMarksCount >= $totalRequired;
            $alreadyIssued = in_array($student->id, $issuedStudentIds);

            $missingInfo = '';
            if (!$marksComplete) {
                $missing = $totalRequired - $studentMarksCount;
                $missingInfo = "{$missing} of {$totalRequired} exam-subject marks missing";
            }

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'admission_no' => $student->admission_no,
                'roll_no' => $student->roll_no ?? 'N/A',
                'marks_complete' => $marksComplete,
                'already_issued' => $alreadyIssued,
                'missing_info' => $missingInfo,
            ];
        });
    }

    /**
     * Toggle select all eligible students.
     */
    public function toggleAllEligible($select)
    {
        if ($select) {
            $this->selectedStudents = $this->issueStudents
                ->filter(fn($s) => $s['marks_complete'] && !$s['already_issued'])
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    /**
     * Issue report cards for selected students.
     */
    public function issueReportCards()
    {
        if (empty($this->selectedStudents)) {
            $this->notification()->warning(
                $title = 'Warning',
                $description = 'Please select at least one student.'
            );
            return;
        }

        try {
            $orgId = Auth::user()->organization_id;
            $currentYear = now()->month >= 4
                ? now()->year . '-' . (now()->year + 1)
                : (now()->year - 1) . '-' . now()->year;

            $issuedCount = 0;
            $skippedCount = 0;

            foreach ($this->selectedStudents as $studentId) {
                // Check if already issued
                $existing = ReportCardModel::where('organization_id', $orgId)
                    ->where('student_detail_id', $studentId)
                    ->where('standard_id', $this->issueStandard)
                    ->where('section_id', $this->issueSection)
                    ->where('status', 'issued')
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                ReportCardModel::create([
                    'organization_id' => $orgId,
                    'student_detail_id' => $studentId,
                    'standard_id' => $this->issueStandard,
                    'section_id' => $this->issueSection,
                    'academic_year' => $currentYear,
                    'issued_at' => now(),
                    'issued_by' => Auth::id(),
                    'status' => 'issued',
                ]);

                $issuedCount++;
            }

            $message = "Successfully issued {$issuedCount} report card(s).";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} skipped (already issued).";
            }

            $this->notification()->success(
                $title = 'Success!',
                $description = $message
            );

            // Refresh the student list
            $this->selectedStudents = [];
            unset($this->issueStudents);

        } catch (\Exception $e) {
            $this->notification()->error(
                $title = 'Error!',
                $description = 'Failed to issue report cards: ' . $e->getMessage()
            );
        }
    }

    /**
     * Revoke a report card.
     */
    public function revokeReportCard($id)
    {
        try {
            $reportCard = ReportCardModel::where('id', $id)
                ->where('organization_id', Auth::user()->organization_id)
                ->first();

            if ($reportCard) {
                $reportCard->update(['status' => 'revoked']);
                $this->notification()->success(
                    $title = 'Revoked!',
                    $description = 'Report card has been revoked.'
                );
            }
        } catch (\Exception $e) {
            $this->notification()->error(
                $title = 'Error!',
                $description = 'Failed to revoke report card: ' . $e->getMessage()
            );
        }
    }

    /**
     * Reset list filters.
     */
    public function resetFilters()
    {
        $this->reset(['search', 'filterStandard', 'filterSection', 'filterStatus']);
        $this->resetPage();
    }

    public function updatedFilterStandard()
    {
        $this->filterSection = '';
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterSection()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    /**
     * Get standards for dropdowns.
     */
    #[\Livewire\Attributes\Computed]
    public function standards()
    {
        return Standard::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * Get sections for the filter standard.
     */
    #[\Livewire\Attributes\Computed]
    public function filterSections()
    {
        if (!$this->filterStandard) {
            return collect();
        }

        return Section::where('standard_id', $this->filterStandard)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * Get sections for the issue standard.
     */
    #[\Livewire\Attributes\Computed]
    public function issueSections()
    {
        if (!$this->issueStandard) {
            return collect();
        }

        return Section::where('standard_id', $this->issueStandard)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * Analytics counts.
     */
    #[\Livewire\Attributes\Computed]
    public function analytics()
    {
        $orgId = Auth::user()->organization_id;

        $studentsQuery = StudentDetail::where('organization_id', $orgId);
        $reportCardsQuery = ReportCardModel::where('organization_id', $orgId);

        if ($this->filterStandard) {
            $studentsQuery->where('standard_id', $this->filterStandard);
            $reportCardsQuery->where('standard_id', $this->filterStandard);
        }
        if ($this->filterSection) {
            $studentsQuery->where('section_id', $this->filterSection);
            $reportCardsQuery->where('section_id', $this->filterSection);
        }

        $totalStudents = (clone $studentsQuery)->count();
        $activeStudents = (clone $studentsQuery)
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->count();
        $issued = (clone $reportCardsQuery)->where('status', 'issued')->count();
        $pending = $totalStudents - $issued;
        if ($pending < 0) $pending = 0;

        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'issued' => $issued,
            'pending' => $pending,
        ];
    }

    public function render()
    {
        $reportCards = collect();

        if ($this->viewMode === 'list') {
            $query = ReportCardModel::with([
                'studentDetail',
                'studentDetail.standard',
                'studentDetail.section',
                'issuedBy',
            ])->where('organization_id', Auth::user()->organization_id);

            if ($this->search) {
                $query->whereHas('studentDetail', function ($q) {
                    $q->where('full_name', 'like', '%' . $this->search . '%')
                        ->orWhere('admission_no', 'like', '%' . $this->search . '%');
                });
            }

            if ($this->filterStandard) {
                $query->where('standard_id', $this->filterStandard);
            }

            if ($this->filterSection) {
                $query->where('section_id', $this->filterSection);
            }

            if ($this->filterStatus) {
                $query->where('status', $this->filterStatus);
            }

            $reportCards = $query->latest('issued_at')->paginate($this->perPage);
        }

        return view('livewire.accounts.report-card', [
            'reportCards' => $reportCards,
        ]);
    }
}
