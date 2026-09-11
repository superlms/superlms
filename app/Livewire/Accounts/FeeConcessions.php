<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesFeeConcessions;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Fee Concession — the whole page. Admin\Fee's "Concession" tab is the same
 * feature, just embedded as one tab among several; both share their logic
 * via HandlesFeeConcessions and their markup via
 * livewire.partials.fee-concession-panel, so a change here reaches both.
 */
class FeeConcessions extends Component
{
    use WireUiActions, WithPagination, HandlesFeeConcessions;

    public function mount(): void
    {
        //
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        return view('livewire.accounts.fee-concessions', array_merge(
            ['standards' => $standards],
            $this->feeConcessionViewData(),
        ));
    }
}
