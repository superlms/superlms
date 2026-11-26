<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Concerns\PicksManyDocuments;
use App\Models\Organization;
use App\Models\SuperAdmin\SuperAdminDocument;
use App\Models\SuperAdmin\SuperAdminDocumentFile;
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
 *
 * A document is a title, a description and one file or several (user's ask,
 * 2026-11-26): the title comes first, then the description, then the files —
 * picked one by one or many at once (PicksManyDocuments), each with a name of
 * its own. It is one row in the list; View shows every file in it. The
 * schools' admins get one bell notification for it.
 */
class Documents extends Component
{
    use WithFileUploads, WithPagination, WireUiActions, PicksManyDocuments;

    /** School-admin accounts live on the users table under these roles. */
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    // ─── Add / edit slide-in panel ───────────────────────────────────────────
    public bool   $showPanel     = false;
    public ?int   $editId        = null;
    public string $title         = '';
    public string $description   = '';
    /** Editing: the document's files kept, in order — [['id', 'name', 'file_name', 'size'], …]. New ones go on $docFiles. */
    public array  $keptFiles     = [];
    public string $audienceScope = 'all';   // private | all | selected
    public array  $selectedOrgs  = [];

    // ─── View slide-in: one document and all its files ───────────────────────
    public ?int $viewId = null;

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
        $this->viewId    = null;
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
        $this->viewId        = null;
        $this->editId        = $doc->id;
        $this->title         = $doc->title;
        $this->description   = (string) $doc->description;
        $this->keptFiles     = self::fileRows($doc)->map(fn (SuperAdminDocumentFile $f) => [
            'id'        => $f->id,
            'name'      => (string) $f->name,
            'file_name' => (string) $f->file_name,
            'size'      => $f->readable_size,
        ])->values()->all();
        $this->audienceScope = $doc->audience_scope;
        $this->selectedOrgs  = $doc->organizations->pluck('id')->all();

        if (($lockedOrgId = $this->lockedOrganizationId()) && $this->audienceScope !== 'private') {
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

    /** Take a kept file out of the document being edited (it goes when the document is saved). */
    public function removeKeptFile(int $index): void
    {
        unset($this->keptFiles[$index]);
        $this->keptFiles = array_values($this->keptFiles);
        $this->resetErrorBag(['docFiles']);
        $this->resetValidation('keptFiles.*');
    }

    /**
     * A document's files as rows of super_admin_document_files — one from
     * before that table gets its own file as its first row here.
     */
    private static function fileRows(SuperAdminDocument $doc): \Illuminate\Support\Collection
    {
        $files = $doc->files()->get();
        if ($files->isEmpty() && $doc->file_path) {
            $files = collect([$doc->files()->create([
                'name'       => $doc->title,
                'file_path'  => $doc->file_path,
                'file_name'  => $doc->file_name ?: basename($doc->file_path),
                'file_size'  => (int) $doc->file_size,
                'mime_type'  => $doc->mime_type,
                'sort_order' => 0,
            ])]);
        }

        return $files;
    }

    private function resetForm(): void
    {
        $this->reset(['title', 'description', 'keptFiles', 'editId']);
        $this->resetManyDocs();
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
        // Sub-super-admin can never broadcast beyond its own org — unless the
        // document is being kept private, which reaches no school at all.
        if (($lockedOrgId = $this->lockedOrganizationId()) && $this->audienceScope !== 'private') {
            $this->audienceScope = 'selected';
            $this->selectedOrgs  = [$lockedOrgId];
        }

        $audience = [
            'audienceScope'   => 'required|in:private,all,selected',
            'selectedOrgs'    => 'required_if:audienceScope,selected|array',
            'selectedOrgs.*'  => 'exists:organizations,id',
        ];
        $messages = ['selectedOrgs.required_if' => 'Pick at least one school, or choose "All schools".'];

        // Title first, then the description, then the files — each file with its own name.
        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ] + $this->manyDocsRules() + $audience;
        $named = [
            'title.required'         => 'Give the document a title.',
            'docTitles.*.required'   => 'Give this file a name.',
            'keptFiles.*.name.required' => 'Give this file a name.',
            'keptFiles.*.name.max'   => 'Keep the name to 255 characters.',
        ];

        if ($this->editId) {
            // Editing: the files kept and the new ones together — at least one, at most maxDocs().
            $rules['docFiles']         = 'array|max:' . max(0, static::maxDocs() - count($this->keptFiles));
            $rules['keptFiles']        = 'array';
            $rules['keptFiles.*.name'] = 'required|string|max:255';
            $this->validate($rules, $named + $this->manyDocsMessages() + $messages
                + ['docFiles.max' => 'Up to ' . static::maxDocs() . ' files in a document.']);

            if (count($this->keptFiles) + count($this->docFiles) === 0) {
                $this->addError('docFiles', 'Keep at least one file, or add one.');
                return;
            }

            if ($this->updateDocument()) {
                $this->closePanel();
            }
            $this->resetPage();
            return;
        }

        $this->validate($rules, $named + $this->manyDocsMessages() + $messages);

        if ($this->createDocument()) {
            $this->closePanel();
        }
        $this->resetPage();
    }

