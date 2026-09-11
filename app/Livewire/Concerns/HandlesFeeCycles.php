<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;

/**
 * The Fee Cycle + Calculator feature — shared verbatim between Accounts\FeeCycles
 * (its own page) and Admin\Fee (one tab among several). These two used to be
 * hand-copied and had already drifted; this trait plus the
 * `livewire.partials.fee-cycle-panel` view are now the one place either needs
 * to change for both to update together.
 *
 * The host component must provide `orgId(): int` and `WireUi\Traits\WireUiActions`
 * (for `notification()`) — both already do.
 */
trait HandlesFeeCycles
{
    // ─── Cycle / Calculator sub-tab ─────────────────────────────────────────
    public string $cycleTab = 'cycle'; // cycle | calculator

    // ─── Fee Cycle (installments) ───────────────────────────────────────────
    // An installment is defined only by its % of the fee — the rupee amount is
    // computed per class from each class's own fee structure (see the calculator).
    public $cycleFeeType       = 'academic';
    public $cycleSerial        = 1;        // installment no. 1–12
    public $cycleDueDate       = '';
    public $cyclePenaltyPerDay = '0';
    public $cycleFeePercent    = '';
    public $cycleYear          = '2026-27';
    public $editCycleId        = null;
    public bool $cycleModalOpen = false;
    public bool $editingToken   = false; // the open modal is editing the token row, not an installment
    public ?int $pendingDeleteCycleId = null;
    // How the installments are generated when adding: '' (chooser) | monthly | quarterly | custom.
    public string $cycleMode   = '';
    public $cycleMonthlyDueDay = 10;    // day-of-month each monthly installment is due
    // Custom mode (add, not edit): any number of installments at once.
    public array $customRows   = []; // [['serial'=>int,'due_date'=>string,'fee_percent'=>string,'penalty_per_day'=>string], ...]

    // A read-only look at one installment or the token fee.
    public $viewingCycle = null;

    // A one-time up-front charge (e.g. admission/registration) with its own due
    // date, collected before the % installments split whatever is left of
    // the fee. Optional — leave the amount blank to skip it.
    public $tokenFeeAmount     = '';
    public $tokenDueDate       = '';
    public $tokenPenaltyPerDay = '0';

    // Installment calculator (per-class breakdown)
    public $calcStandardId = '';
    public $calcSectionId  = '';
    public $calcSerial     = '';   // optional: focus a single installment

    public function switchCycleTab(string $tab): void
    {
        $this->cycleTab = in_array($tab, ['cycle', 'calculator'], true) ? $tab : 'cycle';
    }

    // ── Fee Cycle (installments) ────────────────────────────────────────────

    public function openCycleModal(?int $id = null): void
    {
        $this->resetCycleForm();
        $this->editCycleId = $id;

        if ($id) {
            $c = FeeCycle::forOrg($this->orgId())->find($id);
            if (!$c) return;
            // The token fee has its own minimal edit form (amount/due date/penalty
            // only) — it isn't a % installment, so none of the mode/serial fields apply.
            if ($c->is_token) {
                $this->editingToken       = true;
                $this->editCycleId        = $c->id;
                $this->cycleFeeType       = $c->fee_type;
                $this->cycleYear          = $c->academic_year;
                $this->tokenFeeAmount     = (string) $c->amount;
                $this->tokenDueDate       = optional($c->due_date)->toDateString();
                $this->tokenPenaltyPerDay = (string) $c->penalty_per_day;
                $this->cycleModalOpen     = true;
                return;
            }
            // Editing an existing installment is always a single-row (custom) edit.
            $this->cycleMode          = 'custom';
            $this->cycleFeeType       = $c->fee_type;
            $this->cycleSerial        = $c->payment_serial;
            $this->cycleDueDate       = optional($c->due_date)->toDateString();
            $this->cyclePenaltyPerDay = $c->penalty_per_day;
            $this->cycleFeePercent    = $c->fee_percent;
            $this->cycleYear          = $c->academic_year;
        }
        $this->loadTokenForCurrentCycle();
        $this->cycleModalOpen = true;
    }

    /** Dropdown picks monthly | quarterly | custom — re-validate clean, and seed one blank row for Custom. */
    public function updatedCycleMode(): void
    {
        $this->resetValidation();
        if (!$this->editCycleId && $this->cycleMode === 'custom' && empty($this->customRows)) {
            $this->customRows = [$this->blankCustomRow()];
        }
    }

