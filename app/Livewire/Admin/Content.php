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
    // One panel for all of it. Add Content: a chapter of the class and subject
    // picked (or a new one, with its number and name), a type, the content —
    // no name to type. Edit: an existing chapter's or topic's name and content.
    // A topic with nothing in it yet gets its content without its name asked.
    public $openContentModal = false;
    public $contentTargetType = ''; // 'chapter' or 'topic'
    public $contentTargetId   = null;
    public $contentTargetName = '';
    public $contentEditMode   = false;
    public $contentAdding     = false; // Add Content: the chapter is picked in the panel
    public $contentChapterId  = '';    // Add Content's chapter: its id, or 'new'
    public $contentName       = ''; // the chapter's or topic's name, editable
    public $contentOrder      = ''; // a chapter's number

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

    /** The class and subject picked: their chapters (a section's include the whole class's). */
    private function filteredChapters()
    {
        return Chapter::where('organization_id', $this->orgId())
            ->where('standard_id', $this->filterStandard)
            ->when($this->filterSection, fn ($q) => $this->inSection($q))
            ->where('subject_id', $this->filterSubject);
    }

    /**
     * Add Content — the header's, or a chapter's (that chapter picked): the
     * panel asks for a chapter, a type and the content, and no name. With no
     * chapter yet, it starts on a new one, numbered next.
     */
    public function openNewContent(?int $chapterId = null): void
    {
        if (!$this->filterStandard || !$this->filterSubject) {
            $this->notification()->error('Pick a class and a subject first.');
            return;
        }

        $this->resetContentForm();
        $this->contentTargetType = 'chapter';
        $this->contentAdding     = true;
        $this->contentChapterId  = (string) ($this->findChapter($chapterId)?->id
            ?? $this->filteredChapters()->orderBy('order')->value('id')
            ?? 'new');
        $this->contentOrder      = (string) ((int) $this->filteredChapters()->max('order') + 1);
        $this->openContentModal  = true;
    }

    /** A row's Add Content: a chapter's is Add Content with it picked; a topic's asks only the content. */
    public function onAddContent(string $type, int $id): void
    {
        if ($type === 'chapter') {
            $this->openNewContent($id);
            return;
        }

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
            'contentAdding',
            'contentChapterId',
            'contentName',
            'contentOrder',
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

        // Add Content asks for no name: only a new chapter needs its number and
        // name, and Edit renames.
        $newChapter = $this->contentAdding && $this->contentChapterId === 'new';
        $withName   = $this->contentEditMode || $newChapter;

        // Limits: text max 10,000 chars, image max 500 KB. Rules run regardless
        // of the selected content type so an oversized upload can never slip in.
        $this->validate([
            'contentChapterId' => $this->contentAdding ? 'required' : 'nullable',
            'contentName'  => $withName ? 'required|string|max:255' : 'nullable|string|max:255',
            'contentOrder' => $this->contentTargetType === 'chapter' && $withName ? 'required|integer|min:1|max:999' : 'nullable',
            'contentText'  => 'nullable|string|max:10000',
            'contentUrl'   => 'nullable|url|max:2000',
            'contentImage' => 'nullable|image|max:500', // max is in KB → 500 KB
            'contentPdf'   => 'nullable|file|mimes:pdf|max:5120',
        ], [
            'contentChapterId.required' => 'Pick a chapter.',
            'contentName.required' => $this->contentTargetType === 'chapter' ? 'Enter the chapter name.' : 'Enter the topic name.',
            'contentOrder.required' => 'Enter the chapter number.',
            'contentText.max'    => 'Text cannot exceed 10,000 characters.',
            'contentUrl.url'     => 'Please enter a valid URL.',
            'contentImage.image' => 'The file must be an image.',
            'contentImage.max'   => 'Image must be 500 KB or smaller.',
            'contentPdf.mimes'   => 'The file must be a PDF.',
            'contentPdf.max'     => 'PDF must be 5 MB or smaller.',
        ]);

        // Adding, there has to be something to add (Edit may only rename).
        if (!$this->contentEditMode && !$this->hasNewContent()) {
            $this->addError('contentType', match ($this->contentType) {
                'text'  => 'Write the content text.',
                'url'   => 'Enter the URL.',
                'image' => 'Choose the image.',
                'pdf'   => 'Choose the PDF.',
                default => 'Add some content first.',
            });
            return;
        }

        $org  = $this->orgId();
        $name = trim((string) $this->contentName);
        // The subject's teachers hear what changed in its outline, as Syllabus tells them.
        $push = app(\App\Services\TeacherPushNotifier::class);

        try {
            if ($this->contentTargetType === 'chapter') {
                $chapterId = $this->contentAdding
                    ? ($newChapter ? null : (int) $this->contentChapterId)
                    : $this->contentTargetId;
                $record = $chapterId ? $this->findChapter((int) $chapterId) : null;
                if ($chapterId && !$record) {
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

                // Adding keeps what the chapter already holds; only the type picked is set.
                $data = ($withName ? ['name' => $name, 'order' => (int) $this->contentOrder] : [])
                    + $this->buildChapterContentData($record, $this->contentAdding);
                $record->update($data);
                $push->outlineSaved($before);
            } else {
                $record = $this->findTopic((int) $this->contentTargetId)
                    ?? throw new \RuntimeException('This topic is no longer there.');
                $before = $push->outlineSnapshotOfChapter((int) $record->chapter_id);

                $data = ($withName ? ['topic_name' => $name] : []) + $this->buildTopicContentData($record);
                $record->update($data);
                $push->outlineSaved($before);
            }

            $this->notification()->success($this->contentEditMode ? 'Saved successfully!' : 'Content added!');
            $this->closeContentModal();
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Content save error: ' . $e->getMessage());
        }
    }

    /** The panel's type: All takes every kind, any other only its own. */
    private function wants(string $kind): bool
    {
        return $this->contentType === 'all' || $this->contentType === $kind;
    }

    /** Something to add, of the type picked: text written, a URL, an image or a PDF chosen. */
    private function hasNewContent(): bool
    {
        return ($this->wants('text') && trim((string) $this->contentText) !== '')
            || ($this->wants('url') && trim((string) $this->contentUrl) !== '')
            || ($this->wants('image') && $this->contentImage)
            || ($this->wants('pdf') && $this->contentPdf);
    }

    /**
     * What to write into a chapter. All — or Add Content ($add) — sets only the
     * fields given (of the type picked) and leaves the rest as they are; Edit
     * with one type makes the chapter hold that type only.
     */
    private function buildChapterContentData($chapter, bool $add = false): array
    {
        $data = [];

        if ($this->contentType === 'all' || $add) {
            // Save all fields that have data
            if ($this->wants('text') && $this->contentText) $data['description'] = $this->contentText;
            if ($this->wants('url') && $this->contentUrl)   $data['file_path']   = $this->contentUrl;

            if ($this->wants('image') && $this->contentImage) {
                if ($chapter->image_path) $this->deleteS3File($chapter->image_path);
                $path = $this->contentImage->store('admin/content/chapter-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['image_path'] = Storage::disk('s3')->url($path);
            }

            if ($this->wants('pdf') && $this->contentPdf) {
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
