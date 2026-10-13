<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\SchoolWebsite;
use Livewire\Component;
use Livewire\WithPagination;

class PortalWebsite extends Component
{
    use WithPagination;

    public string $search = '';

    /** '' | published | draft | none — the Students list's filter bar. */
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $siteOrgIds      = SchoolWebsite::pluck('organization_id');
        $publishedOrgIds = SchoolWebsite::where('status', true)->pluck('organization_id');

        $organizations = Organization::query()
            ->when($this->search, fn($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter === 'published', fn ($q) => $q->whereIn('id', $publishedOrgIds))
            ->when($this->statusFilter === 'draft', fn ($q) => $q->whereIn('id', $siteOrgIds)->whereNotIn('id', $publishedOrgIds))
            ->when($this->statusFilter === 'none', fn ($q) => $q->whereNotIn('id', $siteOrgIds))
            ->orderBy('name')
            ->paginate(15);

        // Map organization_id => SchoolWebsite for the current page.
        $sites = SchoolWebsite::whereIn('organization_id', $organizations->pluck('id'))
            ->get()
            ->keyBy('organization_id');

        // The header's figures, as the Students header has its own.
        $totalSchools = Organization::count();
        $stats = [
            'total'       => $totalSchools,
            'published'   => $publishedOrgIds->unique()->count(),
            'draft'       => $siteOrgIds->unique()->diff($publishedOrgIds)->count(),
            'not_created' => max(0, $totalSchools - $siteOrgIds->unique()->count()),
        ];

        return view('livewire.super-admin.portal-website', [
            'organizations' => $organizations,
            'sites'         => $sites,
            'stats'         => $stats,
        ]);
    }
}
