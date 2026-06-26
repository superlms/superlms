<?php

namespace App\Livewire\Admin;

use App\Models\PrivacyPolicy as PrivacyPolicyModel;
use App\Models\AboutApp as AboutAppModel;
use Livewire\Component;

class PrivacyPolicy extends Component
{
    public ?PrivacyPolicyModel $policy = null;
    public array $sections = [];
    public ?string $lastUpdated = null;
    public $aboutApp = null;

    public function mount(): void
    {
        $this->aboutApp = AboutAppModel::first();

        $this->policy = PrivacyPolicyModel::latest()->first();

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
        return view('livewire.admin.privacy-policy');
    }
}