    /**
     * Put every new file on S3 first: all of them or none — a file that does
     * not get there leaves the panel open with the reason, and the ones that
     * did are taken off again. [index => key], or null when one failed.
     */
    private function storeNewFiles(): ?array
    {
        $stored = [];
        $failed = [];

        foreach ($this->docFiles as $i => $file) {
            try {
                $stored[$i] = $this->storeDoc($file, 'super-admin/documents');
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $file->getClientOriginalName() . ': ' . Str::limit($e->getMessage(), 120);
            }
        }

        if ($failed) {
            $this->dropKeys(array_values($stored));
            $this->addError('docFiles', 'Not saved — ' . implode('; ', $failed));
            $this->notification()->error(count($failed) === 1 ? 'A file was not saved' : count($failed) . ' files were not saved', 'They are still in the panel — try Save again.');

            return null;
        }

        return $stored;
    }

    /** The rows for the new files, after $sort. */
    private function newFileRows(array $stored, int $sort): array
    {
        $rows = [];
        foreach ($this->docFiles as $i => $file) {
            $rows[] = [
                'name'       => trim((string) ($this->docTitles[$i] ?? '')) ?: static::titleFromFileName($file->getClientOriginalName()),
                'file_path'  => $stored[$i],
                'file_name'  => $file->getClientOriginalName(),
                'file_size'  => (int) $file->getSize(),
                'mime_type'  => $file->getMimeType(),
                'sort_order' => $sort++,
            ];
        }

        return $rows;
    }

    /** The document's own file columns follow its first file. */
    private static function firstFileColumns(SuperAdminDocument $doc): array
    {
        $first = $doc->files()->first();

        return $first ? [
            'file_path' => $first->file_path,
            'file_name' => $first->file_name,
            'file_size' => (int) $first->file_size,
            'mime_type' => $first->mime_type,
        ] : [];
    }

