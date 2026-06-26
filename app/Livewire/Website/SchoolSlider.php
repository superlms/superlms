<?php

namespace App\Livewire\Website;

use App\Models\Organization;
use Livewire\Component;


class SchoolSlider extends Component
{
    public $organizations;

    public function mount()
    {
        $this->organizations = Organization::where('status', true)->take(4)->get();
    }
    
    public function render()
    {
        return view('livewire.website.school-slider');
    }
}
