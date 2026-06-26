<?php

namespace App\Livewire\Website;

use App\Models\WebsiteContact;
use Livewire\Component;

class ContactUs extends Component
{

    public $full_name, $school_name, $phone, $email;
    public $showSuccess = false;

    public function submit()
    {
        $this->validate([
            'full_name' => 'required',
            'school_name' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
        ]);

        WebsiteContact::create([
            'full_name' => $this->full_name,
            'school_name' => $this->school_name,
            'phone_number' => $this->phone,
            'email' => $this->email,
        ]);

        $this->reset(['full_name', 'school_name', 'phone', 'email']);
        $this->showSuccess = true;
    }
    public function render()
    {
        return view('livewire.website.contact-us');
    }
}
