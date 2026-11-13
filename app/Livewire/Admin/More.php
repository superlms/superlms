<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class More extends Component
{
    /** Tiles for items moved out of the main sidebar — keep titles + icons stable. */
    public array $items = [
        ['title' => 'Profile',             'route' => 'admin.profile',              'icon' => 'user'],
        ['title' => 'Users',               'route' => 'admin.users',                'icon' => 'user-group'],
        ['title' => 'Admissions',          'route' => 'admin.admissions',           'icon' => 'user-plus'],
        ['title' => 'Website Data',         'route' => 'admin.website-data',         'icon' => 'globe-alt'],
        // Documents moved to the sidebar (under Lists); Exam Copy came here from it.
        ['title' => 'Exam Copy',            'route' => 'admin.exam-copy',            'icon' => 'document-text'],
        // The messaging wallet, and WhatsApp / email to the school's students and teachers.
        ['title' => 'Messaging',            'route' => 'admin.messaging',            'icon' => 'chat-bubble-left-right'],
        // A push notification to the school's students and teachers (2026-11-13; the Notification module's).
        ['title' => 'Push Notification',    'route' => 'admin.push-notification',    'icon' => 'megaphone'],
        // Only their pages so far (2026-11-04); what they do is still to be built.
        ['title' => 'Reception',            'route' => 'admin.reception',            'icon' => 'building-office'],
        ['title' => 'Gatepass',             'route' => 'admin.gatepass',             'icon' => 'ticket'],
        ['title' => 'Complaints',           'route' => 'admin.complaints',           'icon' => 'exclamation-circle'],
        // Credit is no longer a tile here (2026-10-09); its page and route stay.
        ['title' => 'Rules & Regulation',  'route' => 'admin.rules-and-regulation', 'icon' => 'clipboard'],
        ['title' => 'Contact Admin',       'route' => 'admin.contact-admin',        'icon' => 'chat-bubble-left'],
        ['title' => 'About App',           'route' => 'admin.about-app',            'icon' => 'information-circle'],
        ['title' => 'Rate LMS',            'route' => 'admin.rate-lms',             'icon' => 'star'],
        ['title' => 'Terms & Conditions',  'route' => 'admin.terms-and-condition',  'icon' => 'document-text'],
        ['title' => 'Privacy Policy',      'route' => 'admin.privacy-policy',       'icon' => 'lock-closed'],
        ['title' => 'Terms Of Use',        'route' => 'admin.terms-of-use',         'icon' => 'document-text'],
        ['title' => 'Setting',             'route' => 'admin.setting',              'icon' => 'cog-6-tooth'],
    ];

    public $organization = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? auth()->user()?->organization;

        $user = auth()->user();

        // A tile of a module the school has not been given is left out, as the
        // sidebar leaves it out (Exam Copy belongs to one; the rest are core).
        $this->items = array_values(array_filter(
            $this->items,
            fn (array $item) => \App\Support\ModuleAccess::allows($user?->organization, $item['route']),
        ));

        // A sub-admin reaches this screen for Profile alone, so drop the tiles
        // their permissions would bounce them straight back off.
        if ($user?->role === 'sub-admin') {
            $this->items = array_values(array_filter(
                $this->items,
                fn (array $item) => $user->canAccessAdminRoute($item['route']),
            ));
        }
    }

    public function render()
    {
        return view('livewire.admin.more');
    }
}
