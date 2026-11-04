<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdmin\SuperAdminLedgerTransaction;
use App\Services\SuperAdminLedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → Ledger: the school Ledger's screen for the platform's own
 * money. Fees received from the schools are its credits and salaries paid
 * its expenses, on their own (SuperAdminLedgerService); credits and expenses
 * made by hand can be added, changed in full and deleted.
 */
class Ledger extends Component
{
    use WireUiActions, WithPagination;

    public string $startDate = '';
    public string $endDate   = '';
    public string $typeFilter   = '';   // '' | credit | expense
    public string $sourceFilter = '';   // '' | School Fee | Messaging Wallet | Salary | Fee Correction | Manual
    public string $search       = '';

    // ─── Add / edit slide-in ────────────────────────────────────────────────
    public bool   $showModal    = false;
    public string $modalType    = '';   // credit | expense ('' = not picked yet)
    public ?int   $editingId    = null;
    public string $mDate        = '';
    public        $mAmount      = '';
    public string $mParty       = '';
    public string $mPartyTo     = '';
    public string $mCollectedBy = '';
    public string $mMode        = 'Cash';
    public string $mReason      = '';

    public array $modes = ['Cash', 'UPI', 'Bank Transfer', 'Cheque', 'Card', 'Online', 'Other'];

    public const SOURCES = ['School Fee', 'Messaging Wallet', 'Salary', 'Fee Correction', 'Manual'];

    /** Where the header's Credit and its Expense come from, in the order their breakdowns list them. */
    public const CREDIT_SOURCES  = ['School Fee', 'Messaging Wallet', 'Manual'];
    public const EXPENSE_SOURCES = ['Salary', 'Fee Correction', 'Manual'];

