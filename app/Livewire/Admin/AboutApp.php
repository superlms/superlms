<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AboutApp as AboutAppModel;

class AboutApp extends Component
{
    public $aboutApp = null;
    public array $contactDetails = [];
    public ?string $lastUpdated = null;

    public function mount(): void
    {
        $this->aboutApp = AboutAppModel::first();
        $this->lastUpdated = $this->aboutApp?->updated_at
            ? $this->aboutApp->updated_at->format('d F Y')
            : null;
    }

    public function render()
    {
        return view('livewire.admin.about-app');
    }
}
