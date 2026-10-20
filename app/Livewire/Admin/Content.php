<?php

namespace App\Livewire\Admin;

use App\Models\Student\Chapter;
use App\Models\Student\Topic;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Content extends Component
{
    use WithPagination, WithFileUploads, WireUiActions;

    // ─── Filters (must select to see list) ───────────────────────────────
    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $filterSubject  = '';
    public $filterSections  = [];
    public $filterSubjects  = [];
    public $standards       = [];

    // ─── Expanded state ──────────────────────────────────────────────────
    public array $expandedChapters = [];

    // ─── Content Modal ───────────────────────────────────────────────────
    // One panel for all of it: a new chapter (with its content) for the class
    // and subject picked, a new topic in a chapter, or an existing chapter's
    // or topic's name and content. contentTargetId is null for a new one.
    public $openContentModal = false;
    public $contentTargetType = ''; // 'chapter' or 'topic'
    public $contentTargetId   = null;
    public $contentTargetName = '';
    public $contentEditMode   = false;
    public $contentName       = ''; // the chapter's or topic's name, editable
    public $contentOrder      = ''; // a chapter's number
    public $contentParentId   = null; // a new topic's chapter

    // Content form fields
    public $contentType    = 'text'; // text, url, image, pdf
    public $contentText    = '';
    public $contentUrl     = '';
    public $contentImage   = null;
    public $contentPdf     = null;
    public $existingImage  = '';
    public $existingPdf    = '';

    // ─── View Content Modal ──────────────────────────────────────────────
    public $showViewModal     = false;
    public $viewContentData   = [];
    public $viewContentTitle  = '';
    /** Where this content sits: class, section, subject (and chapter, for a topic). */
    public array $viewContentMeta = [];

    // ─── Delete confirm overlay (replaces broken WireUI dialog) ──────────
    public bool   $showDeleteConfirm = false;
    public string $deleteTargetType  = ''; // 'chapter' | 'topic'
    public ?int   $deleteTargetId    = null;
    public string $deleteTargetName  = '';
    /** 'content' clears what is in it and keeps it; 'record' deletes the chapter (with its topics) or the topic. */
    public string $deleteMode        = 'content';
    public bool   $deleteTargetHasContent = false;
    public int    $deleteTargetTopics     = 0;

    // ─── Stats ───────────────────────────────────────────────────────────
    public $totalChapters = 0;
    public $totalTopics   = 0;
    public $withContent   = 0;

    public function mount(): void
    {
        $org = Auth::user()->organization_id;
        $this->standards = Standard::where('organization_id', $org)->where('is_active', true)->inClassOrder()->get();
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $org = Auth::user()->organization_id;
        $this->totalChapters = Chapter::where('organization_id', $org)->count();
        $this->totalTopics   = Topic::where('organization_id', $org)->count();
        $this->withContent   = Chapter::where('organization_id', $org)
            ->where(fn($q) => $q->whereNotNull('file_path')->orWhereNotNull('image_path')->orWhereNotNull('pdf_path')->orWhereNotNull('description'))
            ->count()
            + Topic::where('organization_id', $org)
            ->where(fn($q) => $q->whereNotNull('topic_content')->orWhereNotNull('image_path')->orWhereNotNull('pdf_path'))
            ->count();
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

        $query->taughtIn($this->filterStandard, $this->filterSection ?: null);
        $this->filterSubjects = $query->orderBy('id')->get();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterStandard', 'filterSection', 'filterSubject']);
        $this->filterSections = [];
        $this->filterSubjects = [];
        $this->resetPage();
    }

    // ─── Toggle expand ───────────────────────────────────────────────────
    public function toggleChapter(int $id): void
    {
        $this->expandedChapters = in_array($id, $this->expandedChapters)
            ? array_values(array_diff($this->expandedChapters, [$id]))
            : array_merge($this->expandedChapters, [$id]);
    }

    // ─── Check if chapter has content ────────────────────────────────────
    public function chapterHasContent($chapter): bool
    {
        // Check chapter's own content
        if (!empty($chapter->description) || !empty($chapter->file_path) || !empty($chapter->image_path) || !empty($chapter->pdf_path)) {
            return true;
        }
        return false;
    }

    public function topicHasContent($topic): bool
    {
        if (!empty($topic->topic_content) || !empty($topic->image_path) || !empty($topic->pdf_path)) {
            return true;
        }
        return false;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ADD / EDIT CONTENT
    // ═══════════════════════════════════════════════════════════════════════

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    /**
     * A section's chapters: its own, and the whole class's (saved with no
     * section — 0, as Syllabus saves them), which are that section's too.
     */
    private function inSection($query)
    {
        return $query->where(fn ($w) => $w->where('section_id', $this->filterSection)
            ->orWhere('section_id', 0)->orWhereNull('section_id'));
    }

    /** One of the school's chapters (never another school's). */
    private function findChapter(?int $id): ?Chapter
    {
        return $id ? Chapter::where('organization_id', $this->orgId())->find($id) : null;
    }

    /** One of the school's topics — through its chapter, which carries the school. */
    private function findTopic(?int $id): ?Topic
    {
        return $id
            ? Topic::whereHas('chapter', fn ($q) => $q->where('organization_id', $this->orgId()))->find($id)
            : null;
    }

    /** The header's Add Chapter: a new chapter, with its content, for the class and subject picked. */
    public function openNewChapter(): void
    {
        if (!$this->filterStandard || !$this->filterSubject) {
            $this->notification()->error('Pick a class and a subject first.');
            return;
        }

        $this->resetContentForm();
        $this->contentTargetType = 'chapter';
        $this->contentTargetId   = null;
        $this->contentTargetName = 'New chapter';
        $this->contentOrder      = (string) ((int) Chapter::where('organization_id', $this->orgId())
            ->where('standard_id', $this->filterStandard)
            ->when($this->filterSection, fn ($q) => $this->inSection($q))
            ->where('subject_id', $this->filterSubject)
            ->max('order') + 1);
        $this->openContentModal = true;
    }

    /** A chapter's Add Topic: a new topic in it, with its content. */
    public function openNewTopic(int $chapterId): void
    {
        $chapter = $this->findChapter($chapterId);
        if (!$chapter) {
            return;
        }

        $this->resetContentForm();
        $this->contentTargetType = 'topic';
        $this->contentTargetId   = null;
        $this->contentParentId   = $chapter->id;
        $this->contentTargetName = 'New topic in ' . $chapter->name;
        $this->openContentModal  = true;
    }

    public function onAddContent(string $type, int $id): void
    {
        $this->resetContentForm();
        $this->contentTargetType = $type;
        $this->contentTargetId   = $id;
        $this->contentEditMode   = false;

        if ($type === 'chapter') {
            $ch = $this->findChapter($id);
            if (!$ch) return;
            $this->contentTargetName = $ch->name;
            $this->contentName       = $ch->name;
            $this->contentOrder      = (string) $ch->order;
        } else {
            $tp = $this->findTopic($id);
            if (!$tp) return;
            $this->contentTargetName = $tp->topic_name;
            $this->contentName       = $tp->topic_name;
        }

        $this->openContentModal = true;
    }

    public function onEditContent(string $type, int $id): void
    {
        $this->resetContentForm();
        $this->contentTargetType = $type;
        $this->contentTargetId   = $id;
        $this->contentEditMode   = true;

        if ($type === 'chapter') {
            $ch = $this->findChapter($id);
            if (!$ch) return;
            $this->contentTargetName = $ch->name;
            $this->contentName       = $ch->name;
            $this->contentOrder      = (string) $ch->order;

            // Count how many content types exist
            $hasText  = !empty($ch->description);
            $hasUrl   = !empty($ch->file_path) && filter_var($ch->file_path, FILTER_VALIDATE_URL);
            $hasImage = !empty($ch->image_path);
            $hasPdf   = !empty($ch->pdf_path);
            $count    = (int)$hasText + (int)$hasUrl + (int)$hasImage + (int)$hasPdf;

            if ($count > 1) {
                $this->contentType = 'all';
            } elseif ($hasImage) {
                $this->contentType = 'image';
            } elseif ($hasPdf) {
                $this->contentType = 'pdf';
            } elseif ($hasUrl) {
                $this->contentType = 'url';
            } else {
                $this->contentType = 'text';
            }

            $this->contentText   = $ch->description ?? '';
            $this->contentUrl    = ($hasUrl) ? $ch->file_path : '';
            $this->existingImage = $ch->image_path ?? '';
            $this->existingPdf   = $ch->pdf_path ?? '';
        } else {
            $tp = $this->findTopic($id);
            if (!$tp) return;
            $this->contentTargetName = $tp->topic_name;
            $this->contentName       = $tp->topic_name;

            $hasText  = !empty($tp->topic_content);
            $hasImage = !empty($tp->image_path);
            $hasPdf   = !empty($tp->pdf_path);
            $count    = (int)$hasText + (int)$hasImage + (int)$hasPdf;

            if ($count > 1) {
                $this->contentType = 'all';
            } elseif ($hasImage) {
                $this->contentType = 'image';
            } elseif ($hasPdf) {
                $this->contentType = 'pdf';
            } else {
                $this->contentType = 'text';
            }

            $this->contentText   = $tp->topic_content ?? '';
            $this->existingImage = $tp->image_path ?? '';
            $this->existingPdf   = $tp->pdf_path ?? '';
        }

        $this->openContentModal = true;
    }

    public function closeContentModal(): void
    {
        $this->openContentModal = false;
        $this->resetContentForm();
    }

    private function resetContentForm(): void
    {
        $this->reset([
            'contentTargetType',
            'contentTargetId',
            'contentTargetName',
            'contentEditMode',
            'contentName',
            'contentOrder',
            'contentParentId',
            'contentType',
            'contentText',
            'contentUrl',
            'contentImage',
            'contentPdf',
            'existingImage',
            'existingPdf',
        ]);
    }

    public function onSaveContent(): void
    {
        if (!in_array($this->contentTargetType, ['chapter', 'topic'], true)) {
            $this->notification()->error('Invalid target.');
            return;
        }

        // Limits: text max 10,000 chars, image max 500 KB. Rules run regardless
        // of the selected content type so an oversized upload can never slip in.
        $this->validate([
            'contentName'  => 'required|string|max:255',
            'contentOrder' => $this->contentTargetType === 'chapter' ? 'required|integer|min:1|max:999' : 'nullable',
            'contentText'  => 'nullable|string|max:10000',
            'contentUrl'   => 'nullable|url|max:2000',
            'contentImage' => 'nullable|image|max:500', // max is in KB → 500 KB
            'contentPdf'   => 'nullable|file|mimes:pdf|max:5120',
        ], [
            'contentName.required' => $this->contentTargetType === 'chapter' ? 'Enter the chapter name.' : 'Enter the topic name.',
            'contentOrder.required' => 'Enter the chapter number.',
            'contentText.max'    => 'Text cannot exceed 10,000 characters.',
            'contentUrl.url'     => 'Please enter a valid URL.',
            'contentImage.image' => 'The file must be an image.',
            'contentImage.max'   => 'Image must be 500 KB or smaller.',
            'contentPdf.mimes'   => 'The file must be a PDF.',
            'contentPdf.max'     => 'PDF must be 5 MB or smaller.',
        ]);

        $org  = $this->orgId();
        $name = trim($this->contentName);
        // The subject's teachers hear what changed in its outline, as Syllabus tells them.
        $push = app(\App\Services\TeacherPushNotifier::class);

        try {
            if ($this->contentTargetType === 'chapter') {
                $record = $this->contentTargetId ? $this->findChapter((int) $this->contentTargetId) : null;
                if ($this->contentTargetId && !$record) {
                    throw new \RuntimeException('This chapter is no longer there.');
                }
                $subjectId = $record ? (int) $record->subject_id : (int) $this->filterSubject;
                $before    = $push->outlineSnapshot($org, $subjectId);

                if (!$record) {
                    if (!$this->filterStandard || !$this->filterSubject) {
                        throw new \RuntimeException('Pick a class and a subject first.');
                    }
                    $record = Chapter::create([
                        'organization_id' => $org,
                        'standard_id'     => $this->filterStandard,
                        // No section = the whole class: 0, the column's default (as Syllabus saves it).
                        'section_id'      => $this->filterSection ?: 0,
                        'subject_id'      => $this->filterSubject,
                        'user_id'         => Auth::id(),
                        'name'            => $name,
                        'order'           => (int) $this->contentOrder,
                        'is_published'    => true,
                    ]);
                }

                $data = ['name' => $name, 'order' => (int) $this->contentOrder] + $this->buildChapterContentData($record);
                $record->update($data);
                $push->outlineSaved($before);
            } else {
                $record = $this->contentTargetId ? $this->findTopic((int) $this->contentTargetId) : null;
                if ($this->contentTargetId && !$record) {
                    throw new \RuntimeException('This topic is no longer there.');
                }
                $chapter = $record ? $record->chapter : $this->findChapter((int) $this->contentParentId);
                if (!$chapter) {
                    throw new \RuntimeException('This chapter is no longer there.');
                }
                $before = $push->outlineSnapshotOfChapter((int) $chapter->id);

                if (!$record) {
                    $record = Topic::create([
                        'organization_id' => $org,
                        'chapter_id'      => $chapter->id,
                        'topic_name'      => $name,
                        'order'           => (int) Topic::where('chapter_id', $chapter->id)->max('order') + 1,
                    ]);
                    // A chapter given a new topic opens to show it.
                    if (!in_array($chapter->id, $this->expandedChapters, true)) {
                        $this->expandedChapters[] = $chapter->id;
                    }
                }

                $data = ['topic_name' => $name] + $this->buildTopicContentData($record);
                $record->update($data);
                $push->outlineSaved($before);
            }

            $this->notification()->success($this->contentTargetId ? 'Saved successfully!' : ucfirst($this->contentTargetType) . ' added!');
            $this->closeContentModal();
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Content save error: ' . $e->getMessage());
        }
    }

    private function buildChapterContentData($chapter): array
    {
        $data = [];

        if ($this->contentType === 'all') {
            // Save all fields that have data
            if ($this->contentText) $data['description'] = $this->contentText;
            if ($this->contentUrl)  $data['file_path']   = $this->contentUrl;

            if ($this->contentImage) {
                if ($chapter->image_path) $this->deleteS3File($chapter->image_path);
                $path = $this->contentImage->store('admin/content/chapter-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['image_path'] = Storage::disk('s3')->url($path);
            }

            if ($this->contentPdf) {
                if ($chapter->pdf_path) $this->deleteS3File($chapter->pdf_path);
                $path = $this->contentPdf->store('admin/content/chapter-pdfs', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['pdf_path'] = Storage::disk('s3')->url($path);
            }

            return $data;
        }

        switch ($this->contentType) {
            case 'text':
                $data['description'] = trim((string) $this->contentText) !== '' ? $this->contentText : null;
                $data['file_path']   = null;
                $data['image_path']  = $this->deleteFileIfNeeded($chapter->image_path);
                $data['pdf_path']    = $this->deleteFileIfNeeded($chapter->pdf_path);
                break;

            case 'url':
                $data['file_path']   = $this->contentUrl;
                $data['image_path']  = $this->deleteFileIfNeeded($chapter->image_path);
                $data['pdf_path']    = $this->deleteFileIfNeeded($chapter->pdf_path);
                break;

            case 'image':
                if ($this->contentImage) {
                    if ($chapter->image_path) $this->deleteS3File($chapter->image_path);
                    $path = $this->contentImage->store('admin/content/chapter-images', 's3');
                    Storage::disk('s3')->setVisibility($path, 'public');
                    $data['image_path'] = Storage::disk('s3')->url($path);
                }
                $data['file_path'] = null;
                $data['pdf_path']  = $this->deleteFileIfNeeded($chapter->pdf_path);
                break;

            case 'pdf':
                if ($this->contentPdf) {
                    if ($chapter->pdf_path) $this->deleteS3File($chapter->pdf_path);
                    $path = $this->contentPdf->store('admin/content/chapter-pdfs', 's3');
                    Storage::disk('s3')->setVisibility($path, 'public');
                    $data['pdf_path'] = Storage::disk('s3')->url($path);
                }
                $data['file_path']  = null;
                $data['image_path'] = $this->deleteFileIfNeeded($chapter->image_path);
                break;
        }

        return $data;
    }

    private function buildTopicContentData($topic): array
    {
        $data = [];

        if ($this->contentType === 'all') {
            // Save all fields that have data
            if ($this->contentText) $data['topic_content'] = $this->contentText;

            if ($this->contentImage) {
                if ($topic->image_path) $this->deleteS3File($topic->image_path);
                $path = $this->contentImage->store('admin/content/topic-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['image_path'] = Storage::disk('s3')->url($path);
            }

            if ($this->contentPdf) {
                if ($topic->pdf_path) $this->deleteS3File($topic->pdf_path);
                $path = $this->contentPdf->store('admin/content/topic-pdfs', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['pdf_path'] = Storage::disk('s3')->url($path);
            }

            // URL stored in topic_content if provided and no text
            if ($this->contentUrl && !$this->contentText) {
                $data['topic_content'] = $this->contentUrl;
            }

            return $data;
        }

        switch ($this->contentType) {
            case 'text':
                $data['topic_content'] = trim((string) $this->contentText) !== '' ? $this->contentText : null;
                $data['image_path']    = $this->deleteFileIfNeeded($topic->image_path);
                $data['pdf_path']      = $this->deleteFileIfNeeded($topic->pdf_path);
                break;

            case 'url':
                $data['topic_content'] = $this->contentUrl;
                $data['image_path']    = $this->deleteFileIfNeeded($topic->image_path);
                $data['pdf_path']      = $this->deleteFileIfNeeded($topic->pdf_path);
                break;

            case 'image':
                if ($this->contentImage) {
                    if ($topic->image_path) $this->deleteS3File($topic->image_path);
                    $path = $this->contentImage->store('admin/content/topic-images', 's3');
                    Storage::disk('s3')->setVisibility($path, 'public');
                    $data['image_path'] = Storage::disk('s3')->url($path);
                }
                $data['pdf_path'] = $this->deleteFileIfNeeded($topic->pdf_path);
                break;

            case 'pdf':
                if ($this->contentPdf) {
                    if ($topic->pdf_path) $this->deleteS3File($topic->pdf_path);
                    $path = $this->contentPdf->store('admin/content/topic-pdfs', 's3');
                    Storage::disk('s3')->setVisibility($path, 'public');
                    $data['pdf_path'] = Storage::disk('s3')->url($path);
                }
                $data['image_path'] = $this->deleteFileIfNeeded($topic->image_path);
                break;
        }

        return $data;
    }

    private function deleteFileIfNeeded(?string $filePath): ?string
    {
        if ($filePath) $this->deleteS3File($filePath);
        return null;
    }

    private function deleteS3File(string $fileUrl): void
    {
        try {
            $path = str_replace(Storage::disk('s3')->url(''), '', $fileUrl);
            Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            logger()->warning("Failed to delete S3 file: {$fileUrl}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  VIEW CONTENT
    // ═══════════════════════════════════════════════════════════════════════

    public function onViewContent(string $type, int $id): void
    {
        if ($type === 'chapter') {
            $ch = Chapter::with(['standard', 'section', 'subject'])->where('organization_id', $this->orgId())->find($id);
            if (!$ch) return;
            $this->viewContentTitle = $ch->name;
            $this->viewContentData  = [
                'text'  => $ch->description,
                'url'   => $ch->file_path,
                'image' => $ch->image_path,
                'pdf'   => $ch->pdf_path,
            ];
            $this->viewContentMeta = $this->placeOf($ch);
        } else {
            // A topic has no class of its own -- it inherits its chapter's.
            $tp = Topic::with(['chapter.standard', 'chapter.section', 'chapter.subject'])
                ->whereHas('chapter', fn ($q) => $q->where('organization_id', $this->orgId()))->find($id);
            if (!$tp) return;
            $this->viewContentTitle = $tp->topic_name;
            $this->viewContentData  = [
                'text'  => $tp->topic_content,
                'url'   => $tp->link ?: null,
                // A topic saved from the app holds a bare S3 path; its *_url is the link.
                'image' => $tp->image_path ? ($tp->image_url ?: $tp->image_path) : null,
                'pdf'   => $tp->pdf_path ? ($tp->pdf_url ?: $tp->pdf_path) : null,
            ];
            $this->viewContentMeta = $this->placeOf($tp->chapter)
                + ['Chapter' => $tp->chapter?->name ?? '—'];
        }
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal    = false;
        $this->viewContentData  = [];
        $this->viewContentTitle = '';
        $this->viewContentMeta  = [];
    }

    /** Class / Section / Subject a chapter belongs to, ready to print as chips. */
    private function placeOf($chapter): array
    {
        return [
            'Class'   => $chapter?->standard?->name ?? '—',
            'Section' => $chapter?->section?->name ?? '—',
            'Subject' => $chapter?->subject?->name ?? '—',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DELETE CONTENT
    // ═══════════════════════════════════════════════════════════════════════

    /** Remove what is in a chapter or topic; the chapter or topic stays. */
    public function deleteContent(string $type, int $id): void
    {
        $this->askDelete($type, $id, 'content');
    }

    /** Delete a chapter (with its topics) or a topic outright. */
    public function deleteRecord(string $type, int $id): void
    {
        $this->askDelete($type, $id, 'record');
    }

    private function askDelete(string $type, int $id, string $mode): void
    {
        $record = $type === 'chapter' ? $this->findChapter($id) : $this->findTopic($id);
        if (!$record) {
            return;
        }

        $this->deleteTargetType       = $type === 'chapter' ? 'chapter' : 'topic';
        $this->deleteTargetId         = (int) $id;
        $this->deleteTargetName       = $type === 'chapter' ? $record->name : $record->topic_name;
        $this->deleteTargetHasContent = $type === 'chapter' ? $this->chapterHasContent($record) : $this->topicHasContent($record);
        $this->deleteTargetTopics     = $type === 'chapter' ? $record->topics()->count() : 0;
        $this->deleteMode             = $mode;
        $this->showDeleteConfirm      = true;
    }

    /** The delete box's other button: keep the chapter or topic, clear only what is in it. */
    public function removeContentOnly(): void
    {
        $this->deleteMode = 'content';
        $this->confirmDelete();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetType  = '';
        $this->deleteTargetId    = null;
        $this->deleteTargetName  = '';
        $this->deleteMode        = 'content';
        $this->deleteTargetHasContent = false;
        $this->deleteTargetTopics     = 0;
    }

    public function confirmDelete(): void
    {
        if (!$this->deleteTargetId || !$this->deleteTargetType) {
            $this->cancelDelete();
            return;
        }

        try {
            if ($this->deleteMode === 'record') {
                $this->deleteRecordNow();
            } elseif ($this->deleteTargetType === 'chapter') {
                $ch = $this->findChapter($this->deleteTargetId) ?? throw new \RuntimeException('This chapter is no longer there.');
                if ($ch->image_path) $this->deleteS3File($ch->image_path);
                if ($ch->pdf_path)   $this->deleteS3File($ch->pdf_path);
                $ch->update(['description' => null, 'file_path' => null, 'image_path' => null, 'pdf_path' => null]);
                $this->notification()->success('Content removed!');
            } else {
                $tp = $this->findTopic($this->deleteTargetId) ?? throw new \RuntimeException('This topic is no longer there.');
                if ($tp->image_path) $this->deleteS3File($tp->image_path);
                if ($tp->pdf_path)   $this->deleteS3File($tp->pdf_path);
                $tp->update(['topic_content' => null, 'image_path' => null, 'pdf_path' => null]);
                $this->notification()->success('Content removed!');
            }
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }

        $this->cancelDelete();
    }

    /** The chapter with its topics, or the topic — their files too — as the teacher app deletes them. */
    private function deleteRecordNow(): void
    {
        $push = app(\App\Services\TeacherPushNotifier::class);

        if ($this->deleteTargetType === 'chapter') {
            $ch = $this->findChapter($this->deleteTargetId) ?? throw new \RuntimeException('This chapter is no longer there.');
            $before = $push->outlineSnapshot($this->orgId(), (int) $ch->subject_id);

            \Illuminate\Support\Facades\DB::transaction(function () use ($ch) {
                foreach ($ch->topics as $tp) {
                    if ($tp->image_path) $this->deleteS3File($tp->image_path);
                    if ($tp->pdf_path)   $this->deleteS3File($tp->pdf_path);
                    $tp->delete();
                }
                if ($ch->image_path) $this->deleteS3File($ch->image_path);
                if ($ch->pdf_path)   $this->deleteS3File($ch->pdf_path);
                $ch->delete();
            });

            $this->expandedChapters = array_values(array_diff($this->expandedChapters, [$ch->id]));
            $push->outlineSaved($before);
            $this->notification()->success('Chapter deleted!');
            return;
        }

        $tp = $this->findTopic($this->deleteTargetId) ?? throw new \RuntimeException('This topic is no longer there.');
        $before = $push->outlineSnapshotOfChapter((int) $tp->chapter_id);
        if ($tp->image_path) $this->deleteS3File($tp->image_path);
        if ($tp->pdf_path)   $this->deleteS3File($tp->pdf_path);
        $tp->delete();
        $push->outlineSaved($before);
        $this->notification()->success('Topic deleted!');
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $org      = Auth::user()->organization_id;
        $chapters = collect();
        $showList = $this->filterStandard && $this->filterSubject;

        if ($showList) {
            $chapters = Chapter::with(['topics' => fn($q) => $q->orderBy('order')->orderBy('id'), 'standard', 'section', 'subject'])
                ->where('organization_id', $org)
                ->where('standard_id', $this->filterStandard)
                ->when($this->filterSection, fn($q) => $this->inSection($q))
                ->where('subject_id', $this->filterSubject)
                ->orderBy('order')
                ->get();
        }

        return view('livewire.admin.content', compact('chapters', 'showList'));
    }
}
