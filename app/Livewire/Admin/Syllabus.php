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

    // ─── Chapter Modal (add + edit unified) ──────────────────────────────
    public $openChapterModal   = false;
    public $chapterStandardId  = '';
    public $chapterSectionId   = '';
    public $chapterSubjectId   = '';
    public $chapterSections    = [];
    public $chapterSubjects    = [];
    public $chapterRows        = []; // [{id, name, order}]  id=null → new
    public array $deletedChapterIds = [];

    // ─── Topic Modal (add + edit unified) ────────────────────────────────
    public $openTopicModal    = false;
    public $topicStandardId   = '';
    public $topicSectionId    = '';
    public $topicSubjectId    = '';
    public $topicChapterId    = '';
    public $topicSections     = [];
    public $topicSubjects     = [];
    public $topicChapters     = [];
    public $topicRows         = []; // [{id, name, order}]  id=null → new
    public array $deletedTopicIds = [];

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

    // ─── Expanded state (Alpine) — which subject cards are opened via View ─
    public array $expandedSubjects = [];

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

    /** Header "Add Chapter" — blank form; existing chapters appear once a subject is picked. */
    public function onAddChapter(): void
    {
        $this->resetChapterForm();
        $this->openChapterModal = true;
    }

    /**
     * Subject-card "Edit"/"Delete" — open the chapter manager preloaded for that subject,
     * with its existing chapters as editable rows (edit inline, add more, or remove).
     */
    public function onManageChapters($subjectId): void
    {
        $this->resetChapterForm();
        $this->chapterStandardId = (string) $this->filterStandard;
        $this->chapterSections   = $this->chapterStandardId
            ? Section::where('standard_id', $this->chapterStandardId)->where('is_active', true)->get() : [];
        $this->chapterSectionId  = (string) $this->filterSection;
        $this->loadChapterSubjects();
        $this->chapterSubjectId  = (string) $subjectId;
        $this->loadChapterRowsFromExisting();
        $this->openChapterModal  = true;
    }

    public function closeChapterModal(): void
    {
        $this->openChapterModal = false;
        $this->resetChapterForm();
    }

    private function resetChapterForm(): void
    {
        $this->reset(['chapterStandardId', 'chapterSectionId', 'chapterSubjectId', 'chapterRows', 'deletedChapterIds']);
        $this->chapterSections = [];
        $this->chapterSubjects = [];
    }

    public function updatedChapterStandardId(): void
    {
        $this->chapterSectionId   = '';
        $this->chapterSubjectId   = '';
        $this->chapterRows        = [];
        $this->deletedChapterIds  = [];
        $this->chapterSections    = $this->chapterStandardId
            ? Section::where('standard_id', $this->chapterStandardId)->where('is_active', true)->get() : [];
        $this->loadChapterSubjects();
    }

    public function updatedChapterSectionId(): void
    {
        $this->chapterSubjectId  = '';
        $this->chapterRows       = [];
        $this->deletedChapterIds = [];
        $this->loadChapterSubjects();
    }

    public function updatedChapterSubjectId(): void
    {
        $this->loadChapterRowsFromExisting();
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

    /** Load the subject's existing chapters as editable rows (empty when none). */
    private function loadChapterRowsFromExisting(): void
    {
        $this->chapterRows       = [];
        $this->deletedChapterIds = [];
        if (!$this->chapterSubjectId) return;

        $org = Auth::user()->organization_id;
        Chapter::where('organization_id', $org)
            ->where('subject_id', $this->chapterSubjectId)
            ->orderBy('order')
            ->get()
            ->each(function ($ch) {
                $this->chapterRows[] = [
                    'id'    => $ch->id,
                    'name'  => $ch->name,
                    'order' => $ch->order,
                ];
            });
    }

    public function addChapterRow(): void
    {
        $maxOrder = collect($this->chapterRows)->max('order') ?? 0;
        $this->chapterRows[] = ['id' => null, 'name' => '', 'order' => $maxOrder + 1];
    }

    public function removeChapterRow($index): void
    {
        $row = $this->chapterRows[$index] ?? null;
        if ($row && !empty($row['id'])) {
            $this->deletedChapterIds[] = (int) $row['id'];
        }
        unset($this->chapterRows[$index]);
        $this->chapterRows = array_values($this->chapterRows);
    }

    public function onSaveChapters(): void
    {
        if (!$this->chapterStandardId || !$this->chapterSubjectId) {
            $this->notification()->error('Please select class and subject.');
            return;
        }
        if (empty($this->chapterRows) && empty($this->deletedChapterIds)) {
            $this->notification()->error('Please add at least one chapter.');
            return;
        }

        foreach ($this->chapterRows as $i => $row) {
            if (empty(trim($row['name'] ?? ''))) {
                $this->notification()->error('Chapter ' . ($i + 1) . ': Name is required.');
                return;
            }
        }

        $org = Auth::user()->organization_id;

        try {
            DB::beginTransaction();

            // Removed existing chapters (staged via the row × buttons) — drop their topics too.
            if (!empty($this->deletedChapterIds)) {
                $ids = array_unique($this->deletedChapterIds);
                Topic::whereIn('chapter_id', $ids)->where('organization_id', $org)->delete();
                Chapter::whereIn('id', $ids)->where('organization_id', $org)->delete();
            }

            $created = 0;
            $updated = 0;
            foreach ($this->chapterRows as $row) {
                if (!empty($row['id'])) {
                    Chapter::where('id', $row['id'])->where('organization_id', $org)->update([
                        'name'  => trim($row['name']),
                        'order' => (int) ($row['order'] ?? 1),
                    ]);
                    $updated++;
                } else {
                    Chapter::create([
                        'organization_id' => $org,
                        'standard_id'     => $this->chapterStandardId,
                        'section_id'      => $this->chapterSectionId ?: null,
                        'subject_id'      => $this->chapterSubjectId,
                        'user_id'         => Auth::id(),
                        'name'            => trim($row['name']),
                        'order'           => (int) ($row['order'] ?? 1),
                        'is_published'    => true,
                    ]);
                    $created++;
                }
            }
            DB::commit();

            $deleted = count(array_unique($this->deletedChapterIds));
            $this->notification()->success(
                'Chapters saved!',
                trim(($created ? "{$created} added. " : '') . ($updated ? "{$updated} updated. " : '') . ($deleted ? "{$deleted} deleted." : '')) ?: 'No changes.'
            );
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
        $this->reset(['topicStandardId', 'topicSectionId', 'topicSubjectId', 'topicChapterId', 'topicRows', 'deletedTopicIds']);
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

    /** When a chapter is chosen, preload its existing topics as editable rows. */
    public function updatedTopicChapterId(): void
    {
        $this->loadTopicRowsFromExisting();
        if (empty($this->topicRows)) {
            $this->addTopicRow();
        }
    }

    private function loadTopicRowsFromExisting(): void
    {
        $this->topicRows       = [];
        $this->deletedTopicIds = [];
        if (!$this->topicChapterId) return;

        $org = Auth::user()->organization_id;
        Topic::where('organization_id', $org)
            ->where('chapter_id', $this->topicChapterId)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->each(function ($t) {
                $this->topicRows[] = [
                    'id'    => $t->id,
                    'name'  => $t->topic_name,
                    'order' => $t->order ?: (count($this->topicRows) + 1),
                ];
            });
    }

    public function addTopicRow(): void
    {
        $maxOrder = collect($this->topicRows)->max('order') ?? 0;
        $this->topicRows[] = ['id' => null, 'name' => '', 'order' => $maxOrder + 1];
    }

    public function removeTopicRow($index): void
    {
        $row = $this->topicRows[$index] ?? null;
        if ($row && !empty($row['id'])) {
            $this->deletedTopicIds[] = (int) $row['id'];
        }
        unset($this->topicRows[$index]);
        $this->topicRows = array_values($this->topicRows);
    }

    public function onSaveTopics(): void
    {
        if (!$this->topicStandardId || !$this->topicSubjectId || !$this->topicChapterId) {
            $this->notification()->error('Please select class, subject and chapter.');
            return;
        }
        if (empty($this->topicRows) && empty($this->deletedTopicIds)) {
            $this->notification()->error('Please add at least one topic.');
            return;
        }

        foreach ($this->topicRows as $i => $row) {
            if (empty(trim($row['name'] ?? ''))) {
                $this->notification()->error('Topic ' . ($i + 1) . ': Name is required.');
                return;
            }
        }

        $org = Auth::user()->organization_id;

        try {
            DB::beginTransaction();

            if (!empty($this->deletedTopicIds)) {
                Topic::whereIn('id', array_unique($this->deletedTopicIds))
                    ->where('organization_id', $org)->delete();
            }

            $created = 0;
            $updated = 0;
            foreach ($this->topicRows as $row) {
                if (!empty($row['id'])) {
                    Topic::where('id', $row['id'])->where('organization_id', $org)->update([
                        'topic_name' => trim($row['name']),
                        'order'      => (int) ($row['order'] ?? 1),
                    ]);
                    $updated++;
                } else {
                    Topic::create([
                        'organization_id' => $org,
                        'chapter_id'      => $this->topicChapterId,
                        'topic_name'      => trim($row['name']),
                        'order'           => (int) ($row['order'] ?? 1),
                    ]);
                    $created++;
                }
            }
            DB::commit();

            $deleted = count(array_unique($this->deletedTopicIds));
            $this->notification()->success(
                'Topics saved!',
                trim(($created ? "{$created} added. " : '') . ($updated ? "{$updated} updated. " : '') . ($deleted ? "{$deleted} deleted." : '')) ?: 'No changes.'
            );
            $this->closeTopicModal();
            $this->loadStats();
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Topic save error: ' . $e->getMessage());
        }
    }

    // ─── View expand (subject cards) ─────────────────────────────────────
    public function toggleSubject(int $id): void
    {
        $this->expandedSubjects = in_array($id, $this->expandedSubjects)
            ? array_values(array_diff($this->expandedSubjects, [$id]))
            : array_merge($this->expandedSubjects, [$id]);
    }

    /** Subject-card "View" — ensure the card is expanded to show all chapters. */
    public function onViewSubject(int $id): void
    {
        if (!in_array($id, $this->expandedSubjects)) {
            $this->expandedSubjects[] = $id;
        }
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
