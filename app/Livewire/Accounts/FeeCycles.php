<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class FeeCycles extends Component
{
    use WireUiActions;

    // ─── Fee Cycle (installments) ───────────────────────────────────────────────
    // An installment is defined only by its % of the fee — the rupee amount is
    // computed per class from each class's own fee structure (see the calculator).
    public $cycleFeeType    = 'academic';
    public $cycleSerial     = 1;        // installment no. 1–12
    public $cycleDueDate     = '';
    public $cyclePenaltyPerDay = '0';
    public $cycleFeePercent  = '';
    public $cycleYear        = '2026-27';
    public $editCycleId      = null;
    public bool $cycleModalOpen = false;
    public ?int $pendingDeleteCycleId = null;
    // How the installments are generated when adding: '' (chooser) | monthly | quarterly | custom.
    public string $cycleMode = '';
    public $cycleMonthlyDueDay = 10;    // day-of-month each monthly installment is due

    // Installment calculator (per-class breakdown)
    public $calcStandardId = '';
    public $calcSectionId  = '';
    public $calcSerial     = '';   // optional: focus a single installment

    public function mount(): void
    {
        //
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    // ── Fee Cycle (installments) ────────────────────────────────────────────────

    public function openCycleModal(?int $id = null): void
    {
        $this->resetCycleForm();
        $this->editCycleId = $id;

        if ($id) {
            $c = FeeCycle::forOrg($this->orgId())->find($id);
            if (!$c) return;
            // Editing an existing installment is always a single-row (custom) edit.
            $this->cycleMode          = 'custom';
            $this->cycleFeeType       = $c->fee_type;
            $this->cycleSerial        = $c->payment_serial;
            $this->cycleDueDate       = optional($c->due_date)->toDateString();
            $this->cyclePenaltyPerDay = $c->penalty_per_day;
            $this->cycleFeePercent    = $c->fee_percent;
            $this->cycleYear          = $c->academic_year;
        }
        $this->cycleModalOpen = true;
    }

    /** Pick how the cycle is built (monthly | quarterly | custom) from the chooser. */
    public function setCycleMode(string $mode): void
    {
        $this->cycleMode = in_array($mode, ['monthly', 'quarterly', 'custom'], true) ? $mode : '';
        $this->resetValidation();
    }

    public function closeCycleModal(): void
    {
        $this->cycleModalOpen = false;
        $this->resetCycleForm();
    }

    private function resetCycleForm(): void
    {
        $this->reset([
            'editCycleId', 'cycleSerial', 'cycleDueDate',
            'cyclePenaltyPerDay', 'cycleFeePercent',
        ]);
        $this->cycleMode          = '';
        $this->cycleFeeType       = 'academic';
        $this->cycleSerial        = 1;
        $this->cyclePenaltyPerDay = '0';
        $this->cycleMonthlyDueDay = 10;
        $this->cycleYear          = '2026-27';
        $this->resetValidation();
    }

    /** First 4-digit year in an academic-year string, e.g. "2026-27" → 2026. */
    private function cycleStartYear(): int
    {
        return (int) (preg_match('/\d{4}/', (string) $this->cycleYear, $m) ? $m[0] : now()->year);
    }

    public function saveCycle(): void
    {
        // Monthly / Quarterly auto-split (add mode only) — generate the whole set.
        if (!$this->editCycleId && $this->cycleMode === 'monthly') {
            $this->generateMonthlyCycles();
            return;
        }
        if (!$this->editCycleId && $this->cycleMode === 'quarterly') {
            $this->generateQuarterlyCycles();
            return;
        }

        // Custom (single installment) — add or edit.
        $this->validate([
            'cycleFeeType'    => 'required|string|max:20',
            'cycleSerial'     => 'required|integer|min:1|max:12',
            'cycleDueDate'    => 'required|date',
            'cycleFeePercent' => 'required|numeric|min:0|max:100',
            'cycleYear'       => 'required|string|max:20',
        ]);

        $payload = [
            'organization_id' => $this->orgId(),
            'fee_type'        => $this->cycleFeeType,
            'payment_serial'  => $this->cycleSerial,
            'start_date'      => null,
            'end_date'        => null,
            'due_date'        => $this->cycleDueDate,
            'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
            'fee_percent'     => $this->cycleFeePercent,
            // Amount is computed per class from fee_percent × the class's own fee.
            'amount'          => 0,
            'academic_year'   => $this->cycleYear,
            'is_active'       => true,
        ];

        if ($this->editCycleId) {
            FeeCycle::forOrg($this->orgId())->where('id', $this->editCycleId)->update($payload);
            $this->notification()->success('Installment updated!');
        } else {
            FeeCycle::create($payload);
            $this->notification()->success('Installment added!');
        }

        $this->closeCycleModal();
    }

    /**
     * Split the full fee into 12 equal monthly installments for the academic
     * year (April → March). Each is due on the chosen day-of-month. Replaces any
     * existing installments for this fee type + year.
     */
    private function generateMonthlyCycles(): void
    {
        $this->validate([
            'cycleFeeType'       => 'required|string|max:20',
            'cycleYear'          => 'required|string|max:20',
            'cycleMonthlyDueDay' => 'required|integer|min:1|max:28',
        ]);

        $startYear = $this->cycleStartYear();
        $dueDay    = (int) $this->cycleMonthlyDueDay;
        $per       = round(100 / 12, 2);

        $this->replaceCycles(function () use ($startYear, $dueDay, $per) {
            for ($i = 0; $i < 12; $i++) {
                // Academic year runs April(startYear) → March(startYear+1).
                $month = \Carbon\Carbon::create($startYear, 4, 1)->addMonths($i);
                $due   = $month->copy()->day(min($dueDay, $month->daysInMonth));
                // Load the rounding remainder onto the last installment so the set sums to 100%.
                $percent = $i === 11 ? round(100 - $per * 11, 2) : $per;

                FeeCycle::create([
                    'organization_id' => $this->orgId(),
                    'fee_type'        => $this->cycleFeeType,
                    'payment_serial'  => $i + 1,
                    'start_date'      => $month->copy()->startOfMonth()->toDateString(),
                    'end_date'        => $month->copy()->endOfMonth()->toDateString(),
                    'due_date'        => $due->toDateString(),
                    'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
                    'fee_percent'     => $percent,
                    'amount'          => 0,
                    'academic_year'   => $this->cycleYear,
                    'is_active'       => true,
                ]);
            }
        });

        $this->notification()->success('12 monthly installments created!');
        $this->closeCycleModal();
    }

    /**
     * Split the full fee into 4 equal quarterly installments (Apr–Jun, Jul–Sep,
     * Oct–Dec, Jan–Mar). Each is due on the last day of its quarter's last month.
     * Replaces any existing installments for this fee type + year.
     */
    private function generateQuarterlyCycles(): void
    {
        $this->validate([
            'cycleFeeType' => 'required|string|max:20',
            'cycleYear'    => 'required|string|max:20',
        ]);

        $y = $this->cycleStartYear();
        // [startMonth, startYear, endMonth, endYear]
        $quarters = [
            [4, $y, 6, $y],
            [7, $y, 9, $y],
            [10, $y, 12, $y],
            [1, $y + 1, 3, $y + 1],
        ];

        $this->replaceCycles(function () use ($quarters) {
            foreach ($quarters as $i => [$sM, $sY, $eM, $eY]) {
                $start = \Carbon\Carbon::create($sY, $sM, 1)->startOfMonth();
                $end   = \Carbon\Carbon::create($eY, $eM, 1)->endOfMonth();

                FeeCycle::create([
                    'organization_id' => $this->orgId(),
                    'fee_type'        => $this->cycleFeeType,
                    'payment_serial'  => $i + 1,
                    'start_date'      => $start->toDateString(),
                    'end_date'        => $end->toDateString(),
                    'due_date'        => $end->toDateString(),
                    'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
                    'fee_percent'     => 25,
                    'amount'          => 0,
                    'academic_year'   => $this->cycleYear,
                    'is_active'       => true,
                ]);
            }
        });

        $this->notification()->success('4 quarterly installments created!');
        $this->closeCycleModal();
    }

    /** Clear existing installments for the current fee type + year, then run $build. */
    private function replaceCycles(callable $build): void
    {
        FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $this->cycleFeeType)
            ->where('academic_year', $this->cycleYear)
            ->delete();

        $build();
    }

    public function deleteCycle(int $id): void { $this->pendingDeleteCycleId = $id; }
    public function cancelDeleteCycle(): void  { $this->pendingDeleteCycleId = null; }
    public function doDeleteCycle(): void
    {
        FeeCycle::forOrg($this->orgId())->where('id', $this->pendingDeleteCycleId)->delete();
        $this->pendingDeleteCycleId = null;
        $this->notification()->success('Installment deleted!');
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $cycles = FeeCycle::forOrg($orgId)
            ->orderBy('fee_type')
            ->orderBy('payment_serial')
            ->get();

        // Existing installments of the fee type being added/edited — shown in
        // the form so the user can see previous installments' % and due dates.
        $cycleExisting = FeeCycle::forOrg($orgId)
            ->where('fee_type', $this->cycleFeeType ?: 'academic')
            ->where('academic_year', $this->cycleYear ?: '')
            ->orderBy('payment_serial')
            ->get();

        // ── Per-class installment calculator ──
        $calcSections = $this->calcStandardId
            ? Section::where('standard_id', $this->calcStandardId)->where('is_active', true)->orderBy('id')->get()
            : collect();

        $calcTotalFee = 0.0;
        $calcRows     = [];
        if ($this->calcStandardId) {
            $calcTotalFee = (float) FeeStructure::where('organization_id', $orgId)
                ->academic()->active()
                ->forClass((int) $this->calcStandardId, $this->calcSectionId ? (int) $this->calcSectionId : null)
                ->sum('amount');

            $acadCycles = FeeCycle::forOrg($orgId)->active()
                ->where('fee_type', 'academic')
                ->orderBy('payment_serial')->get();

            $cum = 0.0;
            foreach ($acadCycles as $cy) {
                $pct = (float) $cy->fee_percent;
                $amt = round($calcTotalFee * $pct / 100, 2);
                $cum += $amt;
                $calcRows[] = [
                    'serial'     => (int) $cy->payment_serial,
                    'percent'    => $pct,
                    'due_date'   => optional($cy->due_date)->format('d M Y'),
                    'amount'     => $amt,
                    'cumulative' => round($cum, 2),
                    'remaining'  => round(max(0, $calcTotalFee - $cum), 2),
                ];
            }
        }

        // Analytics counts from full dataset
        $totalCycles     = $cycles->count();
        $academicCycles  = $cycles->where('fee_type', 'academic')->count();
        $transportCycles = $cycles->where('fee_type', 'transport')->count();
        $activeCycles    = $cycles->where('is_active', true)->count();

        return view('livewire.accounts.fee-cycles', [
            'standards'       => $standards,
            'cycles'          => $cycles,
            'cycleExisting'   => $cycleExisting,
            'calcSections'    => $calcSections,
            'calcTotalFee'    => $calcTotalFee,
            'calcRows'        => $calcRows,
            'totalCycles'     => $totalCycles,
            'academicCycles'  => $academicCycles,
            'transportCycles' => $transportCycles,
            'activeCycles'    => $activeCycles,
        ]);
    }
}
