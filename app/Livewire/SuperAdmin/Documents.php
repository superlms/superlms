<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\SuperAdmin\SuperAdminDocument;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Super-admin document centre. Upload any document/image (≤ 5 MB), target it at
 * all organizations or a chosen set, and the schools' admins receive it in their
 * own Documents screen (plus a bell notification) to view and download. Add and
 * edit both happen in a right slide-in panel.
 */
class Documents extends Component
{
    use WithFileUploads, WithPagination, WireUiActions;

    /** School-admin accounts live on the users table under these roles. */
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    // ─── Add / edit slide-in panel ───────────────────────────────────────────
    public bool   $showPanel     = false;
    public ?int   $editId        = null;
    public string $title         = '';
    public string $description   = '';
    public $file                 = null;
    public string $existingFileName = '';
    public string $audienceScope = 'all';   // all | selected
    public array  $selectedOrgs  = [];

    // ─── Filters ─────────────────────────────────────────────────────────────
    public string $search     = '';
    public string $filterOrg  = '';
    public string $filterFrom = '';
    public string $filterTo   = '';

    // ─── Delete confirm ───────────────────────────────────────────────────────
    public ?int $deleteId = null;

    public function mount(): void
    {
        // A sub-super-admin scoped to one school can only send to that school.
        if ($lockedOrgId = $this->lockedOrganizationId()) {
            $this->audienceScope = 'selected';
            $this->selectedOrgs  = [$lockedOrgId];
            $this->filterOrg     = (string) $lockedOrgId;
        }
    }

    private function lockedOrganizationId(): ?int
    {
        $user = Auth::user();

        return $user->isSubSuperAdmin() ? $user->allowedOrganizationId() : null;
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
        $doc = SuperAdminDocument::when(
            $this->lockedOrganizationId(),
            fn ($q, $orgId) => $q->forOrganization($orgId)
        )->with('organizations:id')->find($id);

        if (!$doc) {
            $this->notification()->error('Not found', 'This document is no longer available.');
            return;
        }

        $this->resetForm();
        $this->editId           = $doc->id;
        $this->title            = $doc->title;
        $this->description      = (string) $doc->description;
        $this->existingFileName = $doc->file_name;
        $this->audienceScope    = $doc->audience_scope;
        $this->selectedOrgs     = $doc->organizations->pluck('id')->all();

        if ($lockedOrgId = $this->lockedOrganizationId()) {
            $this->audienceScope = 'selected';
            $this->selectedOrgs  = [$lockedOrgId];
        }

        $this->showPanel = true;
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

        if ($lockedOrgId = $this->lockedOrganizationId()) {
            $this->audienceScope = 'selected';
            $this->selectedOrgs  = [$lockedOrgId];
        } else {
            $this->audienceScope = 'all';
            $this->selectedOrgs  = [];
        }
    }

    public function toggleOrg(int $orgId): void
    {
        if ($this->lockedOrganizationId()) {
            return; // sub-super-admin is pinned to its own org
        }

        if (in_array($orgId, $this->selectedOrgs, true)) {
            $this->selectedOrgs = array_values(array_diff($this->selectedOrgs, [$orgId]));
        } else {
            $this->selectedOrgs[] = $orgId;
        }
    }

    // ─── Save (create or update) ─────────────────────────────────────────────

    public function save(): void
    {
        // Sub-super-admin can never broadcast beyond its own org.
        if ($lockedOrgId = $this->lockedOrganizationId()) {
            $this->audienceScope = 'selected';
            $this->selectedOrgs  = [$lockedOrgId];
        }

        $this->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string|max:1000',
            // File is required when creating; optional (keep existing) when editing.
            'file'            => ($this->editId ? 'nullable' : 'required') . '|file|max:5120', // KB → 5 MB
            'audienceScope'   => 'required|in:all,selected',
            'selectedOrgs'    => 'required_if:audienceScope,selected|array',
            'selectedOrgs.*'  => 'exists:organizations,id',
        ], [
            'file.required'            => 'Please choose a file to upload.',
            'file.max'                 => 'The file may not be larger than 5 MB.',
            'selectedOrgs.required_if' => 'Pick at least one school, or choose "All schools".',
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
        $key = $this->file->store('super-admin/documents', 's3');
        Storage::disk('s3')->setVisibility($key, 'public');

        $doc = SuperAdminDocument::create([
            'title'          => $this->title,
            'description'    => $this->description ?: null,
            'file_path'      => $key,
            'file_name'      => $this->file->getClientOriginalName(),
            'file_size'      => $this->file->getSize(),
            'mime_type'      => $this->file->getMimeType(),
            'audience_scope' => $this->audienceScope,
            'uploaded_by'    => Auth::id() ?: null,
        ]);

        if ($this->audienceScope === 'selected') {
            $doc->organizations()->sync($this->selectedOrgs);
        }

        $this->notifyAdmins($doc);
        $this->notification()->success('Document sent', 'Schools can now view and download it.');
    }

