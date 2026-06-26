<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\SuperAdmin\Concerns\ManagesWebsitePage;
use App\Models\CareerApplication;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Careers extends Component
{
    use WireUiActions, ManagesWebsitePage, WithPagination;

    // ─── Job openings (slide panel) ───────────────────────────────────
    public bool $showJobPanel = false;
    public ?int $jobEditIndex = null;
    public string $jobRole = '';
    public string $jobSalary = '';
    public string $jobDepartment = '';
    public string $jobLocation = '';
    public string $jobType = '';
    public ?int $pendingJobDelete = null;

    // ─── Applications / website queries ───────────────────────────────
    public ?int $viewingId = null;
    public ?int $pendingAppDelete = null;

    /** Filters */
    public string $search = '';
    public string $statusFilter = '';
    public string $filterDays = '';

    public function mount(): void
    {
        $this->slug = 'careers';
        $this->loadPage();

        // Keep only real (non-empty) job rows, cleanly 0-indexed.
        $this->meta['jobs'] = array_values(array_filter(
            $this->meta['jobs'] ?? [],
            fn($j) => !empty($j['role'] ?? ''),
        ));

        $this->activeTab = 'jobs';
    }

    protected function defaultMeta(): array
    {
        return ['jobs' => []];
    }

    protected function rowTemplates(): array
    {
        return [
            'jobs' => ['role' => '', 'department' => '', 'location' => '', 'type' => '', 'salary' => ''],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDays(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'filterDays']);
        $this->resetPage();
    }

    // ─── Job CRUD ──────────────────────────────────────────────────────

    public function openJobCreate(): void
    {
        $this->resetJobForm();
        $this->activeTab    = 'jobs';
        $this->showJobPanel = true;
    }

    public function openJobEdit(int $index): void
    {
        $job = $this->meta['jobs'][$index] ?? null;
        if (!$job) {
            return;
        }

        $this->jobEditIndex  = $index;
        $this->jobRole       = $job['role'] ?? '';
        $this->jobSalary     = $job['salary'] ?? '';
        $this->jobDepartment = $job['department'] ?? '';
        $this->jobLocation   = $job['location'] ?? '';
        $this->jobType       = $job['type'] ?? '';
        $this->resetErrorBag();
        $this->showJobPanel  = true;
    }

    public function closeJobPanel(): void
    {
        $this->showJobPanel = false;
        $this->resetJobForm();
    }

    protected function resetJobForm(): void
    {
        $this->reset(['jobEditIndex', 'jobRole', 'jobSalary', 'jobDepartment', 'jobLocation', 'jobType']);
        $this->resetErrorBag();
    }

    public function saveJob(): void
    {
        $this->validate([
            'jobRole'       => 'required|string|max:255',
            'jobSalary'     => 'nullable|string|max:255',
            'jobDepartment' => 'nullable|string|max:255',
            'jobLocation'   => 'nullable|string|max:255',
            'jobType'       => 'nullable|string|max:255',
        ], [
            'jobRole.required' => 'Please enter the job role / title.',
        ]);

        $row = [
            'role'       => trim($this->jobRole),
            'salary'     => trim($this->jobSalary),
            'department' => trim($this->jobDepartment),
            'location'   => trim($this->jobLocation),
            'type'       => trim($this->jobType),
        ];

        if ($this->jobEditIndex !== null && isset($this->meta['jobs'][$this->jobEditIndex])) {
            $this->meta['jobs'][$this->jobEditIndex] = $row;
            $message = 'Job opening updated.';
        } else {
            $this->meta['jobs'][] = $row;
            $message = 'Job opening added.';
        }

        $this->persistJobs();
        $this->notification()->success('Saved', $message);
        $this->closeJobPanel();
    }

    public function confirmJobDelete(int $index): void
    {
        $this->pendingJobDelete = $index;
    }

    public function cancelJobDelete(): void
    {
        $this->pendingJobDelete = null;
    }

    public function deleteJob(): void
    {
        if ($this->pendingJobDelete !== null && isset($this->meta['jobs'][$this->pendingJobDelete])) {
            unset($this->meta['jobs'][$this->pendingJobDelete]);
            $this->meta['jobs'] = array_values($this->meta['jobs']);
            $this->persistJobs();
            $this->notification()->success('Deleted', 'Job opening removed.');
        }
        $this->pendingJobDelete = null;
    }

    protected function persistJobs(): void
    {
        $this->meta['jobs'] = array_values($this->meta['jobs'] ?? []);

        WebsitePage::updateOrCreate(
            ['slug' => $this->slug],
            ['metadata' => $this->meta, 'last_updated' => now()->toDateString()],
        );
    }

    // ─── Applications ─────────────────────────────────────────────────

    public function viewApplication(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeApplication(): void
    {
        $this->viewingId = null;
    }

    /** Open the document inline in a new browser tab (a "second screen"). */
    public function viewDocument(int $id): void
    {
        $app = CareerApplication::find($id);

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
        $app = CareerApplication::find($id);

        if (!$app || !$app->document_path) {
            $this->notification()->error('No document attached for this application.');
            return null;
        }

        if (!Storage::disk('s3')->exists($app->document_path)) {
            $this->notification()->error('File missing on storage.');
            return null;
        }

        $ext      = pathinfo($app->document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = 'application-' . str($app->full_name)->slug() . '.' . $ext;

        $url = Storage::disk('s3')->temporaryUrl(
            $app->document_path,
            now()->addMinutes(5),
            ['ResponseContentDisposition' => 'attachment; filename="' . $filename . '"'],
        );

        return $this->redirect($url);
    }

    public function toggleReviewed(int $id): void
    {
        $app = CareerApplication::find($id);
        if ($app) {
            $app->status = $app->status === 'reviewed' ? 'new' : 'reviewed';
            $app->save();
            $this->notification()->success('Updated', 'Application marked as ' . $app->status . '.');
        }
        // Close the panel so the refreshed listing is shown immediately.
        $this->viewingId = null;
    }

    public function confirmDeleteApplication(int $id): void
    {
        $this->pendingAppDelete = $id;
    }

    public function cancelDeleteApplication(): void
    {
        $this->pendingAppDelete = null;
    }

    public function deleteApplication(): void
    {
        $app = CareerApplication::find($this->pendingAppDelete);
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
        $this->pendingAppDelete = null;
    }

    public function render()
    {
        $applications = CareerApplication::query()
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->filterDays, fn($q) => $q->where('created_at', '>=', now()->subDays((int) $this->filterDays)))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn($w) => $w->where('full_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('job_role', 'like', $term));
            })
            ->latest()
            ->paginate(15);

        $viewing = $this->viewingId ? CareerApplication::find($this->viewingId) : null;

        $analytics = [
            'total'      => CareerApplication::count(),
            'new'        => CareerApplication::where('status', 'new')->count(),
            'reviewed'   => CareerApplication::where('status', 'reviewed')->count(),
            'this_month' => CareerApplication::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];

        return view('livewire.super-admin.website.careers', [
            'jobs'         => $this->meta['jobs'] ?? [],
            'applications' => $applications,
            'viewing'      => $viewing,
            'analytics'    => $analytics,
            'newCount'     => $analytics['new'],
        ]);
    }
}
