<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class More extends Component
{
    /** Tiles for items moved out of the main sidebar — keep titles + icons stable. */
    public array $items = [
        ['title' => 'Users',               'route' => 'admin.users',                'icon' => 'user-group'],
        ['title' => 'Admissions',          'route' => 'admin.admissions',           'icon' => 'user-plus'],
        ['title' => 'Lists',               'route' => 'admin.lists',                'icon' => 'queue-list'],
        ['title' => 'Rules & Regulation',  'route' => 'admin.rules-and-regulation', 'icon' => 'clipboard'],
        ['title' => 'Contact Admin',       'route' => 'admin.contact-admin',        'icon' => 'chat-bubble-left'],
        ['title' => 'About App',           'route' => 'admin.about-app',            'icon' => 'information-circle'],
        ['title' => 'Rate LMS',            'route' => 'admin.rate-lms',             'icon' => 'star'],
        ['title' => 'Terms of Use',        'route' => 'admin.terms-and-condition',  'icon' => 'document-text'],
        ['title' => 'Privacy Policy',      'route' => 'admin.privacy-policy',       'icon' => 'lock-closed'],
        ['title' => 'Terms And Condition', 'route' => 'admin.terms-of-use',         'icon' => 'document-text'],
    ];

    public $organization = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? auth()->user()?->organization;
    }

    public function render()
    {
        return view('livewire.admin.more');
    }
}
