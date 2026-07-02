<?php

namespace App\Livewire\Admin;

use App\Models\Student\Chapter;
use App\Models\Student\Topic;
use App\Models\Student\Subject;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Syllabus extends Component
{
    use WireUiActions, WithPagination;

    // ─── Tabs ────────────────────────────────────────────────────────────
    public string $activeTab = 'view';

    // ─── Chapter Modal ───────────────────────────────────────────────────
    public $openChapterModal   = false;
    public $chapterStandardId  = '';
    public $chapterSectionId   = '';
    public $chapterSubjectId   = '';
    public $chapterSections    = [];
    public $chapterSubjects    = [];
    public $chapterRows        = []; // [{name, description, order}]
    public $editChapterId      = null;

    // ─── Topic Modal ─────────────────────────────────────────────────────
    public $openTopicModal    = false;
    public $topicStandardId   = '';
    public $topicSectionId    = '';
    public $topicSubjectId    = '';
    public $topicChapterId    = '';
    public $topicSections     = [];
    public $topicSubjects     = [];
    public $topicChapters     = [];
    public $topicRows         = []; // [{name, order}]
    public $editTopicId       = null;

    // ─── Edit single chapter/topic ───────────────────────────────────────
    public $editChapterModal  = false;
    public $editChapterName   = '';
    public $editChapterDesc   = '';
    public $editChapterOrder  = 1;

    public $editTopicModal    = false;
    public $editTopicName     = '';
    public $editTopicOrder    = 1;

    // ─── Filters ─────────────────────────────────────────────────────────
    public string $search         = '';
    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $filterSubject  = '';
    public $filterSections        = [];
    public $filterSubjectsList    = [];
    public int    $perPage        = 15;

    // ─── Lookup data ─────────────────────────────────────────────────────
    public $standards = [];

    // ─── Stats ───────────────────────────────────────────────────────────
    public $totalStandards = 0;
    public $totalSubjects  = 0;
    public $totalChapters  = 0;
    public $totalTopics    = 0;

    // ─── Expanded state (Alpine) ─────────────────────────────────────────
    public array $expandedSubjects = [];
    public array $expandedChapters = [];

    // ─── Delete confirm overlay (replaces broken WireUI dialog) ───────────
    public bool   $showDeleteConfirm = false;
    public string $deleteTargetType  = ''; // 'chapter' | 'topic'
    public ?int   $deleteTargetId    = null;

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterStandard' => ['except' => ''],
        'filterSection'  => ['except' => ''],
        'filterSubject'  => ['except' => ''],
        'activeTab'      => ['except' => 'view'],
    ];

    public function mount(): void
    {
        $org = Auth::user()->organization_id;
        $this->standards = Standard::where('organization_id', $org)->where('is_active', true)->orderBy('id')->get();
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $org = Auth::user()->organization_id;
        $this->totalStandards = $this->standards->count();
        $this->totalSubjects  = Subject::where('organization_id', $org)->where('is_active', true)->count();
        $this->totalChapters  = Chapter::where('organization_id', $org)->count();
        $this->totalTopics    = Topic::where('organization_id', $org)->count();
    }

    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ─── Filter handlers ─────────────────────────────────────────────────
    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterSubject(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStandard(): void
    {
        $this->resetPage();
        $this->filterSection      = '';
        $this->filterSubject      = '';
        $this->filterSections     = $this->filterStandard
            ? Section::where('standard_id', $this->filterStandard)->where('is_active', true)->get() : [];
        $this->loadFilterSubjects();
    }

    public function updatedFilterSection(): void
    {
        $this->resetPage();
        $this->filterSubject = '';
        $this->loadFilterSubjects();
    }

    private function loadFilterSubjects(): void
    {
        if (!$this->filterStandard) {
            $this->filterSubjectsList = [];
            return;
        }
        $org = Auth::user()->organization_id;
        $query = Subject::where('organization_id', $org)->where('is_active', true);

        if ($this->filterSection) {
            $query->whereHas('sections', fn($q) => $q->where('sections.id', $this->filterSection));
        } else {
            $query->whereHas('standards', fn($q) => $q->where('standards.id', $this->filterStandard));
        }

        $this->filterSubjectsList = $query->orderBy('id')->get();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStandard', 'filterSection', 'filterSubject']);
        $this->filterSections    = [];
        $this->filterSubjectsList = [];
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  CHAPTER MODAL
    // ═══════════════════════════════════════════════════════════════════════

    public function onAddChapter(): void
    {
        $this->resetChapterForm();
        $this->openChapterModal = true;
    }

    public function closeChapterModal(): void
    {
        $this->openChapterModal = false;
        $this->resetChapterForm();
    }

    private function resetChapterForm(): void
    {
        $this->reset(['chapterStandardId', 'chapterSectionId', 'chapterSubjectId', 'chapterRows']);
        $this->chapterSections = [];
        $this->chapterSubjects = [];
    }

    public function updatedChapterStandardId(): void
    {
        $this->chapterSectionId = '';
        $this->chapterSubjectId = '';
        $this->chapterSections  = $this->chapterStandardId
            ? Section::where('standard_id', $this->chapterStandardId)->where('is_active', true)->get() : [];
        $this->loadChapterSubjects();
    }

    public function updatedChapterSectionId(): void
    {
        $this->chapterSubjectId = '';
        $this->loadChapterSubjects();
    }

    private function loadChapterSubjects(): void
    {
        if (!$this->chapterStandardId) {
            $this->chapterSubjects = [];
            return;
        }
        $org   = Auth::user()->organization_id;
        $query = Subject::where('organization_id', $org)->where('is_active', true);

        if ($this->chapterSectionId) {
            $query->whereHas('sections', fn($q) => $q->where('sections.id', $this->chapterSectionId));
        } else {
            $query->whereHas('standards', fn($q) => $q->where('standards.id', $this->chapterStandardId));
        }
        $this->chapterSubjects = $query->orderBy('id')->get();
    }

    public function addChapterRow(): void
    {
        $nextOrder = count($this->chapterRows) + 1;
        // Auto-detect next order from existing chapters
        if ($this->chapterSubjectId) {
            $maxOrder = Chapter::where('subject_id', $this->chapterSubjectId)
                ->where('organization_id', Auth::user()->organization_id)
                ->max('order') ?? 0;
            $nextOrder = $maxOrder + count($this->chapterRows) + 1;
        }
        $this->chapterRows[] = ['name' => '', 'description' => '', 'order' => $nextOrder];
    }

    public function removeChapterRow($index): void
    {
        unset($this->chapterRows[$index]);
        $this->chapterRows = array_values($this->chapterRows);
    }

    public function onSaveChapters(): void
    {
        if (!$this->chapterStandardId || !$this->chapterSubjectId) {
            $this->notification()->error('Please select class and subject.');
            return;
        }
        if (empty($this->chapterRows)) {
            $this->notification()->error('Please add at least one chapter.');
            return;
        }

        foreach ($this->chapterRows as $i => $row) {
            if (empty($row['name'])) {
                $this->notification()->error('Chapter ' . ($i + 1) . ': Name is required.');
                return;
            }
        }

        $org = Auth::user()->organization_id;

        try {
            DB::beginTransaction();
            foreach ($this->chapterRows as $row) {
                Chapter::create([
                    'organization_id' => $org,
                    'standard_id'     => $this->chapterStandardId,
                    'section_id'      => $this->chapterSectionId ?: null,
                    'subject_id'      => $this->chapterSubjectId,
                    'user_id'         => Auth::id(),
                    'name'            => $row['name'],
                    'description'     => $row['description'] ?? null,
                    'order'           => $row['order'] ?? 1,
                    'is_published'    => true,
                ]);
            }
            DB::commit();

            $count = count($this->chapterRows);
            $this->notification()->success("Created {$count} chapter(s) successfully!");
            $this->closeChapterModal();
            $this->loadStats();
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Chapter save error: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  TOPIC MODAL
    // ═══════════════════════════════════════════════════════════════════════

    public function onAddTopic(): void
    {
        $this->resetTopicForm();
        $this->openTopicModal = true;
    }

    public function closeTopicModal(): void
    {
        $this->openTopicModal = false;
        $this->resetTopicForm();
    }

    private function resetTopicForm(): void
    {
        $this->reset(['topicStandardId', 'topicSectionId', 'topicSubjectId', 'topicChapterId', 'topicRows']);
        $this->topicSections = [];
        $this->topicSubjects = [];
        $this->topicChapters = [];
    }

    public function updatedTopicStandardId(): void
    {
        $this->topicSectionId = '';
        $this->topicSubjectId = '';
        $this->topicChapterId = '';
        $this->topicSections  = $this->topicStandardId
            ? Section::where('standard_id', $this->topicStandardId)->where('is_active', true)->get() : [];
        $this->loadTopicSubjects();
        $this->topicChapters = [];
    }

    public function updatedTopicSectionId(): void
    {
        $this->topicSubjectId = '';
        $this->topicChapterId = '';
        $this->loadTopicSubjects();
        $this->topicChapters = [];
    }

    private function loadTopicSubjects(): void
    {
        if (!$this->topicStandardId) {
            $this->topicSubjects = [];
            return;
        }
        $org   = Auth::user()->organization_id;
        $query = Subject::where('organization_id', $org)->where('is_active', true);

        if ($this->topicSectionId) {
            $query->whereHas('sections', fn($q) => $q->where('sections.id', $this->topicSectionId));
        } else {
            $query->whereHas('standards', fn($q) => $q->where('standards.id', $this->topicStandardId));
        }
        $this->topicSubjects = $query->orderBy('id')->get();
    }

    public function updatedTopicSubjectId(): void
    {
        $this->topicChapterId = '';
        $this->topicChapters  = $this->topicSubjectId
            ? Chapter::where('subject_id', $this->topicSubjectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->orderBy('order')->get()
            : [];
    }

    public function updatedTopicChapterId(): void
    {
        $this->topicRows = [];
        if ($this->topicChapterId) {
            $this->addTopicRow();
        }
    }

    public function addTopicRow(): void
    {
        $maxOrder = Topic::where('chapter_id', $this->topicChapterId)
            ->where('organization_id', Auth::user()->organization_id)
            ->count();
        $nextOrder = $maxOrder + count($this->topicRows) + 1;
        $this->topicRows[] = ['name' => '', 'order' => $nextOrder];
    }

    public function removeTopicRow($index): void
    {
        unset($this->topicRows[$index]);
        $this->topicRows = array_values($this->topicRows);
    }

    public function onSaveTopics(): void
    {
        if (!$this->topicStandardId || !$this->topicSubjectId || !$this->topicChapterId) {
            $this->notification()->error('Please select class, subject and chapter.');
            return;
        }
        if (empty($this->topicRows)) {
            $this->notification()->error('Please add at least one topic.');
            return;
        }

        foreach ($this->topicRows as $i => $row) {
            if (empty($row['name'])) {
                $this->notification()->error('Topic ' . ($i + 1) . ': Name is required.');
                return;
            }
        }

        $org = Auth::user()->organization_id;

        try {
            DB::beginTransaction();
            foreach ($this->topicRows as $row) {
                Topic::create([
                    'organization_id' => $org,
                    'chapter_id'      => $this->topicChapterId,
                    'topic_name'      => $row['name'],
                ]);
            }
            DB::commit();

            $count = count($this->topicRows);
            $this->notification()->success("Created {$count} topic(s) successfully!");
            $this->closeTopicModal();
            $this->loadStats();
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Topic save error: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  INLINE EDIT CHAPTER
    // ═══════════════════════════════════════════════════════════════════════

    public function onEditChapter($id): void
    {
        $chapter = Chapter::find($id);
        if (!$chapter) return;

        $this->editChapterId    = $chapter->id;
        $this->editChapterName  = $chapter->name;
        $this->editChapterDesc  = $chapter->description ?? '';
        $this->editChapterOrder = $chapter->order;
        $this->editChapterModal = true;
    }

    public function closeEditChapterModal(): void
    {
        $this->editChapterModal = false;
        $this->reset(['editChapterId', 'editChapterName', 'editChapterDesc', 'editChapterOrder']);
    }

    public function onUpdateChapter(): void
    {
        if (!$this->editChapterId || !$this->editChapterName) {
            $this->notification()->error('Chapter name is required.');
            return;
        }

        try {
            Chapter::where('id', $this->editChapterId)->update([
                'name'        => $this->editChapterName,
                'description' => $this->editChapterDesc ?: null,
                'order'       => $this->editChapterOrder,
            ]);
            $this->notification()->success('Chapter updated!');
            $this->closeEditChapterModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  INLINE EDIT TOPIC
    // ═══════════════════════════════════════════════════════════════════════

    public function onEditTopic($id): void
    {
        $topic = Topic::find($id);
        if (!$topic) return;

        $this->editTopicId    = $topic->id;
        $this->editTopicName  = $topic->topic_name;
        $this->editTopicModal = true;
    }

    public function closeEditTopicModal(): void
    {
        $this->editTopicModal = false;
        $this->reset(['editTopicId', 'editTopicName']);
    }

    public function onUpdateTopic(): void
    {
        if (!$this->editTopicId || !$this->editTopicName) {
            $this->notification()->error('Topic name is required.');
            return;
        }

        try {
            Topic::where('id', $this->editTopicId)->update([
                'topic_name' => $this->editTopicName,
            ]);
            $this->notification()->success('Topic updated!');
            $this->closeEditTopicModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DELETE
    // ═══════════════════════════════════════════════════════════════════════

    public function deleteChapter($id): void
    {
        $this->deleteTargetType  = 'chapter';
        $this->deleteTargetId    = (int) $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteTopic($id): void
    {
        $this->deleteTargetType  = 'topic';
        $this->deleteTargetId    = (int) $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetType  = '';
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        if (!$this->deleteTargetId || !$this->deleteTargetType) {
            $this->cancelDelete();
            return;
        }

        try {
            if ($this->deleteTargetType === 'chapter') {
                DB::beginTransaction();
                $chapter = Chapter::with('topics')->findOrFail($this->deleteTargetId);
                $chapter->topics()->delete();
                $chapter->delete();
                DB::commit();
                $this->notification()->success('Chapter deleted!');
            } else {
                Topic::findOrFail($this->deleteTargetId)->delete();
                $this->notification()->success('Topic deleted!');
            }
            $this->loadStats();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
        }

        $this->cancelDelete();
    }

    // ─── Toggle expand ───────────────────────────────────────────────────
    public function toggleSubject(int $id): void
    {
        $this->expandedSubjects = in_array($id, $this->expandedSubjects)
            ? array_values(array_diff($this->expandedSubjects, [$id]))
            : array_merge($this->expandedSubjects, [$id]);
    }

    public function toggleChapter(int $id): void
    {
        $this->expandedChapters = in_array($id, $this->expandedChapters)
            ? array_values(array_diff($this->expandedChapters, [$id]))
            : array_merge($this->expandedChapters, [$id]);
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        // Syllabus view is gated: require class + subject (section optional).
        if (!$this->filterStandard || !$this->filterSubject) {
            $empty = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(), 0, $this->perPage, 1,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
            return view('livewire.admin.syllabus', ['subjects' => $empty]);
        }

        $org = Auth::user()->organization_id;

        $subjects = Subject::with([
            'chapters' => fn($q) => $q->where('organization_id', $org)->orderBy('order')->with([
                'topics' => fn($tq) => $tq->orderBy('id')
            ]),
            'standards:id,name',
        ])
            ->where('organization_id', $org)
            ->where('is_active', true)
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(
                    fn($q) => $q->where('name', 'like', $term)
                        ->orWhereHas(
                            'chapters',
                            fn($cq) => $cq->where('name', 'like', $term)
                                ->orWhereHas('topics', fn($tq) => $tq->where('topic_name', 'like', $term))
                        )
                );
            })
            ->whereHas('standards', fn($sq) => $sq->where('standards.id', $this->filterStandard))
            ->when($this->filterSection, fn($q) => $q->whereHas('sections', fn($sq) => $sq->where('sections.id', $this->filterSection)))
            ->where('id', $this->filterSubject)
            ->orderBy('id')
            ->paginate($this->perPage);

        return view('livewire.admin.syllabus', compact('subjects'));
    }
}