    private function updateDocument(): void
    {
        $doc = SuperAdminDocument::findOrFail($this->editId);

        $data = [
            'title'          => $this->title,
            'description'    => $this->description ?: null,
            'audience_scope' => $this->audienceScope,
        ];

        // Replacing the file is optional; swap in the new one and drop the old.
        $oldKey = null;
        if ($this->file) {
            $key = $this->file->store('super-admin/documents', 's3');
            Storage::disk('s3')->setVisibility($key, 'public');
            $oldKey            = $doc->file_path;
            $data['file_path'] = $key;
            $data['file_name'] = $this->file->getClientOriginalName();
            $data['file_size'] = $this->file->getSize();
            $data['mime_type'] = $this->file->getMimeType();
        }

        $doc->update($data);

        if ($this->audienceScope === 'selected') {
            $doc->organizations()->sync($this->selectedOrgs);
        } else {
            $doc->organizations()->detach();
        }

        if ($oldKey) {
            try {
                Storage::disk('s3')->delete($oldKey);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->notification()->success('Document updated', 'Your changes have been saved.');
    }

    /** Target organization ids for a document (resolved from its audience). */
    private function targetOrgIds(SuperAdminDocument $doc): array
    {
        if ($doc->audience_scope === 'all') {
            return Organization::pluck('id')->all();
        }

        return $doc->organizations()->pluck('organizations.id')->all();
    }

    /**
     * Drop a bell notification into each targeted school-admin's web inbox so
     * the document actually "reaches" them. Fail-soft — a notification hiccup
     * must never lose the uploaded document.
     */
    private function notifyAdmins(SuperAdminDocument $doc): void
    {
        try {
            $orgIds = $this->targetOrgIds($doc);
            if (empty($orgIds)) {
                return;
            }

            $userIds = User::whereIn('role', self::ADMIN_ROLES)
                ->whereIn('organization_id', $orgIds)
                ->pluck('id')
                ->all();

            if (empty($userIds)) {
                return;
            }

            $notifiableType = (new User())->getMorphClass();
            $now  = now();
            $data = json_encode([
                'type'   => 'document',
                'title'  => 'New document: ' . $doc->title,
                'body'   => 'A new document is available in your Documents section.',
                'screen' => 'documents',
            ]);

            foreach (array_chunk($userIds, 500) as $chunk) {
                $rows = array_map(fn ($uid) => [
                    'id'              => (string) Str::uuid(),
                    'type'            => 'document',
                    'notifiable_type' => $notifiableType,
                    'notifiable_id'   => $uid,
                    'data'            => $data,
                    'read_at'         => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], $chunk);

                DB::table('notifications')->insert($rows);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ─── Download ──────────────────────────────────────────────────────────────

    public function downloadDocument(int $id)
    {
        $doc = SuperAdminDocument::when(
            $this->lockedOrganizationId(),
            fn ($q, $orgId) => $q->forOrganization($orgId)
        )->find($id);

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

        $doc = SuperAdminDocument::when(
            $this->lockedOrganizationId(),
            fn ($q, $orgId) => $q->forOrganization($orgId)
        )->find($this->deleteId);

        if ($doc) {
            try {
                if ($doc->file_path) {
                    Storage::disk('s3')->delete($doc->file_path);
                }
            } catch (\Throwable $e) {
                report($e);
            }
            $doc->organizations()->detach();
            $doc->delete();
            $this->notification()->success('Deleted', 'Document removed.');
        }

        $this->deleteId = null;
        $this->resetPage();
    }

    // ─── Filters ───────────────────────────────────────────────────────────────

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedFilterOrg(): void  { $this->resetPage(); }
    public function updatedFilterFrom(): void { $this->resetPage(); }
    public function updatedFilterTo(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        if ($lockedOrgId = $this->lockedOrganizationId()) {
            $this->filterOrg = (string) $lockedOrgId;
        } else {
            $this->filterOrg = '';
        }
        $this->search     = '';
        $this->filterFrom = '';
        $this->filterTo   = '';
        $this->resetPage();
    }

    public function render()
    {
        $lockedOrgId = $this->lockedOrganizationId();

        $documents = SuperAdminDocument::with('organizations:id,name')
            ->when($lockedOrgId, fn ($q) => $q->forOrganization($lockedOrgId))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', $term)
                      ->orWhere('file_name', 'like', $term)
                      ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->filterOrg, fn ($q) => $q->forOrganization((int) $this->filterOrg))
            ->when($this->filterFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->filterFrom))
            ->when($this->filterTo, fn ($q) => $q->whereDate('created_at', '<=', $this->filterTo))
            ->latest()
            ->paginate(12);

        return view('livewire.super-admin.documents', [
            'documents'     => $documents,
            'organizations' => Organization::orderBy('name')->get(['id', 'name']),
            'orgLocked'     => (bool) $lockedOrgId,
        ]);
    }
}
