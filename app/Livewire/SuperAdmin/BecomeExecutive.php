<?php

namespace App\Livewire\SuperAdmin;

use App\Models\ExecutiveApplication;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class BecomeExecutive extends Component
{
    use WireUiActions, WithPagination;

    /** Allowed statuses. */
    public const STATUSES = ['new', 'contacted', 'approved', 'rejected'];

    /** Filters */
    public string $statusFilter = '';
    public string $search = '';
    public string $filterDays = '';

    /** Application open in the detail modal. */
    public ?int $viewingId = null;

    /** Editable status + remark bound in the modal. */
    public string $editStatus = 'new';
    public string $editRemark = '';

    /** Pending delete confirmation. */
    public ?int $pendingDelete = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDays(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterDays', 'statusFilter']);
        $this->resetPage();
    }

    // ─── View / status ────────────────────────────────────────────────

    public function viewApplication(int $id): void
    {
        $app = ExecutiveApplication::findOrFail($id);
        $this->viewingId  = $app->id;
        $this->editStatus = $app->status ?: 'new';
        $this->editRemark = $app->admin_remark ?? '';
    }

    public function closeApplication(): void
    {
        $this->viewingId = null;
        $this->editStatus = 'new';
        $this->editRemark = '';
    }

    public function saveStatus(): void
    {
        $this->validate([
            'editStatus' => 'required|in:' . implode(',', self::STATUSES),
            'editRemark' => 'nullable|string|max:2000',
        ]);

        $app = ExecutiveApplication::find($this->viewingId);
        if ($app) {
            $app->update([
                'status'       => $this->editStatus,
                'admin_remark' => $this->editRemark ?: null,
            ]);
            $this->notification()->success('Saved', 'Status updated to ' . $this->editStatus . '.');
        }

        // Close the panel so the refreshed listing is shown immediately.
        $this->closeApplication();
    }

    /** Open the document inline in a new browser tab (a "second screen"). */
    public function viewDocument(int $id): void
    {
        $app = ExecutiveApplication::find($id);

        if (!$app || !$app->document_path) {
            $this->notification()->error('No document attached for this application.');
            return;
        }

        if (!Storage::disk('s3')->exists($app->document_path)) {
            $this->notification()->error('File missing on storage.');
            return;
        }

        $url = Storage::disk('s3')->temporaryUrl($app->document_path, now()->addMinutes(10));

        $this->dispatch('open-document', url: $url);
    }

    public function downloadDocument(int $id): mixed
    {
        $app = ExecutiveApplication::find($id);

        if (!$app || !$app->document_path) {
            $this->notification()->error('No document attached for this application.');
            return null;
        }

        if (!Storage::disk('s3')->exists($app->document_path)) {
            $this->notification()->error('File missing on storage.');
            return null;
        }

        $ext      = pathinfo($app->document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = 'executive-' . str($app->full_name)->slug() . '.' . $ext;

        $url = Storage::disk('s3')->temporaryUrl(
            $app->document_path,
            now()->addMinutes(5),
            ['ResponseContentDisposition' => 'attachment; filename="' . $filename . '"'],
        );

        return $this->redirect($url);
    }

    // ─── Delete ───────────────────────────────────────────────────────

    public function confirmDeleteApplication(int $id): void
    {
        $this->pendingDelete = $id;
    }

    public function cancelDeleteApplication(): void
    {
        $this->pendingDelete = null;
    }

    public function deleteApplication(): void
    {
        $app = ExecutiveApplication::find($this->pendingDelete);
        if ($app) {
            if ($app->document_path && Storage::disk('s3')->exists($app->document_path)) {
                Storage::disk('s3')->delete($app->document_path);
            }
            if ($this->viewingId === $app->id) {
                $this->viewingId = null;
            }
            $app->delete();
            $this->notification()->success('Deleted', 'Application removed.');
        }
        $this->pendingDelete = null;
    }

    public function render()
    {
        $applications = ExecutiveApplication::query()
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->filterDays, fn($q) => $q->where('created_at', '>=', now()->subDays((int) $this->filterDays)))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn($w) => $w->where('full_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('qualification', 'like', $term));
            })
            ->latest()
            ->paginate(15);

        $viewing = $this->viewingId ? ExecutiveApplication::find($this->viewingId) : null;

        $analytics = [
            'total'      => ExecutiveApplication::count(),
            'pending'    => ExecutiveApplication::where('status', 'new')->count(),
            'updated'    => ExecutiveApplication::where('status', '!=', 'new')->count(),
            'this_month' => ExecutiveApplication::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];

        return view('livewire.super-admin.website.become-executive', [
            'applications' => $applications,
            'viewing'      => $viewing,
            'analytics'    => $analytics,
            'statuses'     => self::STATUSES,
        ]);
    }
}
