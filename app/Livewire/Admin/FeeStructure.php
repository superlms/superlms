<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesFeeStructures;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Fee Structure — the whole page, and the Fee page's "Fee Structure" tab
 * (rendered with :embedded="true"). Accounts\FeeStructure is the same
 * feature under the accounts guard; both share their logic via
 * HandlesFeeStructures and their markup via the
 * livewire.partials.fee-structure-* views, so a change here reaches both.
 */
class FeeStructure extends Component
{
    use WireUiActions, HandlesFeeStructures;

    /** True when rendered inside the Fee page's "Fee Structure" tab. */
    public bool $embedded = false;

    protected function queryString(): array
    {
        // Skip URL sync when embedded — avoids clashing with the parent Fee page.
        if ($this->embedded) {
            return [];
        }

        return [
            'structureTab' => ['except' => 'academic'],
            'search'       => ['except' => ''],
        ];
    }

    public function mount($embedded = false): void
    {
        $this->embedded = $embedded;
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    protected function structureRoutePrefix(): string
    {
        return 'admin';
    }

    public function render()
    {
        return view('livewire.admin.fee-structure', $this->feeStructureViewData());
    }
}
