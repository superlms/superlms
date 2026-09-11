<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public $cycleStartDate     = '';   // the span this installment covers…
    public $cycleEndDate       = '';   // …both ends, so the listing can show it
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
    public array $customRows   = []; // [['serial','start_date','end_date','due_date','fee_percent','penalty_per_day'], ...]
    // Editing one installment: what the OTHER installments become once this
    // one's % changes, so the year still totals 100%. [['id','serial','was','percent'], ...]
    public array $cycleSiblingPreview = [];

    // A read-only look at one installment or the token fee.
    public $viewingCycle = null;

    // ─── Edit the whole cycle at once (the listing header's Edit button) ─────
    // One panel listing every installment of a fee type + year, each row
    // labelled the way the cycle was actually built — "April 2026" for a
    // monthly cycle, "Q1 · Apr–Jun" for a quarterly one, plain numbers for a
    // custom one — with its % editable. Typing a % pins that row; the rows
    // still on auto re-split whatever is left of the 100% between them.
    public bool $cycleEditOpen      = false;
    public string $editCycleFeeType = '';
    public string $editCycleYear    = '';
    public string $editCycleKind    = 'custom'; // monthly | quarterly | custom
    public array $editRows          = [];

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
            $this->cycleStartDate     = optional($c->start_date)->toDateString() ?? '';
            $this->cycleEndDate       = optional($c->end_date)->toDateString() ?? '';
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
            $this->customRows = $this->fillBlankPercents([$this->blankCustomRow()]);
        }
    }

    public function addCustomRow(): void
    {
        $this->customRows[] = $this->blankCustomRow();

        // The new row takes an even share and the others each give up a slice
        // of it, so the set still totals 100%.
        $new = array_key_last($this->customRows);
        $this->customRows[$new]['fee_percent'] = $this->trimPercent(round(100 / count($this->customRows), 2));
        $this->customRows = $this->shiftPercentRows($this->customRows, $new);
    }

    public function removeCustomRow(int $index): void
    {
        if (!isset($this->customRows[$index])) return;
        unset($this->customRows[$index]);
        $this->customRows = array_values($this->customRows);

        // The freed % goes back to the surviving rows, evenly.
        $this->customRows = $this->rebalanceToHundred($this->customRows);
    }

    /**
     * Editing one row's % moves only the difference: the other rows each give
     * up (or take back) an equal slice of it and otherwise keep their own
     * numbers. Clearing a row hands it back to the even share.
     */
    public function updatedCustomRows($value, $key): void
    {
        if (!str_ends_with((string) $key, '.fee_percent')) {
            return;
        }

        $index = (int) explode('.', (string) $key)[0];

        $this->customRows = trim((string) $value) === ''
            ? $this->fillBlankPercents($this->customRows)
            : $this->shiftPercentRows($this->customRows, $index);
    }

    /**
     * The one rule behind every % field on this page. One row changed, so the
     * other rows absorb the difference equally and keep everything else they
     * had: 20 / 17 / 33 / 30 with the 17 raised to 20 becomes 19 / 20 / 32 / 29
     * — three points taken, one point off each of the other three. Lower a row
     * and the same slice goes back to them instead.
     *
     * @param  array  $rows     rows with a 'fee_percent' key
     * @param  int    $changed  index of the row the user just typed into
     */
    private function shiftPercentRows(array $rows, int $changed): array
    {
        if (!array_key_exists($changed, $rows) || count($rows) < 2) {
            return $rows;
        }

        $percent = max(0, min(100, (float) ($rows[$changed]['fee_percent'] ?? 0)));
        $rows[$changed]['fee_percent'] = $this->trimPercent($percent);

        $left   = round(100 - $percent, 2);
        $others = array_values(array_diff(array_keys($rows), [$changed]));

        $values = [];
        foreach ($others as $i) {
            $values[$i] = (float) ($rows[$i]['fee_percent'] ?? 0);
        }

        // The flat slice every other row gives up (or takes back).
        $slice = ($left - array_sum($values)) / count($others);
        foreach ($values as $i => $v) {
            $values[$i] = round(max(0, $v + $slice), 2);
        }

        // A row that hit zero can give nothing more — spread what is still owed
        // over the rows that can still move. A couple of passes always settles it.
        for ($pass = 0; $pass < 5; $pass++) {
            $owed = round($left - array_sum($values), 2);
            if (abs($owed) < 0.01) {
                break;
            }
            $movable = array_keys(array_filter($values, fn ($v) => $owed > 0 || $v > 0));
            if (empty($movable)) {
                break;
            }
            $step = $owed / count($movable);
            foreach ($movable as $i) {
                $values[$i] = round(max(0, $values[$i] + $step), 2);
            }
        }

        // Rounding remainder rides on the last row so the set lands on 100.
        $lastKey = (int) end($others);
        $values[$lastKey] = round(max(0, $values[$lastKey] + ($left - array_sum($values))), 2);

        foreach ($values as $i => $v) {
            $rows[$i]['fee_percent'] = $this->trimPercent($v);
        }

        return $rows;
    }

    /** Every row an even share of the 100% — the "Equal split" button. */
    private function equalSplitRows(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $keys  = array_keys($rows);
        $share = floor(100 / count($keys) * 100) / 100;
        $last  = count($keys) - 1;

        foreach ($keys as $n => $i) {
            $rows[$i]['fee_percent'] = $this->trimPercent(
                $n === $last ? round(100 - $share * $last, 2) : $share
            );
        }

        return $rows;
    }

    /** Rows still blank share out whatever the filled ones have left over. */
    private function fillBlankPercents(array $rows): array
    {
        $blank  = [];
        $filled = 0.0;
        foreach ($rows as $i => $r) {
            if (trim((string) ($r['fee_percent'] ?? '')) === '') {
                $blank[] = $i;
            } else {
                $filled += (float) $r['fee_percent'];
            }
        }

        if (empty($blank)) {
            return $rows;
        }

        $left  = max(0, round(100 - $filled, 2));
        $share = floor($left / count($blank) * 100) / 100;
        $last  = count($blank) - 1;

        foreach ($blank as $n => $i) {
            $rows[$i]['fee_percent'] = $this->trimPercent(
                $n === $last ? round($left - $share * $last, 2) : $share
            );
        }

        return $rows;
    }

    /** Nudge every row by the same amount until the set adds up to 100 again. */
    private function rebalanceToHundred(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $values = [];
        foreach ($rows as $i => $r) {
            $values[$i] = (float) ($r['fee_percent'] ?? 0);
        }

        $slice = (100 - array_sum($values)) / count($values);
        foreach ($values as $i => $v) {
            $values[$i] = round(max(0, $v + $slice), 2);
        }

        $keys    = array_keys($values);
        $lastKey = (int) end($keys);
        $values[$lastKey] = round(max(0, $values[$lastKey] + (100 - array_sum($values))), 2);

        foreach ($values as $i => $v) {
            $rows[$i]['fee_percent'] = $this->trimPercent($v);
        }

        return $rows;
    }

    /** What the rows currently add up to — shown live under the form. */
    public function getCustomPercentTotalProperty(): float
    {
        return round(collect($this->customRows)->sum(fn ($r) => (float) ($r['fee_percent'] ?? 0)), 2);
    }

    private function blankCustomRow(): array
    {
        return [
            'serial'          => $this->nextCustomSerial(),
            'start_date'      => '',
            'end_date'        => '',
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

    /** Edit button in the view card's header — swap the read-only card for the edit form. */
    public function editViewingCycle(): void
    {
        $id = $this->viewingCycle?->id;
        $this->closeCycleView();
        if ($id) {
            $this->openCycleModal($id);
        }
    }

    /** Delete button in the view card's header — close the card, ask to confirm. */
    public function deleteViewingCycle(): void
    {
        $id = $this->viewingCycle?->id;
        $this->closeCycleView();
        if ($id) {
            $this->deleteCycle($id);
        }
    }

    /**
     * Changing one installment's % re-splits what is left of the 100% across
     * the other installments of the same fee type + year, keeping their
     * relative weights. The result is previewed in the form and only written
     * when the installment is actually saved.
     */
    public function updatedCycleFeePercent(): void
    {
        $this->buildCycleSiblingPreview();
    }

    private function buildCycleSiblingPreview(): void
    {
        $this->cycleSiblingPreview = [];

        if (!$this->editCycleId || $this->editingToken) {
            return;
        }
        if (!is_numeric($this->cycleFeePercent)) {
            return;
        }
        $percent = (float) $this->cycleFeePercent;
        if ($percent < 0 || $percent > 100) {
            return;
        }

        $siblings = FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $this->cycleFeeType)
            ->where('academic_year', $this->cycleYear)
            ->where('is_token', false)
            ->where('id', '!=', $this->editCycleId)
            ->orderBy('payment_serial')
            ->get();

        if ($siblings->isEmpty()) {
            return;
        }

        $left    = max(0, round(100 - $percent, 2));
        $current = (float) $siblings->sum('fee_percent');
        $last    = $siblings->count() - 1;
        $running = 0.0;
        $preview = [];

        // The difference is shared out flat — every other installment gives up
        // (or takes back) the same slice and keeps the rest of its own number.
        $slice = ($left - $current) / $siblings->count();

        foreach ($siblings->values() as $n => $sibling) {
            $share = round(max(0, (float) $sibling->fee_percent + $slice), 2);
            // Remainder rides on the last one so the year lands exactly on 100%.
            $share = $n === $last ? round($left - $running, 2) : $share;
            $running += $share;

            $preview[] = [
                'id'      => $sibling->id,
                'serial'  => $sibling->payment_serial,
                'was'     => (float) $sibling->fee_percent,
                'percent' => max(0, $share),
            ];
        }

        $this->cycleSiblingPreview = $preview;
    }

    // ── Edit the whole cycle (the listing header's Edit button) ─────────────

    /**
     * Open the bulk editor for one fee type + academic year. Every installment
     * of that cycle comes in as its own row, labelled the way the cycle was
     * built: month names for a monthly cycle, quarters for a quarterly one,
     * plain installment numbers for a custom one.
     */
    public function openCycleEdit(string $feeType, string $year): void
    {
        $rows = FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $feeType)
            ->where('academic_year', $year)
            ->where('is_token', false)
            ->orderBy('payment_serial')
            ->get();

        if ($rows->isEmpty()) {
            $this->notification()->error('This cycle has no installments to edit yet.');
            return;
        }

        $this->editCycleFeeType = $feeType;
        $this->editCycleYear    = $year;
        $this->editCycleKind    = $this->detectCycleKind($rows);
        $this->editRows         = $rows->values()->map(fn (FeeCycle $c) => [
            'id'              => $c->id,
            'serial'          => (int) $c->payment_serial,
            'label'           => $this->cycleRowLabel($c, $this->editCycleKind),
            'start_date'      => optional($c->start_date)->toDateString() ?? '',
            'end_date'        => optional($c->end_date)->toDateString() ?? '',
            'due_date'        => optional($c->due_date)->toDateString(),
            'fee_percent'     => $this->trimPercent((float) $c->fee_percent),
            'penalty_per_day' => (string) $c->penalty_per_day,
        ])->all();

        $this->resetValidation();
        $this->cycleEditOpen = true;
    }

    public function closeCycleEdit(): void
    {
        $this->cycleEditOpen    = false;
        $this->editRows         = [];
        $this->editCycleFeeType = '';
        $this->editCycleYear    = '';
        $this->editCycleKind    = 'custom';
        $this->resetValidation();
    }

    /**
     * A % typed into one row moves only the difference: the other rows each
     * give up (or take back) an equal slice of it and otherwise keep their own
     * numbers, so the cycle still totals 100%.
     */
    public function updatedEditRows($value, $key): void
    {
        if (!str_ends_with((string) $key, '.fee_percent')) {
            return;
        }

        $index = (int) explode('.', (string) $key)[0];

        $this->editRows = trim((string) $value) === ''
            ? $this->fillBlankPercents($this->editRows)
            : $this->shiftPercentRows($this->editRows, $index);
    }

    /** Wipe the hand-tuning — an equal share each again. */
    public function resetEditPercents(): void
    {
        $this->editRows = $this->equalSplitRows($this->editRows);
    }

    /** What the editor's rows currently add up to — shown live in its footer. */
    public function getEditPercentTotalProperty(): float
    {
        return round(collect($this->editRows)->sum(fn ($r) => (float) ($r['fee_percent'] ?? 0)), 2);
    }

    public function saveCycleEdit(): void
    {
        if (empty($this->editRows)) {
            return;
        }

        foreach ($this->editRows as $r) {
            if (trim((string) ($r['due_date'] ?? '')) === '') {
                $this->notification()->error($r['label'] . ': pick a due date.');
                return;
            }
            if (!is_numeric($r['fee_percent'] ?? null) || (float) $r['fee_percent'] < 0 || (float) $r['fee_percent'] > 100) {
                $this->notification()->error($r['label'] . ': fee % must be between 0 and 100.');
                return;
            }
            $start = trim((string) ($r['start_date'] ?? ''));
            $end   = trim((string) ($r['end_date'] ?? ''));
            if (($start === '') !== ($end === '')) {
                $this->notification()->error($r['label'] . ': give both a start and an end date, or neither.');
                return;
            }
            if ($start !== '' && strtotime($end) < strtotime($start)) {
                $this->notification()->error($r['label'] . ': the end date cannot come before the start date.');
                return;
            }
        }

        // The automatic split always lands on 100% — this only catches a user
        // who pinned every row by hand to numbers that don't add up.
        $total = $this->editPercentTotal;
        if (abs($total - 100) > 0.01) {
            $this->notification()->error(
                'The installments must add up to 100% — they currently add up to ' . $this->trimPercent($total) . '%.'
            );
            return;
        }

        foreach ($this->editRows as $r) {
            FeeCycle::forOrg($this->orgId())->where('id', $r['id'])->update([
                'start_date'      => ($r['start_date'] ?? '') ?: null,
                'end_date'        => ($r['end_date'] ?? '') ?: null,
                'due_date'        => $r['due_date'],
                'fee_percent'     => $r['fee_percent'],
                'penalty_per_day' => $r['penalty_per_day'] ?: 0,
            ]);
        }

        $this->notification()->success(count($this->editRows) . ' installments updated — the cycle still totals 100%.');
        $this->closeCycleEdit();
    }

    /** The whole cycle as a CSV, straight to the browser. */
    public function downloadCycle(string $feeType, string $year): StreamedResponse
    {
        $rows = FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $feeType)
            ->where('academic_year', $year)
            ->orderByDesc('is_token')
            ->orderBy('payment_serial')
            ->get();

        $kind = $this->detectCycleKind($rows);
        $file = 'fee-cycle-' . $feeType . '-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', $year) . '.csv';

        return response()->streamDownload(function () use ($rows, $kind) {
            $out = fopen('php://output', 'w');
            // Excel only reads the ₹ column right if the file says up front it is UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Installment', 'Due Date', 'Period', 'Fee %', 'Amount', 'Penalty / Day', 'Academic Year', 'Status']);

            foreach ($rows as $c) {
                fputcsv($out, [
                    $c->is_token ? 'Token Fee' : $this->cycleRowLabel($c, $kind),
                    optional($c->due_date)->format('d M Y') ?? '',
                    ($c->start_date && $c->end_date)
                        ? $c->start_date->format('d M Y') . ' - ' . $c->end_date->format('d M Y')
                        : '',
                    $c->is_token ? '' : $this->trimPercent((float) $c->fee_percent) . '%',
                    $c->is_token ? number_format((float) $c->amount, 2, '.', '') : '',
                    number_format((float) $c->penalty_per_day, 2, '.', ''),
                    $c->academic_year,
                    $c->is_active ? 'Active' : 'Inactive',
                ]);
            }

            fclose($out);
        }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * How a saved cycle was built, read back off its rows: whole-month spans
     * are a monthly cycle, three-month spans a quarterly one, anything else
     * (including rows with no span at all) is custom.
     */
    public function detectCycleKind($installments): string
    {
        $rows = collect($installments)->filter(fn ($c) => !$c->is_token)->values();

        if ($rows->isEmpty() || $rows->contains(fn ($c) => !$c->start_date || !$c->end_date)) {
            return 'custom';
        }

        // Months a row covers, inclusive: Apr→Apr is 1, Apr→Jun is 3.
        $span = function ($c): int {
            $s = \Carbon\Carbon::parse($c->start_date);
            $e = \Carbon\Carbon::parse($c->end_date);
            return ($e->year - $s->year) * 12 + ($e->month - $s->month) + 1;
        };

        // Custom installments now carry spans of their own, so the shape only
        // counts as monthly/quarterly when the whole year is there: twelve
        // one-month rows, or four three-month ones.
        if ($rows->count() === 12 && $rows->every(fn ($c) => $span($c) === 1)) {
            return 'monthly';
        }
        if ($rows->count() === 4 && $rows->every(fn ($c) => $span($c) === 3)) {
            return 'quarterly';
        }

        return 'custom';
    }

    /** How one installment reads for its cycle kind — "April 2026", "Q1 · Apr–Jun", "Installment #3". */
    public function cycleRowLabel(FeeCycle $cycle, string $kind): string
    {
        if ($cycle->is_token) {
            return 'Token Fee';
        }

        $start = $cycle->start_date ? \Carbon\Carbon::parse($cycle->start_date) : null;
        $end   = $cycle->end_date ? \Carbon\Carbon::parse($cycle->end_date) : null;

        if ($kind === 'monthly' && $start) {
            return $start->format('F Y');
        }
        if ($kind === 'quarterly' && $start && $end) {
            $quarter = intdiv((($start->month - 4 + 12) % 12), 3) + 1;
            return 'Q' . $quarter . ' · ' . $start->format('M') . '–' . $end->format('M');
        }

        return 'Installment #' . $cycle->payment_serial;
    }

    /** A % without its trailing zeros — "35", "21.67". */
    private function trimPercent(float $value): string
    {
        $trimmed = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $trimmed === '' ? '0' : $trimmed;
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
            'cycleStartDate', 'cycleEndDate', 'cyclePenaltyPerDay', 'cycleFeePercent',
        ]);
        $this->cycleMode          = '';
        $this->cycleFeeType       = 'academic';
        $this->cycleSerial        = 1;
        $this->cyclePenaltyPerDay = '0';
        $this->cycleMonthlyDueDay = 10;
        $this->cycleYear          = $this->currentAcademicYear();
        $this->customRows         = [];
        $this->cycleSiblingPreview = [];
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
            'cycleStartDate'  => 'nullable|date',
            'cycleEndDate'    => 'nullable|date|after_or_equal:cycleStartDate',
            'cycleFeePercent' => 'required|numeric|min:0|max:100',
        ], [], ['cycleStartDate' => 'start date', 'cycleEndDate' => 'end date']);

        FeeCycle::forOrg($this->orgId())->where('id', $this->editCycleId)->update([
            'fee_type'        => $this->cycleFeeType,
            'payment_serial'  => $this->cycleSerial,
            // The span stays put — it is what tells the listing "Q1" from "April".
            'start_date'      => $this->cycleStartDate ?: null,
            'end_date'        => $this->cycleEndDate ?: null,
            'due_date'        => $this->cycleDueDate,
            'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
            'fee_percent'     => $this->cycleFeePercent,
            // Amount is computed per class from fee_percent × the class's own fee.
            'amount'          => 0,
            'academic_year'   => $this->cycleYear,
            'is_active'       => true,
        ]);
        // Re-split what is left of the 100% across the other installments.
        foreach ($this->cycleSiblingPreview as $sibling) {
            FeeCycle::forOrg($this->orgId())
                ->where('id', $sibling['id'])
                ->update(['fee_percent' => $sibling['percent']]);
        }

        $this->notification()->success(
            $this->cycleSiblingPreview
                ? 'Installment updated — the other installments were re-balanced to 100%.'
                : 'Installment updated!'
        );

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
            // A custom installment says for itself which stretch of the year it
            // covers — the listing prints that span, so both ends are required.
            if (trim((string) ($r['start_date'] ?? '')) === '' || trim((string) ($r['end_date'] ?? '')) === '') {
                $this->notification()->error("Row {$n}: pick both a start and an end date.");
                return;
            }
            if (strtotime($r['end_date']) < strtotime($r['start_date'])) {
                $this->notification()->error("Row {$n}: the end date cannot come before the start date.");
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
                    'start_date'      => $r['start_date'],
                    'end_date'        => $r['end_date'],
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

        // The listing is one card per fee type + academic year, each with its
        // own header (what the cycle is, plus Edit / Download / Print).
        $cycleGroups = $cycles
            ->groupBy(fn ($c) => $c->fee_type . '|' . $c->academic_year)
            ->map(function ($rows, $key) {
                [$feeType, $year] = array_pad(explode('|', (string) $key, 2), 2, '');
                $installments = $rows->where('is_token', false);
                $kind         = $this->detectCycleKind($installments);

                return [
                    'key'      => 'cycle-group-' . preg_replace('/[^A-Za-z0-9]+/', '-', (string) $key),
                    'fee_type' => $feeType,
                    'year'     => $year,
                    'kind'     => $kind,
                    'count'    => $installments->count(),
                    'percent'  => round((float) $installments->sum('fee_percent'), 2),
                    'token'    => $rows->firstWhere('is_token', true),
                    // Token first, then the installments in order.
                    'rows'     => $rows
                        ->sortBy(fn ($c) => ($c->is_token ? '0' : '1') . str_pad((string) $c->payment_serial, 2, '0', STR_PAD_LEFT))
                        ->values(),
                ];
            })
            ->values();

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
            'cycleGroups'      => $cycleGroups,
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