    protected $queryString = [
        'startDate' => ['except' => '', 'history' => false],
        'endDate'   => ['except' => '', 'history' => false],
    ];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate   = now()->toDateString();
    }

    public function updatedStartDate(): void { $this->resetPage(); }
    public function updatedEndDate(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }
    public function updatedSourceFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }

    public function thisMonth(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate   = now()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function overall(): void
    {
        $this->startDate = '';
        $this->endDate   = '';
        $this->resetPage();
    }

    /** A line of the header's Credit / Expense breakdown: list just those entries; the same line again shows everything. */
    public function pickSource(string $type, string $source): void
    {
        if (!in_array($type, ['credit', 'expense'], true) || !in_array($source, self::SOURCES, true)) {
            return;
        }

        $same = $this->typeFilter === $type && $this->sourceFilter === $source;
        $this->typeFilter   = $same ? '' : $type;
        $this->sourceFilter = $same ? '' : $source;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->startDate    = now()->startOfMonth()->toDateString();
        $this->endDate      = now()->toDateString();
        $this->typeFilter   = '';
        $this->sourceFilter = '';
        $this->search       = '';
        $this->resetPage();
    }

    // ─── Export ─────────────────────────────────────────────────────────────

    /** The statement PDF for the period picked in the Export popup ('range', 'day' or 'all'). */
    public function exportStatement(string $mode = 'range', string $from = '', string $to = '', string $day = ''): ?StreamedResponse
    {
        try {
            if ($mode === 'all') {
                $start = $end = null;
            } elseif ($mode === 'day') {
                if ($day === '') return null;
                $start = Carbon::parse($day)->startOfDay();
                $end   = Carbon::parse($day)->endOfDay();
            } else {
                if ($from === '' || $to === '') return null;
                $start = Carbon::parse($from)->startOfDay();
                $end   = Carbon::parse($to)->endOfDay();
            }
        } catch (\Throwable $e) {
            return null;
        }

        [$bytes, $fileName] = SuperAdminLedgerService::statementPdf($start, $end, $mode === 'all');

        return response()->streamDownload(fn () => print($bytes), $fileName, ['Content-Type' => 'application/pdf']);
    }

    // ─── Entries made by hand ───────────────────────────────────────────────

    public function openAdd(): void
    {
        $this->resetValidation();
        $this->editingId    = null;
        $this->modalType    = '';
        $this->mDate        = now()->toDateString();
        $this->mAmount      = '';
        $this->mParty       = '';
        $this->mPartyTo     = '';
        $this->mCollectedBy = '';
        $this->mMode        = 'Cash';
        $this->mReason      = '';
        $this->showModal    = true;
    }

    public function chooseType(string $type): void
    {
        if (in_array($type, ['credit', 'expense'], true)) {
            $this->resetValidation();
            $this->modalType = $type;
        }
    }

    public function openEdit(int $id): void
    {
        $txn = SuperAdminLedgerTransaction::find($id);
        if (!$txn) {
            return;
        }

        $this->resetValidation();
        $this->editingId    = $txn->id;
        $this->modalType    = $txn->type === 'expense' ? 'expense' : 'credit';
        $this->mDate        = Carbon::parse($txn->txn_date)->toDateString();
        $this->mAmount      = rtrim(rtrim(number_format((float) $txn->amount, 2, '.', ''), '0'), '.') ?: '0';
        $this->mParty       = (string) $txn->party;
        $this->mPartyTo     = $this->modalType === 'expense' ? (string) $txn->party_to : '';
        $this->mCollectedBy = $this->modalType === 'credit' ? (string) $txn->party_to : '';
        $this->mMode        = $txn->mode ?: 'Cash';
        $this->mReason      = (string) $txn->reason;
        $this->showModal    = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
    }

    public function saveManual(): void
    {
        if (!in_array($this->modalType, ['credit', 'expense'], true)) {
            return;
        }
        $isExpense = $this->modalType === 'expense';

        $this->validate([
            'mDate'        => 'required|date',
            'mAmount'      => 'required|numeric|min:0|max:99999999',
            'mParty'       => 'required|string|max:255',
            'mPartyTo'     => ($isExpense ? 'required' : 'nullable') . '|string|max:255',
            'mCollectedBy' => 'nullable|string|max:255',
            'mMode'        => 'required|string|max:50',
            'mReason'      => 'required|string|max:1000',
        ], [], [
            'mDate' => 'date', 'mAmount' => 'amount', 'mParty' => 'from', 'mPartyTo' => 'to',
            'mCollectedBy' => 'collected by', 'mMode' => 'mode', 'mReason' => 'remark',
        ]);

        $values = [
            'type'     => $isExpense ? 'expense' : 'credit',
            'amount'   => round((float) $this->mAmount, 2),
            'txn_date' => $this->mDate,
            'party'    => trim($this->mParty) ?: null,
            'party_to' => $isExpense ? (trim($this->mPartyTo) ?: null) : (trim($this->mCollectedBy) ?: null),
            'mode'     => $this->mMode ?: null,
            'reason'   => trim($this->mReason),
        ];

        if ($this->editingId) {
            $txn = SuperAdminLedgerTransaction::find($this->editingId);
            if (!$txn) {
                $this->closeModal();
                return;
            }
            $txn->update($values + ['updated_by' => Auth::id()]);
            $message = ($isExpense ? 'Expense' : 'Credit') . ' updated.';
        } else {
            SuperAdminLedgerTransaction::create($values + ['created_by' => Auth::id()]);
            $message = ($isExpense ? 'Expense' : 'Credit') . ' added.';
        }

        $this->closeModal();
        $this->notification()->success($message);
    }

    public function deleteManual(int $id): void
    {
        $txn = SuperAdminLedgerTransaction::find($id);
        if (!$txn) {
            return;
        }

        $label = $txn->type === 'expense' ? 'Expense' : 'Credit';
        $txn->delete();
        $this->notification()->success($label . ' of ₹' . number_format((float) $txn->amount, 2) . ' deleted.');
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : null;
        $end   = $this->endDate ? Carbon::parse($this->endDate) : null;

        $opening = SuperAdminLedgerService::openingBalance($start);
        $all     = SuperAdminLedgerService::withBalance(SuperAdminLedgerService::entries($start, $end), $opening);
        $closing = round((float) ($all->last()['balance'] ?? $opening), 2);

        // The type, source and search narrow the list; the balance stays the whole statement's.
        $needle  = mb_strtolower(trim($this->search));
        $entries = $all
            ->when($this->typeFilter !== '', fn ($c) => $c->where('type', $this->typeFilter))
            ->when($this->sourceFilter !== '', fn ($c) => $c->where('source', $this->sourceFilter))
            ->when($needle !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower(implode(' ', [
                $r['reason'], $r['from'], $r['to'], $r['mode'] ?? '', $r['receipt'] ?? '',
            ])), $needle)))
            ->reverse()
            ->values();

        $periodCredit  = round($all->where('type', 'credit')->sum('amount'), 2);
        $periodExpense = round($all->where('type', 'expense')->sum('amount'), 2);

        // The header's Credit and Expense split by source (a manual entry counts on its own side).
        $split = fn (string $type) => $all->where('type', $type)->groupBy('source')
            ->map(fn ($rows) => round($rows->sum('amount'), 2))->all();

        $page      = $this->getPage();
        $perPage   = 50;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.super-admin.ledger', [
            'entries'        => $paginated,
            'openingBalance' => $opening,
            'closingBalance' => $closing,
            'periodCredit'   => $periodCredit,
            'periodExpense'  => $periodExpense,
            'periodNet'      => round($periodCredit - $periodExpense, 2),
            'creditBySource'  => $split('credit'),
            'expenseBySource' => $split('expense'),
            'isThisMonth'    => $this->startDate === now()->startOfMonth()->toDateString()
                && in_array($this->endDate, [now()->toDateString(), now()->endOfMonth()->toDateString()], true),
            'isOverall'      => $this->startDate === '' && $this->endDate === '',
            'filtered'       => $this->typeFilter !== '' || $this->sourceFilter !== '' || $needle !== '',
        ]);
    }
}
