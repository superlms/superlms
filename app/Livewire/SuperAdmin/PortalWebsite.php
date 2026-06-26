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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $organizations = Organization::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        // Map organization_id => SchoolWebsite for the current page.
        $sites = SchoolWebsite::whereIn('organization_id', $organizations->pluck('id'))
            ->get()
            ->keyBy('organization_id');

        return view('livewire.super-admin.portal-website', [
            'organizations' => $organizations,
            'sites'         => $sites,
        ]);
    }
}
