<?php

namespace App\Livewire\Accounts;

/**
 * The admin Payroll screen, shown in the accounts panel.
 *
 * Extends the admin component rather than copying it, so both panels run the
 * same salary logic over the same records and neither can drift from the other.
 * Nothing here differs per panel — the screen builds every URL from the guard's
 * own organization — so only the class name changes.
 */
class Payroll extends \App\Livewire\Admin\Payroll
{
}
