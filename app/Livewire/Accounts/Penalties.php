<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesPenalties;
use App\Livewire\Concerns\HandlesStudentFeeView;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * The accounts-side Penalties page — the same screen as the admin Fee
 * "Penalties" tab. Everything on it (the By Student / By Class sub-tabs, the
 * per-installment accrual, the Waiver slide-in) lives in HandlesPenalties and
 * the shared penalties-* partials, so the two stay in sync.
 */
class Penalties extends Component
{
    use WireUiActions, HandlesStudentFeeView, HandlesPenalties;

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.accounts.penalties', $this->penaltyViewData());
    }
}
