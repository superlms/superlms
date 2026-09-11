<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesFeeStructures;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Fee Structure — the accounts-side page. Admin\FeeStructure is the same
 * feature under the admin guard; both share their logic via
 * HandlesFeeStructures and their markup via the
 * livewire.partials.fee-structure-* views, so a change here reaches both.
 */
class FeeStructure extends Component
{
    use WireUiActions, HandlesFeeStructures;

    protected $queryString = [
        'structureTab' => ['except' => 'academic'],
        'search'       => ['except' => ''],
    ];

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    protected function structureRoutePrefix(): string
    {
        return 'accounts';
    }

    public function render()
    {
        return view('livewire.accounts.fee-structure', $this->feeStructureViewData());
    }
}
