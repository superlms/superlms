<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminDocument;
use App\Models\SuperAdmin\SuperAdminDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * School-admin Documents screen. Two things live here:
 *   1. "My Documents" — files the admin uploads and manages for their own
 *      organization (add / edit / download / delete via a right slide-in panel).
 *   2. "Shared with your school" — documents the super-admin has pushed down to
 *      this organization; admins can only view & download these.
 */
class Documents extends Component
{
    use WithFileUploads, WithPagination, WireUiActions;

    public $organization = null;

    // ─── Add / edit slide-in panel (admin-owned docs) ─────────────────────────
    public bool   $showPanel        = false;
    public ?int   $editId           = null;
    public string $title            = '';
    public string $description      = '';
    public $file                    = null;
    public string $existingFileName = '';

    // ─── Delete confirm ───────────────────────────────────────────────────────
    public ?int $deleteId = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? Auth::user()?->organization_id;
    }

    private function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    // ─── Panel open / close ──────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editId    = null;
        $this->showPanel = true;
    }

    public function edit(int $id): void
    {
        $doc = AdminDocument::forOrganization($this->orgId())->find($id);

        if (!$doc) {
            $this->notification()->error('Not found', 'This document is no longer available.');
            return;
        }

        $this->resetForm();
        $this->editId           = $doc->id;
        $this->title            = $doc->title;
        $this->description      = (string) $doc->description;
        $this->existingFileName = $doc->file_name;
        $this->showPanel        = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['title', 'description', 'file', 'existingFileName', 'editId']);
        $this->resetValidation();
    }

    // ─── Save (create or update) ─────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            // File is required when creating; optional (keep existing) when editing.
            'file'        => ($this->editId ? 'nullable' : 'required') . '|file|max:5120', // KB → 5 MB
        ], [
            'file.required' => 'Please choose a file to upload.',
            'file.max'      => 'The file may not be larger than 5 MB.',
        ]);

        try {
            $this->editId ? $this->updateDocument() : $this->createDocument();
        } catch (\Throwable $e) {
            report($e);
            $this->notification()->error('Save failed', $e->getMessage());
            return;
        }

        $this->closePanel();
        $this->resetPage();
    }

    private function createDocument(): void
    {
        $key = $this->file->store('admin/documents', 's3');
        Storage::disk('s3')->setVisibility($key, 'public');

        AdminDocument::create([
            'organization_id' => $this->orgId(),
            'title'           => $this->title,
            'description'     => $this->description ?: null,
            'file_path'       => $key,
            'file_name'       => $this->file->getClientOriginalName(),
            'file_size'       => $this->file->getSize(),
            'mime_type'       => $this->file->getMimeType(),
            'uploaded_by'     => Auth::id() ?: null,
        ]);

        $this->notification()->success('Document added', 'Your document has been saved.');
    }

    private function updateDocument(): void
    {
        $doc = AdminDocument::forOrganization($this->orgId())->findOrFail($this->editId);

        $data = [
            'title'       => $this->title,
            'description' => $this->description ?: null,
        ];

        // Replacing the file is optional; swap in the new one and drop the old.
        $oldKey = null;
        if ($this->file) {
            $key = $this->file->store('admin/documents', 's3');
            Storage::disk('s3')->setVisibility($key, 'public');
            $oldKey            = $doc->file_path;
            $data['file_path'] = $key;
            $data['file_name'] = $this->file->getClientOriginalName();
            $data['file_size'] = $this->file->getSize();
            $data['mime_type'] = $this->file->getMimeType();
        }

        $doc->update($data);

        if ($oldKey) {
            try {
                Storage::disk('s3')->delete($oldKey);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->notification()->success('Document updated', 'Your changes have been saved.');
    }

    // ─── Download ──────────────────────────────────────────────────────────────

    /** Download one of this admin's own documents. */
    public function downloadDocument(int $id)
    {
        $doc = AdminDocument::forOrganization($this->orgId())->find($id);

        if (!$doc || !$doc->file_path) {
            $this->notification()->error('Not found', 'This document is no longer available.');
            return;
        }

        try {
            return Storage::disk('s3')->download($doc->file_path, $doc->file_name);
        } catch (\Throwable $e) {
            $this->notification()->error('Download failed', $e->getMessage());
        }
    }

    /** Download a document the super-admin shared with this school. */
    public function downloadShared(int $id)
    {
        $doc = SuperAdminDocument::forOrganization($this->orgId())->find($id);

        if (!$doc || !$doc->file_path) {
            $this->notification()->error('Not found', 'This document is no longer available.');
            return;
        }

        try {
            return Storage::disk('s3')->download($doc->file_path, $doc->file_name);
        } catch (\Throwable $e) {
            $this->notification()->error('Download failed', $e->getMessage());
        }
    }

    // ─── Delete ────────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function delete(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $doc = AdminDocument::forOrganization($this->orgId())->find($this->deleteId);

        if ($doc) {
            try {
                if ($doc->file_path) {
                    Storage::disk('s3')->delete($doc->file_path);
                }
            } catch (\Throwable $e) {
                report($e);
            }
            $doc->delete();
            $this->notification()->success('Deleted', 'Document removed.');
        }

        $this->deleteId = null;
        $this->resetPage();
    }

    public function render()
    {
        $documents = AdminDocument::forOrganization($this->orgId())
            ->latest()
            ->paginate(12);

        $sharedDocuments = SuperAdminDocument::forOrganization($this->orgId())
            ->latest()
            ->paginate(6, ['*'], 'sharedPage');

        return view('livewire.admin.documents', compact('documents', 'sharedDocuments'));
    }
}
