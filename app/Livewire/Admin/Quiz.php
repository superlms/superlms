<?php

namespace App\Livewire\Admin;

use App\Models\Mcq\McqQuestion;
use App\Models\Mcq\McqOption;
use App\Models\Mcq\McqUserAnswer;
use App\Models\Student\Chapter;
use App\Models\Student\Topic;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Quiz extends Component
{
    use WireUiActions, WithPagination;

    // ─── Filters ─────────────────────────────────────────────────────────
    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $filterSubject  = '';
    public $filterSections  = [];
    public $filterSubjects  = [];
    public $standards       = [];

    // ─── Expanded state ──────────────────────────────────────────────────
    public array $expandedChapters = [];

    // ─── Add MCQ Modal ───────────────────────────────────────────────────
    public $openAddModal     = false;
    public $addTargetType    = '';  // chapter or topic
    public $addTargetId      = null;
    public $addTargetName    = '';
    public $mcqRows          = [];  // [{question_text, time_limit, options: [{text, is_correct}]}]

    // ─── Edit MCQ Modal ──────────────────────────────────────────────────
    public $openEditModal    = false;
    public $editTargetType   = '';
    public $editTargetId     = null;
    public $editTargetName   = '';
    public $editMcqs         = [];  // [{id, question_text, time_limit, options: [{id, text, is_correct}]}]

    // ─── View MCQ Modal ──────────────────────────────────────────────────
    public $openViewModal    = false;
    public $viewTargetName   = '';
    public $viewMcqs         = [];

    // ─── Delete MCQ Modal (select-from-list) ─────────────────────────────
    public $openDeleteModal  = false;
    public $deleteTargetType = '';
    public $deleteTargetId   = null;
    public $deleteTargetName = '';
    public $deleteMcqList    = [];
    public $selectedDeleteIds = [];

    // ─── Final delete confirm overlay ────────────────────────────────────
    public bool $showDeleteConfirm = false;

    // ─── Stats ───────────────────────────────────────────────────────────
    public $totalQuestions = 0;

    public function mount(): void
    {
        $org = Auth::user()->organization_id;
        $this->standards = Standard::where('organization_id', $org)->where('is_active', true)->orderBy('id')->get();
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $org = Auth::user()->organization_id;
        $this->totalQuestions = McqQuestion::where('organization_id', $org)->count();
    }

    // ─── Filter handlers ─────────────────────────────────────────────────
    public function updatedFilterStandard(): void
    {
        $this->resetPage();
        $this->filterSection = '';
        $this->filterSubject = '';
        $this->filterSections = $this->filterStandard
            ? Section::where('standard_id', $this->filterStandard)->where('is_active', true)->get() : [];
        $this->loadFilterSubjects();
    }

    public function updatedFilterSection(): void
    {
        $this->resetPage();
        $this->filterSubject = '';
        $this->loadFilterSubjects();
    }

    public function updatedFilterSubject(): void
    {
        $this->resetPage();
    }

    private function loadFilterSubjects(): void
    {
        if (!$this->filterStandard) {
            $this->filterSubjects = [];
            return;
        }
        $org = Auth::user()->organization_id;
        $query = Subject::where('organization_id', $org)->where('is_active', true);
        if ($this->filterSection) {
            $query->whereHas('sections', fn($q) => $q->where('sections.id', $this->filterSection));
        } else {
            $query->whereHas('standards', fn($q) => $q->where('standards.id', $this->filterStandard));
        }
        $this->filterSubjects = $query->orderBy('id')->get();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterStandard', 'filterSection', 'filterSubject']);
        $this->filterSections = [];
        $this->filterSubjects = [];
        $this->resetPage();
    }

    public function toggleChapter(int $id): void
    {
        $this->expandedChapters = in_array($id, $this->expandedChapters)
            ? array_values(array_diff($this->expandedChapters, [$id]))
            : array_merge($this->expandedChapters, [$id]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ADD MCQ
    // ═══════════════════════════════════════════════════════════════════════

    public function onAddMcq(string $type, int $id): void
    {
        $this->resetAddForm();
        $this->addTargetType = $type;
        $this->addTargetId   = $id;

        if ($type === 'chapter') {
            $this->addTargetName = Chapter::find($id)?->name ?? '';
        } else {
            $this->addTargetName = Topic::find($id)?->topic_name ?? '';
        }

        $this->addMcqRow();
        $this->openAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->openAddModal = false;
        $this->resetAddForm();
    }

    private function resetAddForm(): void
    {
        $this->reset(['addTargetType', 'addTargetId', 'addTargetName', 'mcqRows']);
    }

    public function addMcqRow(): void
    {
        $this->mcqRows[] = [
            'question_text' => '',
            'time_limit'    => 30,
            'options'       => [
                ['text' => '', 'is_correct' => false],
                ['text' => '', 'is_correct' => false],
                ['text' => '', 'is_correct' => false],
                ['text' => '', 'is_correct' => false],
            ],
        ];
    }

    public function removeMcqRow(int $index): void
    {
        unset($this->mcqRows[$index]);
        $this->mcqRows = array_values($this->mcqRows);
    }

    public function setCorrectOption(int $rowIndex, int $optIndex): void
    {
        foreach ($this->mcqRows[$rowIndex]['options'] as $i => &$opt) {
            $opt['is_correct'] = ($i === $optIndex);
        }
    }

    public function onSaveMcqs(): void
    {
        if (empty($this->mcqRows)) {
            $this->notification()->error('Add at least one question.');
            return;
        }

        foreach ($this->mcqRows as $i => $row) {
            if (empty($row['question_text'])) {
                $this->notification()->error('Q' . ($i + 1) . ': Question text is required.');
                return;
            }
            $hasCorrect = false;
            $hasEmpty   = false;
            foreach ($row['options'] as $opt) {
                if (empty($opt['text'])) $hasEmpty = true;
                if ($opt['is_correct']) $hasCorrect = true;
            }
            if ($hasEmpty) {
                $this->notification()->error('Q' . ($i + 1) . ': All 4 options are required.');
                return;
            }
            if (!$hasCorrect) {
                $this->notification()->error('Q' . ($i + 1) . ': Select the correct answer.');
                return;
            }
        }

        $org = Auth::user()->organization_id;

        // Get chapter info for standard/section
        if ($this->addTargetType === 'chapter') {
            $chapter = Chapter::find($this->addTargetId);
            $chapterId = $chapter?->id;
            $topicId   = null;
        } else {
            $topic   = Topic::with('chapter')->find($this->addTargetId);
            $chapter = $topic?->chapter;
            $chapterId = $chapter?->id;
            $topicId   = $topic?->id;
        }

        if (!$chapter) {
            $this->notification()->error('Chapter not found.');
            return;
        }

        try {
            DB::beginTransaction();

            foreach ($this->mcqRows as $row) {
                $question = McqQuestion::create([
                    'organization_id' => $org,
                    'standard_id'     => $chapter->standard_id,
                    'section_id'      => $chapter->section_id,
                    'chapter_id'      => $chapterId,
                    'topic_id'        => $topicId,
                    'created_by'      => Auth::id(),
                    'question_text'   => $row['question_text'],
                    'time_limit'      => $row['time_limit'] ?? 30,
                    'is_active'       => true,
                ]);

                foreach ($row['options'] as $opt) {
                    McqOption::create([
                        'organization_id'  => $org,
                        'mcq_question_id'  => $question->id,
                        'option_text'      => $opt['text'],
                        'is_correct'       => $opt['is_correct'] ? true : false,
                    ]);
                }
            }

            DB::commit();
            $this->notification()->success('Created ' . count($this->mcqRows) . ' MCQ(s)!');
            $this->closeAddModal();
            $this->loadStats();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('MCQ save error: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  EDIT MCQ
    // ═══════════════════════════════════════════════════════════════════════

    public function onEditMcq(string $type, int $id): void
    {
        $this->resetEditForm();
        $this->editTargetType = $type;
        $this->editTargetId   = $id;

        $org = Auth::user()->organization_id;

        if ($type === 'chapter') {
            $this->editTargetName = Chapter::find($id)?->name ?? '';
            $questions = McqQuestion::with('options')
                ->where('chapter_id', $id)->whereNull('topic_id')
                ->where('organization_id', $org)->get();
        } else {
            $this->editTargetName = Topic::find($id)?->topic_name ?? '';
            $questions = McqQuestion::with('options')
                ->where('topic_id', $id)
                ->where('organization_id', $org)->get();
        }

        $this->editMcqs = $questions->map(fn($q) => [
            'id'            => $q->id,
            'question_text' => $q->question_text,
            'time_limit'    => $q->time_limit ?? 30,
            'options'       => $q->options->map(fn($o) => [
                'id'         => $o->id,
                'text'       => $o->option_text,
                'is_correct' => (bool) $o->is_correct,
            ])->toArray(),
        ])->toArray();

        $this->openEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->openEditModal = false;
        $this->resetEditForm();
    }

    private function resetEditForm(): void
    {
        $this->reset(['editTargetType', 'editTargetId', 'editTargetName', 'editMcqs']);
    }

    public function setEditCorrectOption(int $qIndex, int $optIndex): void
    {
        foreach ($this->editMcqs[$qIndex]['options'] as $i => &$opt) {
            $opt['is_correct'] = ($i === $optIndex);
        }
    }

    public function onUpdateMcqs(): void
    {
        foreach ($this->editMcqs as $i => $mcq) {
            if (empty($mcq['question_text'])) {
                $this->notification()->error('Q' . ($i + 1) . ': Question text is required.');
                return;
            }
            $hasCorrect = false;
            foreach ($mcq['options'] as $opt) {
                if (empty($opt['text'])) {
                    $this->notification()->error('Q' . ($i + 1) . ': All options are required.');
                    return;
                }
                if ($opt['is_correct']) $hasCorrect = true;
            }
            if (!$hasCorrect) {
                $this->notification()->error('Q' . ($i + 1) . ': Select the correct answer.');
                return;
            }
        }

        try {
            DB::beginTransaction();

            foreach ($this->editMcqs as $mcq) {
                McqQuestion::where('id', $mcq['id'])->update([
                    'question_text' => $mcq['question_text'],
                    'time_limit'    => $mcq['time_limit'] ?? 30,
                ]);

                foreach ($mcq['options'] as $opt) {
                    McqOption::where('id', $opt['id'])->update([
                        'option_text' => $opt['text'],
                        'is_correct'  => $opt['is_correct'] ? true : false,
                    ]);
                }
            }

            DB::commit();
            $this->notification()->success('MCQs updated!');
            $this->closeEditModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  VIEW MCQ (swipeable)
    // ═══════════════════════════════════════════════════════════════════════

    public function onViewMcq(string $type, int $id): void
    {
        $org = Auth::user()->organization_id;

        if ($type === 'chapter') {
            $this->viewTargetName = Chapter::find($id)?->name ?? '';
            $questions = McqQuestion::with('options')
                ->where('chapter_id', $id)->whereNull('topic_id')
                ->where('organization_id', $org)->orderBy('id')->get();
        } else {
            $this->viewTargetName = Topic::find($id)?->topic_name ?? '';
            $questions = McqQuestion::with('options')
                ->where('topic_id', $id)
                ->where('organization_id', $org)->orderBy('id')->get();
        }

        if ($questions->isEmpty()) {
            $this->notification()->info('No MCQs found.');
            return;
        }

        $this->viewMcqs = $questions->map(fn($q) => [
            'id'            => $q->id,
            'question_text' => $q->question_text,
            'time_limit'    => $q->time_limit,
            'options'       => $q->options->map(fn($o) => [
                'text'       => $o->option_text,
                'is_correct' => (bool) $o->is_correct,
            ])->toArray(),
        ])->toArray();

        $this->openViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->openViewModal  = false;
        $this->viewMcqs       = [];
        $this->viewTargetName = '';
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DELETE MCQ (select from list -> confirm)
    // ═══════════════════════════════════════════════════════════════════════

    public function onDeleteMcq(string $type, int $id): void
    {
        $org = Auth::user()->organization_id;

        $this->deleteTargetType = $type;
        $this->deleteTargetId   = $id;
        $this->selectedDeleteIds = [];

        if ($type === 'chapter') {
            $this->deleteTargetName = Chapter::find($id)?->name ?? '';
            $questions = McqQuestion::where('chapter_id', $id)->whereNull('topic_id')
                ->where('organization_id', $org)->get();
        } else {
            $this->deleteTargetName = Topic::find($id)?->topic_name ?? '';
            $questions = McqQuestion::where('topic_id', $id)
                ->where('organization_id', $org)->get();
        }

        $this->deleteMcqList = $questions->map(fn($q) => [
            'id'            => $q->id,
            'question_text' => $q->question_text,
        ])->toArray();

        $this->openDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->openDeleteModal  = false;
        $this->deleteMcqList    = [];
        $this->selectedDeleteIds = [];
        $this->showDeleteConfirm = false;
    }

    public function toggleDeleteSelect(int $id): void
    {
        if (in_array($id, $this->selectedDeleteIds)) {
            $this->selectedDeleteIds = array_values(array_diff($this->selectedDeleteIds, [$id]));
        } else {
            $this->selectedDeleteIds[] = $id;
        }
    }

    public function selectAllForDelete(): void
    {
        $this->selectedDeleteIds = collect($this->deleteMcqList)->pluck('id')->toArray();
    }

    public function askDeleteConfirm(): void
    {
        if (empty($this->selectedDeleteIds)) {
            $this->notification()->error('Select at least one MCQ to delete.');
            return;
        }
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
    }

    public function confirmDelete(): void
    {
        if (empty($this->selectedDeleteIds)) {
            $this->cancelDelete();
            return;
        }

        try {
            DB::beginTransaction();
            McqOption::whereIn('mcq_question_id', $this->selectedDeleteIds)->delete();
            McqUserAnswer::whereIn('mcq_question_id', $this->selectedDeleteIds)->delete();
            McqQuestion::whereIn('id', $this->selectedDeleteIds)->delete();
            DB::commit();

            $this->notification()->success('Deleted ' . count($this->selectedDeleteIds) . ' MCQ(s)!');
            $this->closeDeleteModal();
            $this->loadStats();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $org      = Auth::user()->organization_id;
        $showList = $this->filterStandard && $this->filterSubject;
        $chapters = collect();
        $chapterMcqCounts = [];
        $topicMcqCounts   = [];

        if ($showList) {
            $chapters = Chapter::with(['topics' => fn($q) => $q->orderBy('id')])
                ->withCount(['topics'])
                ->where('organization_id', $org)
                ->where('standard_id', $this->filterStandard)
                ->when($this->filterSection, fn($q) => $q->where('section_id', $this->filterSection))
                ->where('subject_id', $this->filterSubject)
                ->orderBy('order')
                ->get();

            $chapterIds = $chapters->pluck('id')->toArray();
            $topicIds   = $chapters->flatMap(fn($ch) => $ch->topics->pluck('id'))->toArray();

            $chapterMcqCounts = McqQuestion::where('organization_id', $org)
                ->whereIn('chapter_id', $chapterIds)
                ->whereNull('topic_id')
                ->selectRaw('chapter_id, count(*) as mcq_count')
                ->groupBy('chapter_id')
                ->pluck('mcq_count', 'chapter_id')
                ->toArray();

            $topicMcqCounts = McqQuestion::where('organization_id', $org)
                ->whereIn('topic_id', $topicIds)
                ->selectRaw('topic_id, count(*) as mcq_count')
                ->groupBy('topic_id')
                ->pluck('mcq_count', 'topic_id')
                ->toArray();
        }

        return view('livewire.admin.quiz', compact('chapters', 'showList', 'chapterMcqCounts', 'topicMcqCounts'));
    }
}
