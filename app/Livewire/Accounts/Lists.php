<?php

namespace App\Livewire\Accounts;

/**
 * The admin Lists screen, shown in the accounts panel.
 *
 * Extends the admin component so both panels offer the same list definitions
 * over the same records. Accounts reaches this from its own sidebar, so the
 * "back to More" arrow — an admin-only screen — is left off.
 */
class Lists extends \App\Livewire\Admin\Lists
{
    protected function pdfRouteName(): string
    {
        return 'accounts.lists.pdf';
    }

    protected function showsBackToMore(): bool
    {
        return false;
    }
}
