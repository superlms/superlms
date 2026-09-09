<?php

namespace App\Livewire\Accounts;

/**
 * The admin Ledger, shown in the accounts panel.
 *
 * It deliberately extends the admin component rather than copying it: both
 * panels then read and write the same LedgerTransaction rows through the same
 * LedgerService, scoped to the signed-in user's organization. A credit added
 * here shows up on the admin ledger immediately, and vice versa — there is only
 * one ledger. Only the statement route differs, because the PDF lives behind
 * the accounts guard here.
 */
class Ledger extends \App\Livewire\Admin\Ledger
{
    protected function statementRouteName(): string
    {
        return 'accounts.ledger.statement';
    }
}
