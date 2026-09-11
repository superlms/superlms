<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesFeeCycles;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Fee Cycle + Calculator — the whole page. Admin\Fee's "Cycle" tab is the
 * same feature, just embedded as one tab among several; both share their
 * logic via HandlesFeeCycles and their markup via
 * livewire.partials.fee-cycle-panel, so a change here reaches both.
 */
class FeeCycles extends Component
{
    use WireUiActions, HandlesFeeCycles;

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

        return view('livewire.accounts.fee-cycles', array_merge(
            ['standards' => $standards],
            $this->feeCycleViewData(),
        ));
    }
}
