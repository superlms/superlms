<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PicksStudent;
use App\Models\Admin\SchoolComplaint;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Complaints: the school's admin records a complaint about a student
 * — picked by class, section and name — with a title, a description and a
 * file attached if wanted (a photo, a PDF, a Word file). Every complaint is
 * listed, newest first, and can be found by class, section or a word of its
 * title or the student's name; each can be viewed, edited and deleted.
 */
class Complaints extends Component
{
    use PicksStudent, WithFileUploads, WithPagination, WireUiActions;

    /** Largest attachment, in KB. */
    public const MAX_FILE_KB = 10240;

    public const FILE_TYPES = 'jpg,jpeg,png,webp,pdf,doc,docx';

    // ─── The list's filters ──────────────────────────────────────────────────
    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $search         = '';

    // ─── Add / edit ──────────────────────────────────────────────────────────
    public bool   $formOpen    = false;
    public ?int   $editingId   = null;
    public string $title       = '';
    public string $description = '';
    /** A file picked to attach (replaces a saved one). */
    public $attachment = null;
    /** The saved file is to go when the form is saved. */
    public bool $removeFile = false;

    // ─── View / delete ───────────────────────────────────────────────────────
    public ?int $viewingId  = null;
    public ?int $deletingId = null;

    private function own()
    {
        return SchoolComplaint::where('organization_id', $this->orgId());
    }

    public function updatedFilterStandard(): void
    {
        $this->filterSection = '';
        $this->resetPage();
    }

    public function updatedFilterSection(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterStandard', 'filterSection', 'search']);
        $this->resetPage();
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function openEdit(int $id): void
    {
        $c = $this->own()->find($id);
        if (!$c) {
            return;
        }
        $this->resetForm();
        $this->editingId   = $c->id;
        $this->title       = (string) $c->title;
        $this->description = (string) $c->description;
        $this->pickStudent($c->standard_id, $c->section_id, $c->student_detail_id);
        $this->viewingId = null;
        $this->formOpen  = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->resetForm();
    }

    /** Takes the picked file off, or marks the saved one to go. */
    public function dropAttachment(): void
    {
        if ($this->attachment) {
            $this->attachment = null;

            return;
        }
        $this->removeFile = true;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'attachment', 'removeFile', 'standardId', 'sectionId', 'studentId']);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate($this->pickerRules() + [
            'title'       => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'attachment'  => 'nullable|file|max:' . self::MAX_FILE_KB . '|mimes:' . self::FILE_TYPES,
        ], $this->pickerMessages() + [
            'title.required'       => 'Please enter a title.',
            'description.required' => 'Please describe the complaint.',
            'attachment.max'       => 'The file can be up to 10 MB.',
            'attachment.mimes'     => 'Attach a photo (JPG, PNG, WEBP), a PDF or a Word file.',
        ]);

        $student = $this->pickedStudent();
        if (!$student) {
            $this->addError('studentId', 'Please select a student of this class and section.');

            return;
        }

        $complaint = $this->editingId
            ? $this->own()->find($this->editingId)
            : new SchoolComplaint(['organization_id' => $this->orgId(), 'added_by' => Auth::id()]);
        if (!$complaint) {
            $this->closeForm();

            return;
        }

        $old = $complaint->file_path;
        if ($this->attachment) {
            $complaint->file_path = $this->attachment->store('admin/complaints/' . $this->orgId(), 's3');
            $complaint->file_name = mb_substr((string) $this->attachment->getClientOriginalName(), 0, 255);
        } elseif ($this->removeFile) {
            $complaint->file_path = null;
            $complaint->file_name = null;
        }

        $complaint->fill([
            'student_detail_id' => $student->id,
            'standard_id'       => $student->standard_id,
            'section_id'        => $student->section_id,
            'title'             => trim($this->title),
            'description'       => trim($this->description),
        ])->save();

        if ($old && $old !== $complaint->file_path) {
            $this->deleteFile($old);
        }

        $this->notification()->success($this->editingId ? 'Complaint updated' : 'Complaint added');
        $this->formOpen = false;
        $this->resetForm();
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteComplaint(): void
    {
        $complaint = $this->own()->find($this->deletingId);
        $this->deletingId = null;
        if (!$complaint) {
            return;
        }
        if ($complaint->file_path) {
            $this->deleteFile($complaint->file_path);
        }
        $complaint->delete();
        if ($this->viewingId === $complaint->id) {
            $this->viewingId = null;
        }
        $this->notification()->success('Complaint deleted');
    }

    private function deleteFile(string $path): void
    {
        try {
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            logger()->warning('Complaint file not deleted: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $orgId = $this->orgId();
        $term  = trim($this->search);

        $complaints = $this->own()
            ->with(['studentDetail.user:id,image', 'standard:id,name', 'section:id,name'])
            ->when($this->filterStandard !== '', fn ($q) => $q->where('standard_id', (int) $this->filterStandard))
            ->when($this->filterSection !== '', fn ($q) => $q->where('section_id', (int) $this->filterSection))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$term}%")
                ->orWhereHas('studentDetail', fn ($s) => $s->where('full_name', 'like', "%{$term}%")
                    ->orWhere('admission_no', 'like', "%{$term}%"))))
            ->latest('id')
            ->paginate(10);

        $filterStandards = Standard::where('organization_id', $orgId)->where('is_active', true)->inClassOrder()->get(['id', 'name']);

        return view('livewire.admin.complaints', [
            'complaints'      => $complaints,
            'total'           => $this->own()->count(),
            'filterStandards' => $filterStandards,
            'filterSections'  => $this->filterStandard !== ''
                ? Section::where('standard_id', (int) $this->filterStandard)->whereIn('standard_id', $filterStandards->pluck('id'))->orderBy('name')->get(['id', 'name'])
                : collect(),
            'viewing'   => $this->viewingId
                ? $this->own()->with(['studentDetail.user:id,image', 'standard:id,name', 'section:id,name', 'addedBy:id,name'])->find($this->viewingId)
                : null,
            'editing'   => $this->formOpen && $this->editingId ? $this->own()->find($this->editingId) : null,
            'standards' => $this->formOpen ? $this->pickerStandards() : collect(),
            'sections'  => $this->formOpen ? $this->pickerSections() : collect(),
            'students'  => $this->formOpen ? $this->pickerStudents() : collect(),
        ]);
    }
}