    private function dropKeys(array $keys): void
    {
        foreach (array_filter(array_unique($keys)) as $key) {
            try {
                Storage::disk('s3')->delete($key);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** One document: the title, the description and every file picked, each with its name. */
    private function createDocument(): bool
    {
        $stored = $this->storeNewFiles();
        if ($stored === null) {
            return false;
        }

        try {
            $doc = DB::transaction(function () use ($stored) {
                $rows  = $this->newFileRows($stored, 0);
                $first = $rows[0];

                $doc = SuperAdminDocument::create([
                    'title'          => trim($this->title),
                    'description'    => trim($this->description) ?: null,
                    'file_path'      => $first['file_path'],
                    'file_name'      => $first['file_name'],
                    'file_size'      => $first['file_size'],
                    'mime_type'      => $first['mime_type'],
                    'audience_scope' => $this->audienceScope,
                    'uploaded_by'    => Auth::id() ?: null,
                ]);
                $doc->files()->createMany($rows);

                if ($this->audienceScope === 'selected') {
                    $doc->organizations()->sync($this->selectedOrgs);
                }

                return $doc;
            });
        } catch (\Throwable $e) {
            report($e);
            $this->dropKeys(array_values($stored));
            $this->notification()->error('Save failed', Str::limit($e->getMessage(), 160));

            return false;
        }

        $n     = count($stored);
        $files = $n === 1 ? '' : ' with ' . $n . ' files';

        // A private document stays here: no school sees it, nobody is notified.
        if ($this->audienceScope === 'private') {
            $this->notification()->success('Document saved' . $files, 'Kept private — no school can see it.');
        } else {
            $this->notifyAdmins([$doc]);
            $this->notification()->success('Document sent' . $files, 'Schools can now view and download it.');
        }

        return true;
    }

    /**
     * The title, description and who gets it; the kept files take their new
     * names and order, the ones taken out go, the new ones join at the end.
     */
    private function updateDocument(): bool
    {
        $doc = SuperAdminDocument::find($this->editId);
        if (!$doc) {
            $this->notification()->error('Not found', 'This document is no longer available.');
            return true;
        }

        $stored = $this->storeNewFiles();
        if ($stored === null) {
            return false;
        }

        try {
            $gone = DB::transaction(function () use ($doc, $stored) {
                $keep = array_map(fn ($f) => (int) $f['id'], $this->keptFiles);
                $old  = $doc->files()->get();
                $gone = $old->whereNotIn('id', $keep)->pluck('file_path')->all();
                $doc->files()->whereNotIn('id', $keep ?: [0])->delete();

                $sort = 0;
                foreach ($this->keptFiles as $f) {
                    $doc->files()->whereKey((int) $f['id'])->update(['name' => trim((string) $f['name']), 'sort_order' => $sort++]);
                }
                $doc->files()->createMany($this->newFileRows($stored, $sort));

                $doc->update([
                    'title'          => trim($this->title),
                    'description'    => trim($this->description) ?: null,
                    'audience_scope' => $this->audienceScope,
                ] + self::firstFileColumns($doc));

                if ($this->audienceScope === 'selected') {
                    $doc->organizations()->sync($this->selectedOrgs);
                } else {
                    // 'all' resolves without the pivot; 'private' must reach nobody.
                    $doc->organizations()->detach();
                }

                // A key still used by a kept file (a document from before) stays.
                return array_diff($gone, $doc->files()->pluck('file_path')->all());
            });
        } catch (\Throwable $e) {
            report($e);
            $this->dropKeys(array_values($stored));
            $this->notification()->error('Save failed', Str::limit($e->getMessage(), 160));

            return false;
        }

        $this->dropKeys($gone);

        $this->notification()->success(
            'Document updated',
            $this->audienceScope === 'private'
                ? 'Kept private — no school can see it.'
                : 'Your changes have been saved.'
        );

        return true;
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
     * the documents actually "reach" them — one for documents added together,
     * which all go to the same schools. Fail-soft — a notification hiccup
     * must never lose the uploaded documents.
     *
     * @param SuperAdminDocument[] $docs
     */
    private function notifyAdmins(array $docs): void
    {
        $doc = $docs[0] ?? null;
        if (!$doc) {
            return;
        }

        $title = count($docs) === 1
            ? 'New document: ' . $doc->title
            : count($docs) . ' new documents: ' . Str::limit(collect($docs)->pluck('title')->join(', '), 120);

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
                'title'  => $title,
                'body'   => count($docs) === 1
                    ? 'A new document is available in your Documents section.'
                    : count($docs) . ' new documents are available in your Documents section.',
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

    // ─── View: one document and every file in it ───────────────────────────────

    /** The documents this user sees: all of them, or a locked school's plus their own private ones. */
    private function visibleDocuments()
    {
        $lockedOrgId = $this->lockedOrganizationId();

        return SuperAdminDocument::query()->when($lockedOrgId, function ($q) use ($lockedOrgId) {
            $q->where(function ($w) use ($lockedOrgId) {
                $w->where('audience_scope', 'all')
                  ->orWhere(fn ($p) => $p->where('audience_scope', 'private')
                      ->where('uploaded_by', Auth::id()))
                  ->orWhereHas('organizations', fn ($o) => $o->where('organizations.id', $lockedOrgId));
            });
        });
    }

    public function view(int $id): void
    {
        if ($this->visibleDocuments()->whereKey($id)->exists()) {
            $this->viewId = $id;
        }
    }

    public function closeView(): void
    {
        $this->viewId = null;
    }

    public function viewedDocument(): ?SuperAdminDocument
    {
        return $this->viewId
            ? $this->visibleDocuments()->with(['organizations:id,name', 'files'])->find($this->viewId)
            : null;
    }

    // ─── Download ──────────────────────────────────────────────────────────────

    /** A document's first file (the row's Download, for a document of one file). */
    public function downloadDocument(int $id)
    {
        $doc  = $this->visibleDocuments()->find($id);
        $file = $doc?->fileList()->first();

        return $this->sendFile($file);
    }

    /** One file of a document, under the name it was given. */
    public function downloadFile(int $fileId)
    {
        $file = SuperAdminDocumentFile::find($fileId);
        if ($file && !$this->visibleDocuments()->whereKey($file->super_admin_document_id)->exists()) {
            $file = null;
        }

        return $this->sendFile($file);
    }

    private function sendFile(?SuperAdminDocumentFile $file)
    {
        if (!$file || !$file->file_path) {
            $this->notification()->error('Not found', 'This document is no longer available.');
            return;
        }

        try {
            return Storage::disk('s3')->download($file->file_path, $file->downloadName());
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
            // Every file in it goes with it.
            $keys = $doc->files()->pluck('file_path')->push($doc->file_path)->all();
            $doc->files()->delete();
            $doc->organizations()->detach();
            $doc->delete();
            $this->dropKeys($keys);
            $this->notification()->success('Deleted', 'Document removed.');
        }

        if ($this->viewId === $this->deleteId) {
            $this->viewId = null;
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

        // Everything this school can see, plus my own private uploads; each with its files.
        $documents = $this->visibleDocuments()
            ->with(['organizations:id,name', 'files'])
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', $term)
                      ->orWhere('file_name', 'like', $term)
                      ->orWhere('description', 'like', $term)
                      ->orWhereHas('files', fn ($f) => $f->where(fn ($g) => $g->where('name', 'like', $term)->orWhere('file_name', 'like', $term)));
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
