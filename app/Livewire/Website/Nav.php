<?php

namespace App\Livewire\Website;

use Livewire\Component;

class Nav extends Component
{
     public $mobileMenuOpen = false; 
     
    public function render()
    {
        return view('livewire.website.nav');
    }
}
