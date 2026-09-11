<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesFeeSubmission;
use App\Livewire\Concerns\HandlesStudentFeeView;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Fee Submission — the accounts-side page. Admin\Fee's "Fee Submission" tab is
 * the same screen under the admin guard; both share their logic via
 * HandlesFeeSubmission (plus HandlesStudentFeeView for the per-student ledger)
 * and their markup via livewire.partials.fee-submission-header /
 * fee-submission-panel, so a change here reaches both.
 */
class FeeSubmission extends Component
{
    use WireUiActions, HandlesStudentFeeView, HandlesFeeSubmission;

    public function mount(): void
    {
        $this->submitDate = today()->toDateString();
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.accounts.fee-submission', $this->feeSubmissionViewData());
    }
}
