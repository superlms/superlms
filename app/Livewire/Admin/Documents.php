<?php

namespace App\Livewire\Admin;

use App\Models\SuperAdmin\SuperAdminDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * School-admin view of documents the super-admin has sent to this organization
 * (either broadcast to all schools or targeted specifically). Admins can view
 * and download; they cannot edit or delete.
 */
class Documents extends Component
{
    use WithPagination, WireUiActions;

    public $organization = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? Auth::user()?->organization_id;
    }

    private function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    public function downloadDocument(int $id)
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

    public function render()
    {
        $documents = SuperAdminDocument::forOrganization($this->orgId())
            ->latest()
            ->paginate(12);

        return view('livewire.admin.documents', compact('documents'));
    }
}
