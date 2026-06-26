<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Fee\FeeCycle;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class FeeCycles extends Component
{
    use WireUiActions, WithPagination;

    public $modalOpen = false;
    public $editId = null;

    // Form
    public $feeType = 'academic';
    public $paymentSerial = '';
    public $dueDate = '';
    public $penaltyPerDay = '0'; // kept in DB but not shown in UI form
    public $feePercent = '';
    public $academicYear = '';
    public $isActive = true;

    // Delete confirm
    public ?int $pendingDeleteCycleId = null;

    // Filter
    public $filterFeeType = '';
    public $filterYear = '';
    public $perPage = 10;

    public function mount(): void
    {
        $this->academicYear = '2026-27';
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function openModal(int $id = null): void
    {
        $this->resetForm();
        $this->editId = $id;
        $this->modalOpen = true;

        if ($id) {
            $cycle = FeeCycle::find($id);
            $this->feeType = $cycle->fee_type;
            $this->paymentSerial = $cycle->payment_serial;
            $this->dueDate = $cycle->due_date?->toDateString();
            $this->penaltyPerDay = $cycle->penalty_per_day;
            $this->feePercent = $cycle->fee_percent;
            $this->academicYear = $cycle->academic_year;
            $this->isActive = $cycle->is_active;
        }
    }

    public function saveCycle(): void
    {
        $this->validate([
            'feeType' => 'required|in:academic,transport',
            'paymentSerial' => 'required|integer|min:1',
            'dueDate' => 'required|date',
            'feePercent' => 'required|numeric|min:0|max:100',
            'academicYear' => 'required|string|max:20',
        ]);

        try {
            $data = [
                'organization_id' => $this->orgId(),
                'fee_type' => $this->feeType,
                'payment_serial' => $this->paymentSerial,
                'due_date' => $this->dueDate,
                'penalty_per_day' => $this->penaltyPerDay ?? 0,
                'fee_percent' => $this->feePercent,
                'academic_year' => $this->academicYear,
                'is_active' => $this->isActive,
            ];

            if ($this->editId) {
                FeeCycle::where('id', $this->editId)
                    ->where('organization_id', $this->orgId())
                    ->update(collect($data)->except('organization_id')->toArray());
                $this->notification()->success('Fee cycle updated!');
            } else {
                FeeCycle::create($data);
                $this->notification()->success('Fee cycle created!');
            }

            $this->resetForm();
        } catch (\Exception $e) {
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    public function editCycle(int $id): void
    {
        $this->openModal($id);
    }

    // ─── Delete (custom pendingDelete pattern) ───────────────────────────────────

    public function deleteCycle(int $id): void
    {
        $this->pendingDeleteCycleId = $id;
    }

    public function cancelDeleteCycle(): void
    {
        $this->pendingDeleteCycleId = null;
    }

    public function doDeleteCycle(): void
    {
        FeeCycle::where('id', $this->pendingDeleteCycleId)
            ->where('organization_id', $this->orgId())
            ->delete();
        $this->pendingDeleteCycleId = null;
        $this->notification()->success('Fee cycle deleted!');
    }

    public function toggleActive(int $id): void
    {
        $cycle = FeeCycle::where('id', $id)->where('organization_id', $this->orgId())->first();
        if ($cycle) {
            $cycle->update(['is_active' => !$cycle->is_active]);
            $this->notification()->success('Status updated!');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'modalOpen', 'feeType', 'paymentSerial', 'dueDate', 'feePercent', 'isActive']);
        $this->feeType = 'academic';
        $this->isActive = true;
        $this->penaltyPerDay = '0';
        $this->academicYear = '2026-27';
    }

    public function updatedFilterFeeType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterYear(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orgId = $this->orgId();

        $cycles = FeeCycle::where('organization_id', $orgId)
            ->when($this->filterFeeType, fn($q) => $q->where('fee_type', $this->filterFeeType))
            ->when($this->filterYear, fn($q) => $q->where('academic_year', $this->filterYear))
            ->orderBy('fee_type')
            ->orderBy('payment_serial')
            ->paginate($this->perPage);

        // Analytics counts from full (unfiltered) dataset
        $totalCycles = FeeCycle::where('organization_id', $orgId)->count();
        $academicCycles = FeeCycle::where('organization_id', $orgId)->where('fee_type', 'academic')->count();
        $transportCycles = FeeCycle::where('organization_id', $orgId)->where('fee_type', 'transport')->count();
        $activeCycles = FeeCycle::where('organization_id', $orgId)->where('is_active', true)->count();

        return view('livewire.accounts.fee-cycles', [
            'cycles' => $cycles,
            'totalCycles' => $totalCycles,
            'academicCycles' => $academicCycles,
            'transportCycles' => $transportCycles,
            'activeCycles' => $activeCycles,
        ]);
    }
}
