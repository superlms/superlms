<?php

namespace App\Livewire\Admin;

use App\Models\Admin\LedgerTransaction;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Ledger extends Component
{
    use WithPagination;

    // ─── Date filter (statement window) ──────────────────────────────────────
    public string $startDate = '';
    public string $endDate   = '';
    public string $singleDate = '';   // "on this exact day" quick filter

    // ─── Manual entry modal ──────────────────────────────────────────────────
    public bool $showModal   = false;
    public string $modalType = 'credit'; // 'credit' | 'expense'
    public string $mDate     = '';
    public $mAmount          = '';
    public string $mParty    = '';   // "By / From"
    public string $mPartyTo  = '';   // "To" (expenses)
    public string $mMode     = '';   // payment mode
    public string $mReason   = '';
    public string $mCollectedBy = ''; // credit only: staff member who collected the money

    /** Manual entry currently being edited (null = adding a new one). */
    public ?int $editingId = null;

    /** Selectable payment modes for manual entries. */
    public array $modes = ['Cash', 'UPI', 'Bank Transfer', 'Cheque', 'Card', 'Other'];

    // ─── Delete confirm overlay ──────────────────────────────────────────────
    public bool $showDeleteConfirm = false;
    public $deleteId = null;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate'   => ['except' => ''],
    ];

    public function mount(): void
    {
        // Default window: current calendar month → today.
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate   = now()->toDateString();
    }

    public function updatedStartDate(): void { $this->singleDate = ''; $this->resetPage(); }
    public function updatedEndDate(): void   { $this->singleDate = ''; $this->resetPage(); }

    /** Jump the window to a whole month (YYYY-MM). */
    public function setMonth(string $month): void
    {
        try {
            $m = Carbon::createFromFormat('Y-m', $month);
            $this->startDate  = $m->copy()->startOfMonth()->toDateString();
            $this->endDate    = $m->copy()->endOfMonth()->toDateString();
            $this->singleDate = '';
            $this->resetPage();
        } catch (\Throwable $e) {
            // ignore malformed month
        }
    }

    /** Pin the window to a single calendar day. */
    public function updatedSingleDate(string $value): void
    {
        if ($value === '') return;
        $this->startDate = $value;
        $this->endDate   = $value;
        $this->resetPage();
    }

    public function thisMonth(): void
    {
        $this->startDate  = now()->startOfMonth()->toDateString();
        $this->endDate    = now()->endOfMonth()->toDateString();
        $this->singleDate = '';
        $this->resetPage();
    }

    /** Show everything ever recorded (no date window). */
    public function overall(): void
    {
        $this->startDate  = '';
        $this->endDate    = '';
        $this->singleDate = '';
        $this->resetPage();
    }

    // ─── Manual entry ─────────────────────────────────────────────────────────

    public function openCredit(): void  { $this->openModal('credit'); }
    public function openExpense(): void { $this->openModal('expense'); }

    protected function openModal(string $type): void
    {
        $this->resetValidation();
        $this->editingId    = null;
        $this->modalType    = $type;
        $this->mDate        = now()->toDateString();
        $this->mAmount      = '';
        $this->mParty       = '';
        $this->mPartyTo     = '';
        $this->mCollectedBy = '';
        $this->mMode        = 'Cash';
        $this->mReason      = '';
        $this->showModal    = true;
    }

    /** Load an existing manual entry into the same slide-in for editing. */
    public function openEdit(int $id): void
    {
        $txn = LedgerTransaction::where('organization_id', Auth::user()->organization_id)->find($id);
        if (!$txn) return;

        $this->resetValidation();
        $this->editingId    = $txn->id;
        $this->modalType    = $txn->type === 'expense' ? 'expense' : 'credit';
        $this->mDate        = Carbon::parse($txn->txn_date)->toDateString();
        $this->mAmount      = $txn->amount;
        $this->mParty       = $txn->party ?? '';
        // party_to doubles as "To" (expense) or "Collected by" (credit).
        $this->mPartyTo     = $this->modalType === 'expense' ? ($txn->party_to ?? '') : '';
        $this->mCollectedBy = $this->modalType === 'credit'  ? ($txn->party_to ?? '') : '';
        $this->mMode        = $txn->mode ?: 'Cash';
        $this->mReason      = $txn->reason ?? '';
        $this->showModal    = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
    }

    public function saveManual(): void
    {
        $this->validate([
            'mDate'        => 'required|date',
            'mAmount'      => 'required|numeric|min:0.01',
            'mParty'       => 'nullable|string|max:255',
            'mPartyTo'     => 'nullable|string|max:255',
            'mCollectedBy' => 'nullable|string|max:255',
            'mMode'        => 'nullable|string|max:50',
            'mReason'      => 'required|string|max:1000',
        ], [], [
            'mDate'        => 'date',
            'mAmount'      => 'amount',
            'mParty'       => 'from',
            'mPartyTo'     => 'to',
            'mCollectedBy' => 'collected by',
            'mMode'        => 'mode',
            'mReason'      => 'remark',
        ]);

        $isExpense = $this->modalType === 'expense';

        // party_to stores the payee "To" for expenses, or "Collected by" for credits.
        $payload = [
            'type'     => $isExpense ? 'expense' : 'credit',
            'amount'   => $this->mAmount,
            'txn_date' => $this->mDate,
            'party'    => $this->mParty ?: null,
            'party_to' => $isExpense ? ($this->mPartyTo ?: null) : ($this->mCollectedBy ?: null),
            'mode'     => $this->mMode ?: null,
            'reason'   => $this->mReason,
        ];

        if ($this->editingId) {
            LedgerTransaction::where('organization_id', Auth::user()->organization_id)
                ->where('id', $this->editingId)
                ->update($payload);
            $msg = ($isExpense ? 'Expense' : 'Credit') . ' updated successfully.';
        } else {
            LedgerTransaction::create($payload + [
                'organization_id' => Auth::user()->organization_id,
                'created_by'      => Auth::id(),
            ]);
            $msg = ($isExpense ? 'Expense' : 'Credit') . ' added successfully.';
        }

        $this->showModal = false;
        $this->editingId = null;
        session()->flash('ledger_msg', $msg);
    }

    // ─── Delete (manual entries only) ────────────────────────────────────────

    public function confirmDelete($id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    public function deleteManual(): void
    {
        if ($this->deleteId) {
            LedgerTransaction::where('organization_id', Auth::user()->organization_id)
                ->where('id', $this->deleteId)
                ->delete();
            session()->flash('ledger_msg', 'Manual entry deleted.');
        }
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId = Auth::user()->organization_id;
        $start = $this->startDate ? Carbon::parse($this->startDate) : null;
        $end   = $this->endDate ? Carbon::parse($this->endDate) : null;

        $entries = LedgerService::entries($orgId, $start, $end);

        // Running balance starts from the opening balance carried into the window.
        $opening = LedgerService::openingBalance($orgId, $start);
        $balance = $opening;
        $entries = $entries->map(function ($row) use (&$balance) {
            $balance += $row['type'] === 'credit' ? $row['amount'] : -$row['amount'];
            $row['balance'] = round($balance, 2);
            return $row;
        });
        $closing = round($balance, 2);

        // Newest first for on-screen review (PDF keeps oldest-first running order).
        $entries = $entries->reverse()->values();

        $periodCredit  = LedgerService::creditSum($orgId, $start, $end);
        $periodExpense = LedgerService::expenseSum($orgId, $start, $end);

        // Month dropdown options (last 24 months, newest first) so the picker
        // can show a real "Select month" prompt instead of a native "---".
        $monthOptions = collect(range(0, 23))->map(function ($i) {
            $m = now()->startOfMonth()->subMonths($i);
            return ['value' => $m->format('Y-m'), 'label' => $m->format('F Y')];
        });

        // Paginate the in-memory collection.
        $page    = $this->getPage();
        $perPage = 15;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.admin.ledger', [
            'entries'        => $paginated,
            'netBalance'     => LedgerService::netBalance($orgId),
            'openingBalance' => $opening,
            'closingBalance' => $closing,
            'periodCredit'   => $periodCredit,
            'periodExpense'  => $periodExpense,
            'monthOptions'   => $monthOptions,
            'isOverall'      => $this->startDate === '' && $this->endDate === '',
        ]);
    }
}
