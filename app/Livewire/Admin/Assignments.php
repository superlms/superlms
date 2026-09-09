<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Assignment\Assignment;
use App\Models\Admin\Assignment\AssignmentQuestion;
use App\Models\Admin\Assignment\AssignmentQuestionOption;
use App\Models\Admin\Assignment\AssignmentSubmission;
use App\Models\Admin\Assignment\AssignmentSubmissionAnswer;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Assignments — the screen that used to be "Quiz".
 *
 * Tab 1 (Assignments): pick class → section → subject, set a start/end window
 *   and hand out either a written task (text and/or a file) or a set of MCQs.
 * Tab 2 (Responses): for one assignment, the whole class roster showing who
 *   attempted, what they submitted, and inline marks + status.
 */
class Assignments extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    public const STATUSES = ['submitted', 'reviewed', 'approved', 'rejected'];

    #[Url]
    public string $activeTab = 'assignments';   // assignments | responses

    // ─── Lookups ─────────────────────────────────────────────────────────
    public $standards = [];

    // ─── Assignments tab filters ─────────────────────────────────────────
    #[Url]
    public $search = '';
    #[Url]
    public $filterStandard = '';
    #[Url]
    public $filterSection = '';
    #[Url]
    public $filterSubject = '';
    #[Url]
    public $filterType = '';
    #[Url]
    public $filterWindow = '';                  // open | upcoming | closed
    public $filterSections = [];
    public $filterSubjects = [];
    public int $perPage = 10;

    // ─── Add / Edit form ─────────────────────────────────────────────────
    public bool $openForm = false;
    public ?int $editId = null;
    public $standard_id = '';
    public $section_id = '';
    public $subject_id = '';
    public string $title = '';
    public string $description = '';
    public string $type = 'written';            // written | mcq
    public string $submission_mode = 'both';    // text | file | both  (written only)
    public $start_date = '';
    public $end_date = '';
    public $total_marks = '';
    public bool $is_active = true;
    public $attachment;                         // freshly picked upload
    public $existingFile = null;                // already-saved attachment url
    public bool $removeFile = false;
    public $formSections = [];
    public $formSubjects = [];

    /** MCQ rows: [{id, question_text, marks, options:[{id, text, is_correct}]}] */
    public array $questions = [];

    // ─── View modal ──────────────────────────────────────────────────────
    public bool $openView = false;
    public $viewAssignment = null;
    public array $viewQuestions = [];

    // ─── Delete confirm ──────────────────────────────────────────────────
    public bool $openDelete = false;
    public ?int $deleteId = null;
    public string $deleteTitle = '';

    // ─── Responses tab ───────────────────────────────────────────────────
    #[Url]
    public $resStandard = '';
    #[Url]
    public $resSection = '';
    #[Url]
    public $resSubject = '';
    #[Url]
    public $resAssignment = '';
    #[Url]
    public $resStatus = '';                     // pending | submitted | reviewed | approved | rejected
    public $resSections = [];
    public $resSubjects = [];
    public $resAssignments = [];

    /** Inline marking state, keyed by submission id. */
    public array $marking = [];

    // ─── Single-response drawer ──────────────────────────────────────────
    public bool $openResponse = false;
    public ?int $responseId = null;
    public $responseRow = null;
    public array $responseAnswers = [];

    // ═════════════════════════════════════════════════════════════════════
    //  Lifecycle
    // ═════════════════════════════════════════════════════════════════════

    public function mount(): void
    {
        $this->standards = Standard::where('organization_id', $this->orgId())
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $this->filterSections = $this->sectionsFor($this->filterStandard);
        $this->filterSubjects = $this->subjectsFor($this->filterStandard, $this->filterSection);
        $this->resSections    = $this->sectionsFor($this->resStandard);
        $this->resSubjects    = $this->subjectsFor($this->resStandard, $this->resSection);
        $this->loadResponseAssignments();
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    private function sectionsFor($standardId)
    {
        return $standardId
            ? Section::where('standard_id', $standardId)->where('is_active', true)->orderBy('id')->get()
            : collect();
    }

    private function subjectsFor($standardId, $sectionId = null)
    {
        if (!$standardId) {
            return collect();
        }

        $query = Subject::where('organization_id', $this->orgId())->where('is_active', true);

        if ($sectionId) {
            $query->whereHas('sections', fn($q) => $q->where('sections.id', $sectionId));
        } else {
            $query->whereHas('standards', fn($q) => $q->where('standards.id', $standardId));
        }

        return $query->orderBy('id')->get();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Assignments tab — filters
    // ═════════════════════════════════════════════════════════════════════

    public function updatedFilterStandard(): void
    {
        $this->filterSection  = '';
        $this->filterSubject  = '';
        $this->filterSections = $this->sectionsFor($this->filterStandard);
        $this->filterSubjects = $this->subjectsFor($this->filterStandard);
        $this->resetPage();
    }

    public function updatedFilterSection(): void
    {
        $this->filterSubject  = '';
        $this->filterSubjects = $this->subjectsFor($this->filterStandard, $this->filterSection);
        $this->resetPage();
    }

    public function updatedFilterSubject(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStandard', 'filterSection', 'filterSubject', 'filterType', 'filterWindow']);
        $this->filterSections = collect();
        $this->filterSubjects = collect();
        $this->resetPage();
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Assignments tab — create / edit
    // ═════════════════════════════════════════════════════════════════════

    public function onAdd(): void
    {
        $this->resetForm();

        // Carry whatever the list is filtered by straight into the form.
        $this->standard_id  = $this->filterStandard;
        $this->section_id   = $this->filterSection;
        $this->subject_id   = $this->filterSubject;
        $this->formSections = $this->sectionsFor($this->standard_id);
        $this->formSubjects = $this->subjectsFor($this->standard_id, $this->section_id);

        $this->start_date = now()->format('Y-m-d\TH:i');
        $this->end_date   = now()->addWeek()->format('Y-m-d\TH:i');

        $this->openForm = true;
    }

    public function onEdit(int $id): void
    {
        $assignment = Assignment::with('questions.options')
            ->where('organization_id', $this->orgId())
            ->find($id);

        if (!$assignment) {
            $this->notification()->error('Assignment not found.');
            return;
        }

        $this->resetForm();

        $this->editId          = $assignment->id;
        $this->standard_id     = $assignment->standard_id;
        $this->section_id      = $assignment->section_id;
        $this->subject_id      = $assignment->subject_id;
        $this->title           = $assignment->title ?? '';
        $this->description     = $assignment->description ?? '';
        $this->type            = $assignment->type ?? 'written';
        $this->submission_mode = $assignment->submission_mode ?? 'both';
        $this->start_date      = $assignment->start_date?->format('Y-m-d\TH:i') ?? '';
        $this->end_date        = $assignment->end_date?->format('Y-m-d\TH:i') ?? '';
        $this->total_marks     = $assignment->total_marks ?: '';
        $this->is_active       = (bool) $assignment->is_active;
        $this->existingFile    = $assignment->file;

        $this->formSections = $this->sectionsFor($this->standard_id);
        $this->formSubjects = $this->subjectsFor($this->standard_id, $this->section_id);

        $this->questions = $assignment->questions->map(fn($q) => [
            'id'            => $q->id,
            'question_text' => $q->question_text,
            'marks'         => $q->marks,
            'options'       => $q->options->map(fn($o) => [
                'id'         => $o->id,
                'text'       => $o->option_text,
                'is_correct' => (bool) $o->is_correct,
            ])->toArray(),
        ])->toArray();

        if ($this->type === 'mcq' && empty($this->questions)) {
            $this->addQuestion();
        }

        $this->openForm = true;
    }

    public function closeForm(): void
    {
        $this->openForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editId', 'standard_id', 'section_id', 'subject_id', 'title', 'description',
            'type', 'submission_mode', 'start_date', 'end_date', 'total_marks',
            'attachment', 'existingFile', 'removeFile', 'questions',
        ]);
        $this->is_active    = true;
        $this->formSections = collect();
        $this->formSubjects = collect();
        $this->resetValidation();
    }

    public function updatedStandardId(): void
    {
        $this->section_id   = '';
        $this->subject_id   = '';
        $this->formSections = $this->sectionsFor($this->standard_id);
        $this->formSubjects = $this->subjectsFor($this->standard_id);
    }

    public function updatedSectionId(): void
    {
        $this->subject_id   = '';
        $this->formSubjects = $this->subjectsFor($this->standard_id, $this->section_id);
    }

    public function updatedType(): void
    {
        if ($this->type === 'mcq' && empty($this->questions)) {
            $this->addQuestion();
        }
    }

    // ─── MCQ rows ────────────────────────────────────────────────────────

    public function addQuestion(): void
    {
        $this->questions[] = [
            'id'            => null,
            'question_text' => '',
            'marks'         => 1,
            'options'       => [
                ['id' => null, 'text' => '', 'is_correct' => true],
                ['id' => null, 'text' => '', 'is_correct' => false],
                ['id' => null, 'text' => '', 'is_correct' => false],
                ['id' => null, 'text' => '', 'is_correct' => false],
            ],
        ];
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function setCorrectOption(int $qIndex, int $optIndex): void
    {
        foreach ($this->questions[$qIndex]['options'] as $i => $opt) {
            $this->questions[$qIndex]['options'][$i]['is_correct'] = ($i === $optIndex);
        }
    }

    // ─── Save ────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'standard_id' => 'required',
            'section_id'  => 'required',
            'subject_id'  => 'required',
            'title'       => 'required|string|max:255',
            'type'        => 'required|in:written,mcq',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'total_marks' => 'nullable|integer|min:0|max:10000',
            'attachment'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:5120',
        ], [
            'standard_id.required'    => 'Pick a class.',
            'section_id.required'     => 'Pick a section.',
            'subject_id.required'     => 'Pick a subject.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'attachment.max'          => 'Attachment must be 5 MB or smaller.',
        ]);

        if ($this->type === 'mcq' && !$this->questionsAreValid()) {
            return;
        }

        $org = $this->orgId();

        try {
            DB::beginTransaction();

            $data = [
                'organization_id' => $org,
                'standard_id'     => $this->standard_id,
                'section_id'      => $this->section_id,
                'subject_id'      => $this->subject_id,
                'title'           => $this->title,
                'description'     => $this->description ?: null,
                'type'            => $this->type,
                'submission_mode' => $this->type === 'mcq' ? 'text' : $this->submission_mode,
                'start_date'      => $this->start_date,
                'end_date'        => $this->end_date,
                'total_marks'     => (int) ($this->total_marks ?: 0),
                'is_active'       => $this->is_active,
            ];

            $assignment = $this->editId
                ? Assignment::where('organization_id', $org)->findOrFail($this->editId)
                : new Assignment(['user_id' => Auth::id()]);

            // Attachment: replace it, drop it, or leave it alone.
            if ($this->attachment) {
                $this->deleteStoredFile($assignment->file);
                $data['file'] = $this->storeFile($this->attachment, 'admin/assignments/files');
            } elseif ($this->removeFile && $assignment->file) {
                $this->deleteStoredFile($assignment->file);
                $data['file'] = null;
            }

            $assignment->fill($data)->save();

            if ($this->type === 'mcq') {
                $this->syncQuestions($assignment, $org);
            } else {
                // Switching away from MCQ clears the question set.
                $this->wipeQuestions($assignment);
            }

            DB::commit();

            $this->notification()->success($this->editId ? 'Assignment updated!' : 'Assignment created!');
            $this->closeForm();
            $this->loadResponseAssignments();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Assignment save failed: ' . $e->getMessage());
        }
    }

    /** Every MCQ row needs text, two filled options and one marked correct. */
    private function questionsAreValid(): bool
    {
        if (empty($this->questions)) {
            $this->notification()->error('Add at least one question.');
            return false;
        }

        foreach ($this->questions as $i => $row) {
            $label = 'Q' . ($i + 1);

            if (trim((string) $row['question_text']) === '') {
                $this->notification()->error($label . ': question text is required.');
                return false;
            }

            $filled  = 0;
            $correct = false;

            foreach ($row['options'] as $opt) {
                if (trim((string) $opt['text']) === '') {
                    continue;
                }

                $filled++;

                if (!empty($opt['is_correct'])) {
                    $correct = true;
                }
            }

            if ($filled < 2) {
                $this->notification()->error($label . ': add at least two options.');
                return false;
            }

            if (!$correct) {
                $this->notification()->error($label . ': mark the correct option.');
                return false;
            }
        }

        return true;
    }

    private function syncQuestions(Assignment $assignment, int $org): void
    {
        $keptQuestionIds = [];

        foreach (array_values($this->questions) as $order => $row) {
            $question = !empty($row['id'])
                ? AssignmentQuestion::where('assignment_id', $assignment->id)->find($row['id'])
                : null;

            $payload = [
                'organization_id' => $org,
                'assignment_id'   => $assignment->id,
                'question_text'   => $row['question_text'],
                'marks'           => max(1, (int) ($row['marks'] ?: 1)),
                'order'           => $order,
            ];

            if ($question) {
                $question->update($payload);
            } else {
                $question = AssignmentQuestion::create($payload);
            }

            $keptQuestionIds[] = $question->id;
            $keptOptionIds     = [];

            foreach ($row['options'] as $opt) {
                if (trim((string) $opt['text']) === '') {
                    continue;
                }

                $optionPayload = [
                    'organization_id'        => $org,
                    'assignment_question_id' => $question->id,
                    'option_text'            => $opt['text'],
                    'is_correct'             => !empty($opt['is_correct']),
                ];

                $option = !empty($opt['id'])
                    ? AssignmentQuestionOption::where('assignment_question_id', $question->id)->find($opt['id'])
                    : null;

                if ($option) {
                    $option->update($optionPayload);
                } else {
                    $option = AssignmentQuestionOption::create($optionPayload);
                }

                $keptOptionIds[] = $option->id;
            }

            AssignmentQuestionOption::where('assignment_question_id', $question->id)
                ->whereNotIn('id', $keptOptionIds ?: [0])
                ->delete();
        }

        $dropped = AssignmentQuestion::where('assignment_id', $assignment->id)
            ->whereNotIn('id', $keptQuestionIds ?: [0])
            ->pluck('id');

        if ($dropped->isNotEmpty()) {
            AssignmentQuestionOption::whereIn('assignment_question_id', $dropped)->delete();
            AssignmentQuestion::whereIn('id', $dropped)->delete();
        }
    }

    private function wipeQuestions(Assignment $assignment): void
    {
        $ids = AssignmentQuestion::where('assignment_id', $assignment->id)->pluck('id');

        if ($ids->isNotEmpty()) {
            AssignmentQuestionOption::whereIn('assignment_question_id', $ids)->delete();
            AssignmentQuestion::whereIn('id', $ids)->delete();
        }
    }

    private function storeFile($file, string $folder): string
    {
        $path = $file->store($folder, 's3');
        Storage::disk('s3')->setVisibility($path, 'public');

        return Storage::disk('s3')->url($path);
    }

    private function deleteStoredFile(?string $url): void
    {
        if (!$url) {
            return;
        }

        try {
            Storage::disk('s3')->delete(ltrim(parse_url($url, PHP_URL_PATH), '/'));
        } catch (\Throwable $e) {
            logger()->warning('Assignment file delete failed: ' . $e->getMessage());
        }
    }

    // ─── View ────────────────────────────────────────────────────────────

    public function onView(int $id): void
    {
        $assignment = Assignment::with(['standard', 'section', 'subject', 'user', 'questions.options'])
            ->withCount('submissions')
            ->where('organization_id', $this->orgId())
            ->find($id);

        if (!$assignment) {
            $this->notification()->error('Assignment not found.');
            return;
        }

        $this->viewAssignment = $assignment;
        $this->viewQuestions  = $assignment->questions->map(fn($q) => [
            'question_text' => $q->question_text,
            'marks'         => $q->marks,
            'options'       => $q->options->map(fn($o) => [
                'text'       => $o->option_text,
                'is_correct' => (bool) $o->is_correct,
            ])->toArray(),
        ])->toArray();

        $this->openView = true;
    }

    public function closeView(): void
    {
        $this->openView       = false;
        $this->viewAssignment = null;
        $this->viewQuestions  = [];
    }

    // ─── Delete ──────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $assignment = Assignment::where('organization_id', $this->orgId())->find($id);

        if (!$assignment) {
            $this->notification()->error('Assignment not found.');
            return;
        }

        $this->deleteId    = $assignment->id;
        $this->deleteTitle = $assignment->title;
        $this->openDelete  = true;
    }

    public function cancelDelete(): void
    {
        $this->openDelete  = false;
        $this->deleteId    = null;
        $this->deleteTitle = '';
    }

    public function destroyAssignment(): void
    {
        $assignment = Assignment::where('organization_id', $this->orgId())->find($this->deleteId);

        if (!$assignment) {
            $this->cancelDelete();
            return;
        }

        try {
            DB::beginTransaction();

            $submissionIds = AssignmentSubmission::where('assignment_id', $assignment->id)->pluck('id');
            AssignmentSubmissionAnswer::whereIn('assignment_submission_id', $submissionIds)->delete();
            AssignmentSubmission::whereIn('id', $submissionIds)->delete();
            $this->wipeQuestions($assignment);
            $this->deleteStoredFile($assignment->file);
            $assignment->delete();

            DB::commit();

            $this->notification()->success('Assignment deleted.');
            $this->cancelDelete();
            $this->loadResponseAssignments();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Responses tab
    // ═════════════════════════════════════════════════════════════════════

    public function updatedResStandard(): void
    {
        $this->resSection    = '';
        $this->resSubject    = '';
        $this->resAssignment = '';
        $this->resSections   = $this->sectionsFor($this->resStandard);
        $this->resSubjects   = $this->subjectsFor($this->resStandard);
        $this->loadResponseAssignments();
    }

    public function updatedResSection(): void
    {
        $this->resSubject    = '';
        $this->resAssignment = '';
        $this->resSubjects   = $this->subjectsFor($this->resStandard, $this->resSection);
        $this->loadResponseAssignments();
    }

    public function updatedResSubject(): void
    {
        $this->resAssignment = '';
        $this->loadResponseAssignments();
    }

    public function updatedResAssignment(): void
    {
        $this->marking = [];
    }

    private function loadResponseAssignments(): void
    {
        if (!$this->resStandard) {
            $this->resAssignments = collect();
            return;
        }

        $this->resAssignments = Assignment::where('organization_id', $this->orgId())
            ->where('standard_id', $this->resStandard)
            ->when($this->resSection, fn($q) => $q->where('section_id', $this->resSection))
            ->when($this->resSubject, fn($q) => $q->where('subject_id', $this->resSubject))
            ->orderByDesc('id')
            ->get(['id', 'title', 'type', 'start_date', 'end_date']);
    }

    /** Roster rows for the selected assignment, filling the summary counters as it goes. */
    private function responseRows(?Assignment $assignment, array &$summary)
    {
        if (!$assignment) {
            return collect();
        }

        $students = StudentDetail::where('organization_id', $this->orgId())
            ->where('standard_id', $assignment->standard_id)
            ->when($assignment->section_id, fn($q) => $q->where('section_id', $assignment->section_id))
            ->orderByRaw('CAST(roll_no AS UNSIGNED), full_name')
            ->get(['id', 'user_id', 'full_name', 'roll_no', 'image']);

        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)->get()->keyBy('user_id');

        $rows = $students->map(function ($student) use ($submissions) {
            $submission = $submissions->get($student->user_id);

            return [
                'student'    => $student,
                'submission' => $submission,
                'status'     => $submission ? $submission->status : 'pending',
            ];
        });

        $summary['total']     = $rows->count();
        $summary['attempted'] = $rows->filter(fn($r) => (bool) $r['submission'])->count();
        $summary['pending']   = $summary['total'] - $summary['attempted'];
        $summary['reviewed']  = $rows->filter(fn($r) => in_array($r['status'], ['reviewed', 'approved'], true))->count();

        if ($this->resStatus !== '') {
            $rows = $rows->where('status', $this->resStatus);
        }

        return $rows->values();
    }

    /** Seed the inline marks/status/remarks inputs for one submission. */
    private function primeMarking(AssignmentSubmission $submission): void
    {
        if (!isset($this->marking[$submission->id])) {
            $this->marking[$submission->id] = [
                'marks'   => $submission->marks !== null ? (string) (0 + $submission->marks) : '',
                'status'  => $submission->status,
                'remarks' => $submission->remarks ?? '',
            ];
        }
    }

    public function saveMarking(int $submissionId): void
    {
        $submission = AssignmentSubmission::with('assignment')
            ->where('organization_id', $this->orgId())
            ->find($submissionId);

        if (!$submission) {
            $this->notification()->error('Submission not found.');
            return;
        }

        $row    = $this->marking[$submissionId] ?? [];
        $marks  = trim((string) ($row['marks'] ?? ''));
        $status = $row['status'] ?? $submission->status;

        if ($marks !== '' && (!is_numeric($marks) || (float) $marks < 0)) {
            $this->notification()->error('Marks must be a number of 0 or more.');
            return;
        }

        $max = $submission->assignment?->maxMarks() ?? 0;

        if ($marks !== '' && $max > 0 && (float) $marks > $max) {
            $this->notification()->error('Marks cannot be more than ' . $max . '.');
            return;
        }

        if (!in_array($status, self::STATUSES, true)) {
            $this->notification()->error('Unknown status.');
            return;
        }

        $submission->update([
            'marks'       => $marks === '' ? null : (float) $marks,
            'status'      => $status,
            'remarks'     => $row['remarks'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        $this->notification()->success('Saved for ' . ($submission->student?->full_name ?? 'student') . '.');
    }

    /** Give every MCQ attempt its auto-graded score as marks in one go. */
    public function applyMcqScores(): void
    {
        $assignment = Assignment::where('organization_id', $this->orgId())->find($this->resAssignment);

        if (!$assignment || !$assignment->isMcq()) {
            $this->notification()->error('Pick an MCQ assignment first.');
            return;
        }

        $updated = 0;

        AssignmentSubmission::where('assignment_id', $assignment->id)
            ->whereNotNull('mcq_score')
            ->get()
            ->each(function ($submission) use (&$updated) {
                $submission->update([
                    'marks'       => $submission->mcq_score,
                    'status'      => $submission->status === 'submitted' ? 'reviewed' : $submission->status,
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                ]);
                $updated++;
            });

        $this->marking = [];
        $this->notification()->success($updated . ' attempt(s) marked from their MCQ score.');
    }

    public function onViewResponse(int $submissionId): void
    {
        $submission = AssignmentSubmission::with(['student', 'user', 'assignment.questions.options', 'answers'])
            ->where('organization_id', $this->orgId())
            ->find($submissionId);

        if (!$submission) {
            $this->notification()->error('Submission not found.');
            return;
        }

        $this->responseId  = $submission->id;
        $this->responseRow = $submission;

        $chosen = $submission->answers->keyBy('assignment_question_id');

        $this->responseAnswers = $submission->assignment
            ? $submission->assignment->questions->map(function ($q) use ($chosen) {
                $answer = $chosen->get($q->id);

                return [
                    'question_text' => $q->question_text,
                    'marks'         => $q->marks,
                    'chosen_id'     => $answer?->assignment_question_option_id,
                    'is_correct'    => (bool) ($answer?->is_correct),
                    'options'       => $q->options->map(fn($o) => [
                        'id'         => $o->id,
                        'text'       => $o->option_text,
                        'is_correct' => (bool) $o->is_correct,
                    ])->toArray(),
                ];
            })->toArray()
            : [];

        $this->openResponse = true;
    }

    public function closeResponse(): void
    {
        $this->openResponse    = false;
        $this->responseId      = null;
        $this->responseRow     = null;
        $this->responseAnswers = [];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Render
    // ═════════════════════════════════════════════════════════════════════

    public function render()
    {
        $org = $this->orgId();

        $statistics = [
            'total'    => Assignment::where('organization_id', $org)->count(),
            'open'     => Assignment::where('organization_id', $org)
                ->where('is_active', true)
                ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->count(),
            'mcq'      => Assignment::where('organization_id', $org)->where('type', 'mcq')->count(),
            'attempts' => AssignmentSubmission::where('organization_id', $org)->count(),
        ];

        $assignments = collect();

        if ($this->activeTab === 'assignments') {
            $assignments = Assignment::with(['standard', 'section', 'subject', 'user'])
                ->withCount(['submissions', 'questions'])
                ->where('organization_id', $org)
                ->when($this->search, fn($q) => $q->where(function ($sub) {
                    $sub->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                }))
                ->when($this->filterStandard, fn($q) => $q->where('standard_id', $this->filterStandard))
                ->when($this->filterSection, fn($q) => $q->where('section_id', $this->filterSection))
                ->when($this->filterSubject, fn($q) => $q->where('subject_id', $this->filterSubject))
                ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
                ->when($this->filterWindow === 'open', fn($q) => $q
                    ->where(fn($s) => $s->whereNull('start_date')->orWhere('start_date', '<=', now()))
                    ->where(fn($s) => $s->whereNull('end_date')->orWhere('end_date', '>=', now())))
                ->when($this->filterWindow === 'upcoming', fn($q) => $q->where('start_date', '>', now()))
                ->when($this->filterWindow === 'closed', fn($q) => $q->where('end_date', '<', now()))
                ->orderByDesc('id')
                ->paginate($this->perPage);
        }

        $selectedAssignment = null;
        $responseRows       = collect();
        $responseSummary    = ['total' => 0, 'attempted' => 0, 'pending' => 0, 'reviewed' => 0];

        if ($this->activeTab === 'responses' && $this->resAssignment) {
            $selectedAssignment = Assignment::with(['standard', 'section', 'subject'])
                ->where('organization_id', $org)
                ->find($this->resAssignment);

            $responseRows = $this->responseRows($selectedAssignment, $responseSummary);

            foreach ($responseRows as $row) {
                if ($row['submission']) {
                    $this->primeMarking($row['submission']);
                }
            }
        }

        return view('livewire.admin.assignments', compact(
            'assignments',
            'statistics',
            'selectedAssignment',
            'responseRows',
            'responseSummary'
        ));
    }
}
