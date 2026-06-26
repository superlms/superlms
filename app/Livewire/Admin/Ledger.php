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

    // ─── Manual entry modal ──────────────────────────────────────────────────
    public bool $showModal   = false;
    public string $modalType = 'credit'; // 'credit' | 'expense'
    public string $mDate     = '';
    public $mAmount          = '';
    public string $mParty    = '';
    public string $mReason   = '';

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

    public function updatedStartDate(): void { $this->resetPage(); }
    public function updatedEndDate(): void   { $this->resetPage(); }

    /** Jump the window to a whole month (YYYY-MM). */
    public function setMonth(string $month): void
    {
        try {
            $m = Carbon::createFromFormat('Y-m', $month);
            $this->startDate = $m->copy()->startOfMonth()->toDateString();
            $this->endDate   = $m->copy()->endOfMonth()->toDateString();
            $this->resetPage();
        } catch (\Throwable $e) {
            // ignore malformed month
        }
    }

    public function thisMonth(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate   = now()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    // ─── Manual entry ─────────────────────────────────────────────────────────

    public function openCredit(): void  { $this->openModal('credit'); }
    public function openExpense(): void { $this->openModal('expense'); }

    protected function openModal(string $type): void
    {
        $this->resetValidation();
        $this->modalType = $type;
        $this->mDate     = now()->toDateString();
        $this->mAmount   = '';
        $this->mParty    = '';
        $this->mReason   = '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function saveManual(): void
    {
        $this->validate([
            'mDate'   => 'required|date',
            'mAmount' => 'required|numeric|min:0.01',
            'mParty'  => 'nullable|string|max:255',
            'mReason' => 'required|string|max:1000',
        ], [], [
            'mDate'   => 'date',
            'mAmount' => 'amount',
            'mParty'  => 'by',
            'mReason' => 'reason',
        ]);

        LedgerTransaction::create([
            'organization_id' => Auth::user()->organization_id,
            'type'            => $this->modalType === 'expense' ? 'expense' : 'credit',
            'amount'          => $this->mAmount,
            'txn_date'        => $this->mDate,
            'party'           => $this->mParty ?: null,
            'reason'          => $this->mReason,
            'created_by'      => Auth::id(),
        ]);

        $this->showModal = false;
        session()->flash('ledger_msg', ($this->modalType === 'expense' ? 'Expense' : 'Credit') . ' added successfully.');
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
        ]);
    }
}
