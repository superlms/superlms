<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Faq;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Faqs extends Component
{
    use WireUiActions, WithPagination;

    /** Filters */
    public string $search = '';
    public string $categoryFilter = '';

    /** Slide-in panel */
    public bool $showPanel = false;
    public ?int $editId = null;

    /** Form fields */
    public string $category = '';     // chosen existing category
    public string $newCategory = '';  // typed new category (takes priority)
    public string $question = '';
    public string $answer = '';

    /** Delete confirmation */
    public ?int $pendingDelete = null;

    protected function rules(): array
    {
        return [
            'category'    => 'nullable|string|max:255',
            'newCategory' => 'nullable|string|max:255',
            'question'    => 'required|string|max:500',
            'answer'      => 'required|string|max:20000',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    protected function categories()
    {
        return Faq::query()->whereNotNull('category')
            ->distinct()->orderBy('category')->pluck('category');
    }

    // ─── Panel ────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $this->editId      = $faq->id;
        $this->category    = $faq->category ?? '';
        $this->newCategory = '';
        $this->question    = $faq->question;
        $this->answer      = $faq->answer ?? '';
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
        $this->reset(['editId', 'category', 'newCategory', 'question', 'answer']);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate();

        $category = trim($this->newCategory) ?: trim($this->category);
        if ($category === '') {
            $this->addError('category', 'Please choose an existing category or add a new one.');
            return;
        }

        $data = [
            'category' => $category,
            'question' => $this->question,
            'answer'   => $this->answer,
        ];

        if ($this->editId) {
            Faq::findOrFail($this->editId)->update($data);
            $this->notification()->success('Updated', 'FAQ updated successfully.');
        } else {
            Faq::create($data);
            $this->notification()->success('Added', 'FAQ added successfully.');
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

    public function deleteFaq(): void
    {
        $faq = Faq::find($this->pendingDelete);
        if ($faq) {
            $faq->delete();
            $this->notification()->success('Deleted', 'FAQ removed.');
        }
        $this->pendingDelete = null;
    }

    public function render()
    {
        $faqs = Faq::query()
            ->when($this->search, fn($q) => $q->where('question', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('category')
            ->orderBy('id')
            ->paginate(12);

        return view('livewire.super-admin.website.faqs', [
            'faqs'       => $faqs,
            'categories' => $this->categories(),
        ]);
    }
}
