<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesStudentFeeView;
use App\Livewire\Concerns\HandlesViewFee;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * View Fee — the accounts-side page. Admin\Fee's "View Fee" tab is the same
 * screen under the admin guard; both share their logic via HandlesViewFee
 * (plus HandlesStudentFeeView for the per-student ledger) and their markup via
 * livewire.partials.view-fee-header / view-fee-panel, so a change here reaches
 * both.
 */
class ViewFee extends Component
{
    use WireUiActions, HandlesStudentFeeView, HandlesViewFee;

    protected $queryString = [
        'viewSubTab' => ['except' => 'by_student'],
    ];

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.accounts.view-fee', $this->viewFeeViewData());
    }
}
