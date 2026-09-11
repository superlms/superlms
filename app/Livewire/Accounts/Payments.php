<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesPayments;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The accounts-side Payments page. Everything on it — filters, the analytics
 * strip and the merged listing — lives in HandlesPayments, which the Admin
 * Fee "Payments" tab shares so the two stay identical.
 */
class Payments extends Component
{
    use WithPagination, HandlesPayments;

    protected $queryString = [
        'paymentModeFilter' => ['except' => ''],
        'paymentStudentSearch' => ['except' => ''],
        'feeTypeFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->initPaymentFilters();
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    protected function paymentsRoutePrefix(): string
    {
        return 'accounts';
    }

    public function resetFilters(): void
    {
        $this->resetPaymentFilters();
    }

    public function render()
    {
        $standards = Standard::where('organization_id', $this->orgId())
            ->where('is_active', true)->orderBy('id')->get();

        return view('livewire.accounts.payments', array_merge(
            ['standards' => $standards],
            $this->paymentsViewData(),
        ));
    }
}
