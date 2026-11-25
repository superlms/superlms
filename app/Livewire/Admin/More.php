<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class More extends Component
{
    /**
     * Tiles for items moved out of the main sidebar — keep titles + icons stable.
     * Three rows in the user's order (2026-11-25); `row` keeps each tile on its
     * own row on a wide screen even when a school lacks a module. Profile is no
     * tile: it is the sidebar's user card at the bottom.
     */
    public array $items = [
        // Row 1 — messaging, notices, the front desk, logins and the boards.
        // The messaging wallet, and WhatsApp / email to the school's students and teachers.
        ['title' => 'Messaging',            'route' => 'admin.messaging',            'icon' => 'chat-bubble-left-right', 'row' => 1],
        // A push notification to the school's students and teachers (2026-11-13; the Notification module's).
        ['title' => 'Push Notification',    'route' => 'admin.push-notification',    'icon' => 'megaphone',              'row' => 1],
        // The visitor book, a student's gate pass, and complaints about students (2026-11-17).
        ['title' => 'Reception',            'route' => 'admin.reception',            'icon' => 'building-office',        'row' => 1],
        ['title' => 'Gatepass',             'route' => 'admin.gatepass',             'icon' => 'ticket',                 'row' => 1],
        ['title' => 'Users',                'route' => 'admin.users',                'icon' => 'user-group',             'row' => 1],
        // The Class 9–12 students and their details (2026-11-25), as the website's Boards login shows them.
        ['title' => 'Boards',               'route' => 'admin.boards',               'icon' => 'academic-cap',           'row' => 1],

        // Row 2.
        ['title' => 'Admissions',           'route' => 'admin.admissions',           'icon' => 'user-plus',              'row' => 2],
        ['title' => 'Website Data',         'route' => 'admin.website-data',         'icon' => 'globe-alt',              'row' => 2],
        // Documents moved to the sidebar (under Lists); Exam Copy came here from it.
        ['title' => 'Exam Copy',            'route' => 'admin.exam-copy',            'icon' => 'document-text',          'row' => 2],
        ['title' => 'Complaints',           'route' => 'admin.complaints',           'icon' => 'exclamation-circle',     'row' => 2],
        ['title' => 'Contact Admin',        'route' => 'admin.contact-admin',        'icon' => 'chat-bubble-left',       'row' => 2],
        ['title' => 'Setting',              'route' => 'admin.setting',              'icon' => 'cog-6-tooth',            'row' => 2],

        // Row 3 — rules, the app, and the policies.
        // Credit is no longer a tile here (2026-10-09); its page and route stay.
        ['title' => 'Rules & Regulation',   'route' => 'admin.rules-and-regulation', 'icon' => 'clipboard',              'row' => 3],
        ['title' => 'About App',            'route' => 'admin.about-app',            'icon' => 'information-circle',     'row' => 3],
        ['title' => 'Rate LMS',             'route' => 'admin.rate-lms',             'icon' => 'star',                   'row' => 3],
        ['title' => 'Terms & Conditions',   'route' => 'admin.terms-and-condition',  'icon' => 'document-text',          'row' => 3],
        ['title' => 'Privacy Policy',       'route' => 'admin.privacy-policy',       'icon' => 'lock-closed',            'row' => 3],
        ['title' => 'Terms Of Use',         'route' => 'admin.terms-of-use',         'icon' => 'document-text',          'row' => 3],
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

        // Drop the tiles a sub-admin's permissions would bounce them straight
        // back off.
        if ($user?->role === 'sub-admin') {
            $this->items = array_values(array_filter(
                $this->items,
                fn (array $item) => $user->canAccessAdminRoute($item['route']),
            ));
        }

        // A row left with no tile is closed up, so no empty band shows.
        $rows = array_flip(array_values(array_unique(array_column($this->items, 'row'))));
        foreach ($this->items as &$item) {
            $item['row'] = $rows[$item['row']] + 1;
        }
        unset($item);
    }

    public function render()
    {
        return view('livewire.admin.more');
    }
}
