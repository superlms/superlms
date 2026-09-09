<?php

namespace App\Livewire\Accounts;

/**
 * The admin Report Card screen, shown in the accounts panel.
 *
 * Same issue flow over the same cards; only the download and print PDFs differ,
 * because those sit behind the accounts guard.
 */
class ReportCard extends \App\Livewire\Admin\ReportCard
{
    protected function downloadRouteName(): string
    {
        return 'accounts.report-card.download';
    }

    protected function printRouteName(): string
    {
        return 'accounts.report-card.print';
    }
}
