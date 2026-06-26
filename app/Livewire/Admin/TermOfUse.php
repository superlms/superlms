<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\TermOfUse as TermOfUseModel;
use App\Models\AboutApp as AboutAppModel;

class TermOfUse extends Component
{
    public ?TermOfUseModel $policy = null;
    public array $sections = [];
    public ?string $lastUpdated = null;
    public $aboutApp = null;

    public function mount(): void
    {
        $this->aboutApp = AboutAppModel::first();

        $this->policy = TermOfUseModel::latest()->first();

        if ($this->policy) {
            $meta = $this->policy->metadata ?? [];
            $this->sections    = $meta['sections'] ?? [];
            $this->lastUpdated = $this->policy->last_updated
                ? \Carbon\Carbon::parse($this->policy->last_updated)->format('d F Y')
                : null;
        }
    }

    public function render()
    {
        return view('livewire.admin.term-of-use');
    }
}