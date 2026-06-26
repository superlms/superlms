<?php

namespace App\Livewire\Website;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\Admin\RateLms;
use Livewire\Component;

class HeroSection extends Component
{
    public int $schoolCount = 0;
    public int $studentCount = 0;
    public string $avgRating = '0';

    public function mount(): void
    {
        $this->schoolCount = Organization::where('status', true)->count();
        $this->studentCount = StudentDetail::count();
        $this->avgRating = number_format(RateLms::avg('rating') ?: 4.5, 1);
    }

    public function render()
    {
        return view('livewire.website.hero-section');
    }
}
