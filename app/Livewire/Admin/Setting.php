<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Setting extends Component
{
    /** Placeholder screen — the settings content is still to be decided. */
    public $organization = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? auth()->user()?->organization_id;
    }

    public function render()
    {
        return view('livewire.admin.setting');
    }
}
