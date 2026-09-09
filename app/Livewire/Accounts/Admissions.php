<?php

namespace App\Livewire\Accounts;

/**
 * The admin Admissions screen, shown in the accounts panel.
 *
 * Accounts reaches this from its own sidebar, so the back arrow to admin's
 * "More" screen — which the accounts panel does not have — is left off.
 */
class Admissions extends \App\Livewire\Admin\Admissions
{
    protected function showsBackToMore(): bool
    {
        return false;
    }
}