    public function addCustomRow(): void
    {
        $this->customRows[] = $this->blankCustomRow();
    }

    public function removeCustomRow(int $index): void
    {
        if (!isset($this->customRows[$index])) return;
        unset($this->customRows[$index]);
        $this->customRows = array_values($this->customRows);
    }

    private function blankCustomRow(): array
    {
        return [
            'serial'          => $this->nextCustomSerial(),
            'due_date'        => '',
            'fee_percent'     => '',
            'penalty_per_day' => '0',
        ];
    }

    /** The lowest installment number (1–12) not already used, in the DB or in rows added so far this session. */
    private function nextCustomSerial(): int
    {
        $used = FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $this->cycleFeeType)
            ->where('academic_year', $this->cycleYear)
            ->where('is_token', false)
            ->pluck('payment_serial')
            ->merge(collect($this->customRows)->pluck('serial'))
            ->map(fn ($v) => (int) $v)
            ->all();

        for ($i = 1; $i <= 12; $i++) {
            if (!in_array($i, $used, true)) return $i;
        }
        return 1;
    }

    /** View a single installment (or the token fee) read-only. */
    public function viewCycle(int $id): void
    {
        $this->viewingCycle = FeeCycle::forOrg($this->orgId())->find($id);
    }

    public function closeCycleView(): void
    {
        $this->viewingCycle = null;
    }

    /**
     * "#3 (Apr)" for a monthly installment, "Q1 (Apr-Jun)" for a quarterly one —
     * both already carry a start/end date; a custom installment has neither, so
     * it prints as just its own number.
     */
    public function cycleSpanLabel(FeeCycle $cycle): ?string
    {
        if ($cycle->is_token || !$cycle->start_date || !$cycle->end_date) {
            return null;
        }

        $start = \Carbon\Carbon::parse($cycle->start_date);
        $end   = \Carbon\Carbon::parse($cycle->end_date);

        if ($start->isSameMonth($end)) {
            return $start->format('M');
        }

        $quarter = intdiv((($start->month - 4 + 12) % 12), 3) + 1;
        return "Q{$quarter} ({$start->format('M')}-{$end->format('M')})";
    }

    /** The academic year running now — April(this year) → March(next), e.g. "2026-27". */
    private function currentAcademicYear(): string
    {
        $now       = now();
        $startYear = $now->month >= 4 ? $now->year : $now->year - 1;
        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    /** Prefills the token-fee fields from whatever token row already exists for this fee type + year. */
    private function loadTokenForCurrentCycle(): void
    {
        $token = FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $this->cycleFeeType ?: 'academic')
            ->where('academic_year', $this->cycleYear ?: '')
            ->where('is_token', true)
            ->first();

        $this->tokenFeeAmount     = $token ? (string) $token->amount : '';
        $this->tokenDueDate       = $token ? optional($token->due_date)->toDateString() : '';
        $this->tokenPenaltyPerDay = $token ? (string) $token->penalty_per_day : '0';
    }

    /** Create/update the one-time token fee for the current fee type + year. No-op if no amount was set. */
    private function upsertTokenFee(): void
    {
        $amount = (float) ($this->tokenFeeAmount ?: 0);
        if ($amount <= 0) {
            return;
        }
        if (!$this->tokenDueDate) {
            $this->notification()->error('Set a due date for the token fee, or leave its amount blank.');
            return;
        }

        FeeCycle::updateOrCreate(
            [
                'organization_id' => $this->orgId(),
                'fee_type'        => $this->cycleFeeType,
                'academic_year'   => $this->cycleYear,
                'is_token'        => true,
            ],
            [
                'payment_serial'  => 0,
                'due_date'        => $this->tokenDueDate,
                'penalty_per_day' => $this->tokenPenaltyPerDay ?: 0,
                'fee_percent'     => 0,
                'amount'          => $amount,
                'is_active'       => true,
            ]
        );
    }

    /** Update the token row being edited. The calculator recomputes off it automatically on next render. */
    private function saveTokenEdit(): void
    {
        $amount = (float) ($this->tokenFeeAmount ?: 0);
        if ($amount <= 0) {
            $this->notification()->error('Enter a token fee amount.');
            return;
        }
        if (!$this->tokenDueDate) {
            $this->notification()->error('Set a due date for the token fee.');
            return;
        }

        FeeCycle::forOrg($this->orgId())->where('id', $this->editCycleId)->update([
            'due_date'        => $this->tokenDueDate,
            'penalty_per_day' => $this->tokenPenaltyPerDay ?: 0,
            'amount'          => $amount,
        ]);
        $this->notification()->success('Token fee updated!');
        $this->closeCycleModal();
    }

    public function closeCycleModal(): void
    {
        $this->cycleModalOpen = false;
        $this->resetCycleForm();
    }

    private function resetCycleForm(): void
    {
        $this->reset([
            'editCycleId', 'editingToken', 'cycleSerial', 'cycleDueDate',
            'cyclePenaltyPerDay', 'cycleFeePercent',
        ]);
        $this->cycleMode          = '';
        $this->cycleFeeType       = 'academic';
        $this->cycleSerial        = 1;
        $this->cyclePenaltyPerDay = '0';
        $this->cycleMonthlyDueDay = 10;
        $this->cycleYear          = $this->currentAcademicYear();
        $this->customRows         = [];
        $this->tokenFeeAmount     = '';
        $this->tokenDueDate       = '';
        $this->tokenPenaltyPerDay = '0';
        $this->resetValidation();
    }

    /** First 4-digit year in an academic-year string, e.g. "2026-27" → 2026. */
    private function cycleStartYear(): int
    {
        return (int) (preg_match('/\d{4}/', (string) $this->cycleYear, $m) ? $m[0] : now()->year);
    }

    public function saveCycle(): void
    {
        if ($this->editingToken) {
            $this->saveTokenEdit();
            return;
        }

        // Monthly / Quarterly auto-split (add mode only) — generate the whole set.
        if (!$this->editCycleId && $this->cycleMode === 'monthly') {
            $this->generateMonthlyCycles();
            return;
        }
        if (!$this->editCycleId && $this->cycleMode === 'quarterly') {
            $this->generateQuarterlyCycles();
            return;
        }

        // Custom, adding fresh — any number of installments at once.
        if (!$this->editCycleId) {
            $this->saveCustomRows();
            return;
        }

        // Custom, editing one existing installment.
        $this->validate([
            'cycleSerial'     => 'required|integer|min:1|max:12',
            'cycleDueDate'    => 'required|date',
            'cycleFeePercent' => 'required|numeric|min:0|max:100',
        ]);

        FeeCycle::forOrg($this->orgId())->where('id', $this->editCycleId)->update([
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
        ]);
        $this->notification()->success('Installment updated!');

        $this->upsertTokenFee();
        $this->closeCycleModal();
    }

    /** Custom mode, add: save every filled row in $customRows as its own installment. */
    private function saveCustomRows(): void
    {
        $rows = collect($this->customRows)
            ->filter(fn ($r) => trim((string) ($r['due_date'] ?? '')) !== '' && trim((string) ($r['fee_percent'] ?? '')) !== '')
            ->values();

        if ($rows->isEmpty()) {
            $this->notification()->error('Add at least one installment with a due date and fee %.');
            return;
        }

        foreach ($rows as $i => $r) {
            $n = $i + 1;
            if (!is_numeric($r['fee_percent']) || $r['fee_percent'] < 0 || $r['fee_percent'] > 100) {
                $this->notification()->error("Row {$n}: fee % must be between 0 and 100.");
                return;
            }
            if (empty($r['serial']) || $r['serial'] < 1 || $r['serial'] > 12) {
                $this->notification()->error("Row {$n}: pick an installment number.");
                return;
            }
        }

        foreach ($rows as $r) {
            FeeCycle::updateOrCreate(
                [
                    'organization_id' => $this->orgId(),
                    'fee_type'        => $this->cycleFeeType,
                    'academic_year'   => $this->cycleYear,
                    'payment_serial'  => (int) $r['serial'],
                    'is_token'        => false,
                ],
                [
                    'start_date'      => null,
                    'end_date'        => null,
                    'due_date'        => $r['due_date'],
                    'penalty_per_day' => $r['penalty_per_day'] ?: 0,
                    'fee_percent'     => $r['fee_percent'],
                    'amount'          => 0,
                    'is_active'       => true,
                ]
            );
        }

        $this->upsertTokenFee();
        $this->notification()->success(count($rows) . ' installment(s) saved!');
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

        $this->upsertTokenFee();
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

        $this->upsertTokenFee();
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

    /**
     * Everything the `livewire.partials.fee-cycle-panel` view needs: the
     * installments list, the "already added" preview for the open form, and
     * the per-class calculator breakdown. Call from render() and merge the
     * result into whatever view data the host component returns.
     */
    protected function feeCycleViewData(): array
    {
        $orgId = $this->orgId();

        $cycles = FeeCycle::forOrg($orgId)
            ->orderBy('fee_type')
            ->orderByDesc('is_token')
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

        $calcTotalFee     = 0.0;
        $calcStudentCount = 0;
        $calcRows         = [];
        if ($this->calcStandardId) {
            $calcTotalFee = (float) FeeStructure::where('organization_id', $orgId)
                ->academic()->active()
                ->forClass((int) $this->calcStandardId, $this->calcSectionId ? (int) $this->calcSectionId : null)
                ->sum('amount');

            $calcStudentIds = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $this->calcStandardId)
                ->when($this->calcSectionId, fn ($q) => $q->where('section_id', $this->calcSectionId))
                ->pluck('id');
            $calcStudentCount = $calcStudentIds->count();

            // Real money collected for this class/section, allocated across
            // installments oldest-due-first — a payment carries no installment
            // of its own to join against, so this is the closest true reading
            // of "how much of installment N has actually come in".
            $realCollected = (float) FeePayment::whereIn('student_detail_id', $calcStudentIds)
                ->where('fee_type', 'academic')
                ->sum('amount');

            $acadCycles = FeeCycle::forOrg($orgId)->active()
                ->where('fee_type', 'academic')
                ->get();

            // The token fee (if any) comes off the top; the % installments
            // then split whatever of the class fee is left over.
            $tokenCycle    = $acadCycles->firstWhere('is_token', true);
            $tokenAmount   = (float) ($tokenCycle->amount ?? 0);
            $remainingBase = max(0, $calcTotalFee - $tokenAmount);

            // The token fee always leads the list, ahead of every installment,
            // whatever its own due date happens to be — it's the up-front
            // charge, so it reads first and is the first thing paid down too.
            $defs = [];
            if ($tokenCycle) {
                $defs[] = [
                    'serial'      => 'Token',
                    'percent'     => null,
                    'due_date'    => $tokenCycle->due_date,
                    'per_student' => $tokenAmount,
                    'span'        => null,
                ];
            }
            foreach ($acadCycles->where('is_token', false)->sortBy('payment_serial') as $cy) {
                $pct = (float) $cy->fee_percent;
                $defs[] = [
                    'serial'      => '#' . $cy->payment_serial,
                    'percent'     => $pct,
                    'due_date'    => $cy->due_date,
                    'per_student' => round($remainingBase * $pct / 100, 2),
                    'span'        => $this->cycleSpanLabel($cy),
                ];
            }

            $allocated = 0.0;
            foreach ($defs as $d) {
                $classTotal = round($d['per_student'] * $calcStudentCount, 2);
                $collected  = min($classTotal, max(0, $realCollected - $allocated));
                $allocated += $collected;

                $calcRows[] = [
                    'serial'      => $d['serial'],
                    'span'        => $d['span'],
                    'percent'     => $d['percent'],
                    'due_date'    => optional($d['due_date'])->format('d M Y'),
                    'amount'      => $d['per_student'],
                    'class_total' => $classTotal,
                    'collected'   => round($collected, 2),
                    'remaining'   => round(max(0, $classTotal - $collected), 2),
                ];
            }
        }

        $installmentCycles = $cycles->where('is_token', false);

        return [
            'cycles'           => $cycles,
            'cycleExisting'    => $cycleExisting,
            'calcSections'     => $calcSections,
            'calcTotalFee'     => $calcTotalFee,
            'calcStudentCount' => $calcStudentCount,
            'calcRows'         => $calcRows,
            'totalCycles'      => $installmentCycles->count(),
            'academicCycles'   => $installmentCycles->where('fee_type', 'academic')->count(),
            'transportCycles'  => $installmentCycles->where('fee_type', 'transport')->count(),
        ];
    }
}
