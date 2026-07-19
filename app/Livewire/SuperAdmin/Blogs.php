<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Blog;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Blogs extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    /** Filters */
    public string $search = '';
    public string $dateFilter = ''; // '' | 7 | 15 | 30 | 60

    /** Slide-in panel */
    public bool $showPanel = false;
    public ?int $editId = null;

    /** Form fields */
    public $coverImage = null;             // new upload
    public ?string $coverImageUrl = null;  // existing url (edit)
    public string $category = '';
    public string $title = '';
    public string $heading = '';

    /** Article body as a list of paragraphs (add / edit / delete). */
    public array $paragraphs = [''];

    /** Delete confirmation */
    public ?int $pendingDelete = null;

    protected function rules(): array
    {
        return [
            'coverImage'    => 'nullable|image|max:4096', // 4 MB
            'category'      => 'nullable|string|max:255',
            'title'         => 'required|string|max:255',
            'heading'       => 'nullable|string|max:255',
            'paragraphs'    => 'array',
            'paragraphs.*'  => 'nullable|string|max:20000',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    // ─── Panel ────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $blog = Blog::findOrFail($id);
        $this->editId        = $blog->id;
        $this->category      = $blog->category ?? '';
        $this->title         = $blog->title;
        $this->heading       = $blog->heading ?? '';
        // Prefer structured paragraphs; fall back to legacy description text.
        $paras               = $blog->body_paragraphs;
        $this->paragraphs    = !empty($paras) ? $paras : [''];
        $this->coverImageUrl = $blog->cover_image;
        $this->coverImage    = null;
        $this->resetErrorBag();
        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editId', 'coverImage', 'coverImageUrl', 'category', 'title', 'heading', 'paragraphs']);
        $this->paragraphs = [''];
        $this->resetErrorBag();
    }

    // ─── Paragraph repeater ───────────────────────────────────────────

    public function addParagraph(): void
    {
        $this->paragraphs[] = '';
    }

    public function removeParagraph(int $index): void
    {
        unset($this->paragraphs[$index]);
        $this->paragraphs = array_values($this->paragraphs);
        if (empty($this->paragraphs)) {
            $this->paragraphs = [''];
        }
    }

    public function moveParagraphUp(int $index): void
    {
        if ($index > 0) {
            [$this->paragraphs[$index - 1], $this->paragraphs[$index]] =
                [$this->paragraphs[$index], $this->paragraphs[$index - 1]];
        }
    }

    public function moveParagraphDown(int $index): void
    {
        if ($index < count($this->paragraphs) - 1) {
            [$this->paragraphs[$index + 1], $this->paragraphs[$index]] =
                [$this->paragraphs[$index], $this->paragraphs[$index + 1]];
        }
    }

    public function save(): void
    {
        $this->validate();

        // Drop blank paragraphs and normalise whitespace.
        $paragraphs = array_values(array_filter(
            array_map('trim', $this->paragraphs),
            fn($p) => $p !== ''
        ));

        $data = [
            'category'    => $this->category ?: null,
            'title'       => $this->title,
            'heading'     => $this->heading ?: null,
            'paragraphs'  => $paragraphs ?: null,
            // Keep the plain-text description in sync (used for search & excerpts).
            'description' => $paragraphs ? implode("\n\n", $paragraphs) : null,
        ];

        // Cover image upload (public S3 url)
        if ($this->coverImage) {
            if ($this->editId && $this->coverImageUrl) {
                $old = parse_url($this->coverImageUrl, PHP_URL_PATH);
                if ($old) {
                    Storage::disk('s3')->delete(ltrim($old, '/'));
                }
            }
            $path = $this->coverImage->store('website/blogs', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['cover_image'] = Storage::disk('s3')->url($path);
        }

        if ($this->editId) {
            $blog = Blog::findOrFail($this->editId);
            $data['slug'] = Blog::uniqueSlug($this->title, $blog->id);
            $blog->update($data);
            $this->notification()->success('Updated', 'Blog post updated successfully.');
        } else {
            $data['slug'] = Blog::uniqueSlug($this->title);
            Blog::create($data);
            $this->notification()->success('Published', 'Blog post created successfully.');
        }

        $this->closePanel();
    }

    // ─── Delete ───────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->pendingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->pendingDelete = null;
    }

    public function deleteBlog(): void
    {
        $blog = Blog::find($this->pendingDelete);
        if ($blog) {
            if ($blog->cover_image) {
                $old = parse_url($blog->cover_image, PHP_URL_PATH);
                if ($old) {
                    Storage::disk('s3')->delete(ltrim($old, '/'));
                }
            }
            $blog->delete();
            $this->notification()->success('Deleted', 'Blog post removed.');
        }
        $this->pendingDelete = null;
    }

    public function render()
    {
        $blogs = Blog::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('heading', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->dateFilter, fn($q) => $q->where('created_at', '>=', now()->subDays((int) $this->dateFilter)))
            ->latest()
            ->paginate(9);

        return view('livewire.super-admin.website.blogs', [
            'blogs' => $blogs,
        ]);
    }
}
