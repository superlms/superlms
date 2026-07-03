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
    public $openContentModal = false;
    public $contentTargetType = ''; // 'chapter' or 'topic'
    public $contentTargetId   = null;
    public $contentTargetName = '';
    public $contentEditMode   = false;

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

    // ─── Delete confirm overlay (replaces broken WireUI dialog) ──────────
    public bool   $showDeleteConfirm = false;
    public string $deleteTargetType  = ''; // 'chapter' | 'topic'
    public ?int   $deleteTargetId    = null;
    public string $deleteTargetName  = '';

    // ─── Stats ───────────────────────────────────────────────────────────
    public $totalChapters = 0;
    public $totalTopics   = 0;
    public $withContent   = 0;

    public function mount(): void
    {
        $org = Auth::user()->organization_id;
        $this->standards = Standard::where('organization_id', $org)->where('is_active', true)->orderBy('id')->get();
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

    public function onAddContent(string $type, int $id): void
    {
        $this->resetContentForm();
        $this->contentTargetType = $type;
        $this->contentTargetId   = $id;
        $this->contentEditMode   = false;

        if ($type === 'chapter') {
            $ch = Chapter::find($id);
            $this->contentTargetName = $ch?->name ?? '';
        } else {
            $tp = Topic::find($id);
            $this->contentTargetName = $tp?->topic_name ?? '';
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
            $ch = Chapter::find($id);
            if (!$ch) return;
            $this->contentTargetName = $ch->name;

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
            $tp = Topic::find($id);
            if (!$tp) return;
            $this->contentTargetName = $tp->topic_name;

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
        if (!$this->contentTargetId || !$this->contentTargetType) {
            $this->notification()->error('Invalid target.');
            return;
        }

        // Limits: text max 10,000 chars, image max 500 KB. Rules run regardless
        // of the selected content type so an oversized upload can never slip in.
        $this->validate([
            'contentText'  => 'nullable|string|max:10000',
            'contentUrl'   => 'nullable|url|max:2000',
            'contentImage' => 'nullable|image|max:500', // max is in KB → 500 KB
        ], [
            'contentText.max'    => 'Text cannot exceed 10,000 characters.',
            'contentUrl.url'     => 'Please enter a valid URL.',
            'contentImage.image' => 'The file must be an image.',
            'contentImage.max'   => 'Image must be 500 KB or smaller.',
        ]);

        try {
            if ($this->contentTargetType === 'chapter') {
                $record = Chapter::findOrFail($this->contentTargetId);
                $data   = $this->buildChapterContentData($record);
                $record->update($data);
            } else {
                $record = Topic::findOrFail($this->contentTargetId);
                $data   = $this->buildTopicContentData($record);
                $record->update($data);
            }

            $this->notification()->success('Content saved successfully!');
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
                $data['description'] = $this->contentText;
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
                $data['topic_content'] = $this->contentText;
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
            $ch = Chapter::find($id);
            if (!$ch) return;
            $this->viewContentTitle = $ch->name;
            $this->viewContentData  = [
                'text'  => $ch->description,
                'url'   => $ch->file_path,
                'image' => $ch->image_path,
                'pdf'   => $ch->pdf_path,
            ];
        } else {
            $tp = Topic::find($id);
            if (!$tp) return;
            $this->viewContentTitle = $tp->topic_name;
            $this->viewContentData  = [
                'text'  => $tp->topic_content,
                'url'   => null,
                'image' => $tp->image_path,
                'pdf'   => $tp->pdf_path,
            ];
        }
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal    = false;
        $this->viewContentData  = [];
        $this->viewContentTitle = '';
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DELETE CONTENT
    // ═══════════════════════════════════════════════════════════════════════

    public function deleteContent(string $type, int $id): void
    {
        $this->deleteTargetType = $type;
        $this->deleteTargetId   = (int) $id;

        if ($type === 'chapter') {
            $this->deleteTargetName = Chapter::find($id)?->name ?? '';
        } else {
            $this->deleteTargetName = Topic::find($id)?->topic_name ?? '';
        }

        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetType  = '';
        $this->deleteTargetId    = null;
        $this->deleteTargetName  = '';
    }

    public function confirmDelete(): void
    {
        if (!$this->deleteTargetId || !$this->deleteTargetType) {
            $this->cancelDelete();
            return;
        }

        try {
            if ($this->deleteTargetType === 'chapter') {
                $ch = Chapter::findOrFail($this->deleteTargetId);
                if ($ch->image_path) $this->deleteS3File($ch->image_path);
                if ($ch->pdf_path)   $this->deleteS3File($ch->pdf_path);
                $ch->update(['description' => null, 'file_path' => null, 'image_path' => null, 'pdf_path' => null]);
            } else {
                $tp = Topic::findOrFail($this->deleteTargetId);
                if ($tp->image_path) $this->deleteS3File($tp->image_path);
                if ($tp->pdf_path)   $this->deleteS3File($tp->pdf_path);
                $tp->update(['topic_content' => null, 'image_path' => null, 'pdf_path' => null]);
            }
            $this->notification()->success('Content removed!');
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }

        $this->cancelDelete();
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $org      = Auth::user()->organization_id;
        $chapters = collect();
        $showList = $this->filterStandard && $this->filterSubject;

        if ($showList) {
            $chapters = Chapter::with(['topics' => fn($q) => $q->orderBy('id'), 'standard', 'section', 'subject'])
                ->where('organization_id', $org)
                ->where('standard_id', $this->filterStandard)
                ->when($this->filterSection, fn($q) => $q->where('section_id', $this->filterSection))
                ->where('subject_id', $this->filterSubject)
                ->orderBy('order')
                ->get();
        }

        return view('livewire.admin.content', compact('chapters', 'showList'));
    }
}
