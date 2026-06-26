<?php

namespace App\Livewire\Admin;

use Livewire\Component;

/**
 * Admin → More → Lists landing page.
 * Placeholder ("Coming Soon") scaffold — fleshed out in a future update.
 */
class Lists extends Component
{
    public $organization = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? auth()->user()?->organization_id;
    }

    public function render()
    {
        return view('livewire.admin.lists');
    }
}
