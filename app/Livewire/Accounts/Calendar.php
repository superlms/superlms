<?php

namespace App\Livewire\Accounts;

use Livewire\Component;

/**
 * Accounts Calendar — mirrors the admin Calendar page (App\Livewire\Admin\Calender).
 * The month grid itself is the shared, org-scoped <livewire:admin.time-table-calendar>
 * component, embedded from the accounts blade for exact parity with the admin panel.
 */
class Calendar extends Component
{
    public function render()
    {
        return view('livewire.accounts.calendar');
    }
}
