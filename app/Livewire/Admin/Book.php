<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Book as ModalBook;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\Subject;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use WireUi\Traits\WireUiActions;

class Book extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    public $open = false;
    public $showViewModal = false;
    public $editId = null;
    
    // Form fields
    public $title = '';
    public $standard_id = '';
    public $section_id = '';
    public $subject_id = '';
    public $book_logo;
    public $pdf_file;
    public $is_active = true;
    
    // Temporary URLs for preview
    public $tempLogoUrl = null;
    public $tempPdfUrl = null;
    
    // Dropdown data
    public $standards = [];
    public $sections = [];
    public $subjects = [];
    
    // View modal data
    public $viewModalTitle = '';
    public $viewBook = null;

    // Table filters
    #[Url]
    public $search = '';

    #[Url]
    public $perPage = 10;

    #[Url]
    public $filterStandard = '';

    #[Url]
    public $filterSection = '';

    #[Url]
    public $filterSubject = '';

    #[Url]
    public $filterStatus = '';

    // Filter dropdown data
    public $filterSections = [];
    public $filterSubjects = [];

    // Custom delete overlay (replaces broken WireUI dialog)
    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    // Stats
    public int $totalBooks    = 0;
    public int $activeBooks   = 0;
    public int $inactiveBooks = 0;
    public int $withPdfCount  = 0;

    protected $listeners = ['refresh-book-list' => '$refresh'];

    public function mount()
    {
        $this->loadStandards();
        $this->loadFilterData();
        $this->loadStats();
    }

    public function loadStandards()
    {
        $organizationId = Auth::user()->organization_id;
        
        $this->standards = Standard::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function loadFilterData()
    {
        if ($this->filterStandard) {
            $this->filterSections = Section::where('standard_id', $this->filterStandard)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $this->loadFilterSubjects($this->filterStandard, $this->filterSection);
        }
    }

    private function loadFilterSubjects($standardId, $sectionId = null)
    {
        $organizationId = Auth::user()->organization_id;

        if ($sectionId) {
            $this->filterSubjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        } else {
            $this->filterSubjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        }
    }

    /**
     * Handle real-time updates for table filters
     */
    public function updated($property, $value)
    {
        // Reset pagination when filters change
        if (in_array($property, ['search', 'filterStandard', 'filterSection', 'filterSubject', 'filterStatus'])) {
            $this->resetPage();
        }

        // Handle filter standard change
        if ($property === 'filterStandard' && $value) {
            $this->filterSections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            $this->filterSection = '';

            $this->loadFilterSubjects($value);
            $this->filterSubject = '';
        } elseif ($property === 'filterStandard' && !$value) {
            $this->filterSections = [];
            $this->filterSection = '';
            $this->filterSubjects = [];
            $this->filterSubject = '';
        }

        // Handle filter section change
        if ($property === 'filterSection' && $value && $this->filterStandard) {
            $this->loadFilterSubjects($this->filterStandard, $value);
            $this->filterSubject = '';
        } elseif ($property === 'filterSection' && !$value && $this->filterStandard) {
            $this->loadFilterSubjects($this->filterStandard);
            $this->filterSubject = '';
        }
    }

    /**
     * Handle real-time updates when standard is selected in form
     */
    public function updatedStandardId($value)
    {
        $this->section_id = '';
        $this->subject_id = '';
        $this->sections = [];
        $this->subjects = [];

        if ($value) {
            $this->sections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $this->loadSubjectsForStandard($value);
        }
    }

    /**
     * Handle real-time updates when section is selected in form
     */
    public function updatedSectionId($value)
    {
        $this->subject_id = '';
        
        if ($value && $this->standard_id) {
            $this->loadSubjectsForStandard($this->standard_id, $value);
        } elseif (!$value && $this->standard_id) {
            $this->loadSubjectsForStandard($this->standard_id);
        }
    }

    /**
     * Load subjects based on standard and optionally section for form
     */
    private function loadSubjectsForStandard($standardId, $sectionId = null)
    {
        $organizationId = Auth::user()->organization_id;

        if ($sectionId) {
            $this->subjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        } else {
            $this->subjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        }

        if ($this->subjects->isEmpty()) {
            $this->subjects = collect();
        }
    }

    public function updatedBookLogo()
    {
        $this->validate([
            'book_logo' => 'image|max:1024',
        ], [
            'book_logo.max' => 'Cover image must be 1 MB (1024 KB) or smaller.',
        ]);
        $this->tempLogoUrl = $this->book_logo->temporaryUrl();
    }

    public function updatedPdfFile()
    {
        $this->validate([
            'pdf_file' => 'file|mimes:pdf|max:5120',
        ], [
            'pdf_file.max' => 'PDF must be 5 MB (5120 KB) or smaller.',
        ]);
        $this->tempPdfUrl = $this->pdf_file->getClientOriginalName();
    }

    public function onAddBook()
    {
        $this->open = true;
    }

    public function closeModal()
    {
        $this->open = false;
        $this->resetForm();
    }

    public function onSave()
    {
        $orgId = Auth::user()->organization_id;

        $this->validate([
            'title' => [
                'required', 'string', 'max:100',
                Rule::unique('books', 'title')
                    ->where(fn($q) => $q
                        ->where('organization_id', $orgId)
                        ->where('standard_id', $this->standard_id)
                        ->where('section_id', $this->section_id ?: null)
                    )
                    ->ignore($this->editId),
            ],
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'book_logo'   => 'nullable|image|max:1024',
            'pdf_file'    => 'nullable|file|mimes:pdf|max:5120',
            'is_active'   => 'boolean',
        ], [
            'title.max'      => 'Book title may not be longer than 100 characters.',
            'title.unique'   => 'A book with this name already exists for this class and section.',
            'book_logo.max'  => 'Cover image must be 1 MB (1024 KB) or smaller.',
            'pdf_file.max'   => 'PDF must be 5 MB (5120 KB) or smaller.',
        ]);

        try {
            $data = [
                'title' => $this->title,
                'standard_id' => $this->standard_id,
                'section_id' => $this->section_id ?: null,
                'subject_id' => $this->subject_id,
                'is_active' => $this->is_active,
                'organization_id' => Auth::user()->organization_id,
            ];

            if ($this->editId) {
                $book = ModalBook::findOrFail($this->editId);

                if ($this->book_logo) {
                    if ($book->book_logo) {
                        $oldLogoPath = parse_url($book->book_logo, PHP_URL_PATH);
                        Storage::disk('s3')->delete($oldLogoPath);
                    }

                    $logoPath = $this->book_logo->store('admin/library/covers', 's3');
                    Storage::disk('s3')->setVisibility($logoPath, 'public');
                    $data['book_logo'] = Storage::disk('s3')->url($logoPath);
                }

                if ($this->pdf_file) {
                    if ($book->pdf_file) {
                        $oldPdfPath = parse_url($book->pdf_file, PHP_URL_PATH);
                        Storage::disk('s3')->delete($oldPdfPath);
                    }

                    $pdfPath = $this->pdf_file->store('admin/library/pdfs', 's3');
                    Storage::disk('s3')->setVisibility($pdfPath, 'public');
                    $data['pdf_file'] = Storage::disk('s3')->url($pdfPath);
                }

                $book->update($data);
                $this->notification()->success('Book updated successfully!');
            } else {
                if ($this->book_logo) {
                    $logoPath = $this->book_logo->store('admin/library/covers', 's3');
                    Storage::disk('s3')->setVisibility($logoPath, 'public');
                    $data['book_logo'] = Storage::disk('s3')->url($logoPath);
                }

                if ($this->pdf_file) {
                    $pdfPath = $this->pdf_file->store('admin/library/pdfs', 's3');
                    Storage::disk('s3')->setVisibility($pdfPath, 'public');
                    $data['pdf_file'] = Storage::disk('s3')->url($pdfPath);
                }

                ModalBook::create($data);
                $this->notification()->success('Book added successfully!');
            }

            $this->loadStats();
            $this->closeModal();
        } catch (\Exception $e) {
            $this->notification()->error(
                'Error Saving Book',
                $e->getMessage()
            );
            logger()->error('Book save error: ' . $e->getMessage());
        }
    }

    protected function resetForm()
    {
        $this->reset([
            'editId',
            'title',
            'standard_id',
            'section_id',
            'subject_id',
            'book_logo',
            'pdf_file',
            'is_active',
            'tempLogoUrl',
            'tempPdfUrl',
            'sections',
            'subjects'
        ]);
        $this->resetErrorBag();
    }

    public function onEditBook($id)
    {
        $book = ModalBook::findOrFail($id);

        $this->editId = $book->id;
        $this->title = $book->title;
        $this->standard_id = $book->standard_id;
        $this->section_id = $book->section_id;
        $this->subject_id = $book->subject_id;
        $this->is_active = $book->is_active;

        if ($book->standard_id) {
            $this->sections = Section::where('standard_id', $book->standard_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $this->loadSubjectsForStandard($book->standard_id, $book->section_id);
        }

        $this->open = true;
    }

    public function onDeleteBook($id)
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        try {
            $book = ModalBook::findOrFail($this->deleteTargetId);

            if ($book->book_logo) {
                Storage::disk('s3')->delete(parse_url($book->book_logo, PHP_URL_PATH));
            }
            if ($book->pdf_file) {
                Storage::disk('s3')->delete(parse_url($book->pdf_file, PHP_URL_PATH));
            }

            $book->delete();
            $this->notification()->success('Book deleted successfully!');
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error Deleting Book', $e->getMessage());
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function doDeleteBook($id = null): void
    {
        if ($id) $this->deleteTargetId = $id;
        $this->confirmDelete();
    }

    private function loadStats(): void
    {
        $orgId = Auth::user()->organization_id;
        $stats = ModalBook::where('organization_id', $orgId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN pdf_file IS NOT NULL AND pdf_file != "" THEN 1 ELSE 0 END) as with_pdf
            ')->first();

        $this->totalBooks    = (int) ($stats->total ?? 0);
        $this->activeBooks   = (int) ($stats->active ?? 0);
        $this->inactiveBooks = (int) ($stats->inactive ?? 0);
        $this->withPdfCount  = (int) ($stats->with_pdf ?? 0);
    }

    public function onViewBook($id)
    {
        $book = ModalBook::with(['standard', 'section', 'subject'])->find($id);

        if (!$book) {
            $this->notification()->error('Book not found!');
            return;
        }

        $this->viewModalTitle = 'Book Details';
        $this->viewBook = $book;
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewBook = null;
        $this->viewModalTitle = '';
    }

    /**
     * Stream the book's PDF to the browser as a download (force save).
     */
    public function downloadBook($id)
    {
        $book = ModalBook::find($id);

        if (!$book || !$book->pdf_file) {
            $this->notification()->error('No PDF available for this book.');
            return;
        }

        $path = ltrim(parse_url($book->pdf_file, PHP_URL_PATH), '/');
        $filename = \Illuminate\Support\Str::slug($book->title ?: 'book') . '.pdf';

        try {
            return Storage::disk('s3')->download($path, $filename);
        } catch (\Exception $e) {
            $this->notification()->error('Download failed', $e->getMessage());
        }
    }

    public function render()
    {
        $books = $this->getBooks();
        return view('livewire.admin.book', compact('books'));
    }

    private function getBooks()
    {
        // Books list is gated on class selection — show nothing until a class is picked.
        if (!$this->filterStandard) {
            return ModalBook::query()->whereRaw('0 = 1')->paginate($this->perPage);
        }

        $query = ModalBook::with(['standard', 'section', 'subject'])
            ->where('organization_id', Auth::user()->organization_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhereHas('standard', function ($standardQuery) {
                        $standardQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('subject', function ($subjectQuery) {
                        $subjectQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->filterStandard) {
            $query->where('standard_id', $this->filterStandard);
        }

        if ($this->filterSection) {
            $query->where('section_id', $this->filterSection);
        }

        if ($this->filterSubject) {
            $query->where('subject_id', $this->filterSubject);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }
}