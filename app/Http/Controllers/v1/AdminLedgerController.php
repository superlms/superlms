<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Admin\LedgerStatementController;
use App\Models\Admin\LedgerTransaction;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The admin app's Ledger — app/Livewire/Admin/Ledger.php over the API.
 *
 * Reads the same LedgerService as the panel and the accounts panel, so the
 * three always agree: fee, transport, admission and salary rows live in their
 * own tables and only manual credits and expenses are kept here, editable for
 * LedgerTransaction::EDIT_WINDOW_DAYS days after they were recorded.
 */
class AdminLedgerController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /** The modes a manual entry may carry — the panel's list. */
    public const MODES = ['Cash', 'UPI', 'Bank Transfer', 'Cheque', 'Card', 'Other'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    /**
     * The window asked for: ?overall=1 for all time, else ?month=YYYY-MM,
     * else start_date/end_date, else this month so far — the panel's default.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: bool}
     */
    private function window(Request $request): array
    {
        if ($request->boolean('overall')) {
            return [null, null, true];
        }

        if ($request->filled('month')) {
            try {
                $m = Carbon::createFromFormat('Y-m', $request->month);
                return [$m->copy()->startOfMonth(), $m->copy()->endOfMonth(), false];
            } catch (\Throwable) {
                // fall through to the default window
            }
        }

        $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth();
        $end   = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end, false];
    }

    /**
     * GET /admin/ledger?start_date=&end_date= | ?month=YYYY-MM | ?overall=1 &page=&per_page=
     *
     * The statement, newest first, each row with the balance after it; the
     * period's opening and closing balance, credits and expenses; and the
     * all-time net balance.
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        [$start, $end, $overall] = $this->window($request);

        $entries = LedgerService::entries($orgId, $start, $end);
        $opening = LedgerService::openingBalance($orgId, $start);

        $balance = $opening;
        $entries = $entries->map(function ($row) use (&$balance) {
            $balance += $row['type'] === 'credit' ? $row['amount'] : -$row['amount'];
            $row['balance'] = round($balance, 2);
            return $row;
        });
        $closing = round($balance, 2);

        // Newest first on screen, as on the panel; the PDF keeps oldest first.
        $entries = $entries->reverse()->values();

        $perPage = max(5, min(100, (int) $request->input('per_page', 30)));
        $page    = max(1, (int) $request->input('page', 1));
        $total   = $entries->count();

        $rows = $entries->forPage($page, $perPage)->values()->map(fn ($r) => [
            'date'         => $r['date']->toDateString(),
            'time'         => $r['time'] ?? null,
            'type'         => $r['type'],
            'amount'       => round((float) $r['amount'], 2),
            'source'       => $r['source'],
            'from'         => $r['from'] ?? null,
            'to'           => $r['to'] ?? null,
            'mode'         => $r['mode'] ?? null,
            'party'        => $r['party'] ?? null,
            'reason'       => $r['reason'] ?? null,
            'manual_id'    => $r['manual_id'] ?? null,
            'editable'     => (bool) ($r['editable'] ?? false),
            'collected_by' => $r['collected_by'] ?? null,
            'balance'      => $r['balance'],
        ]);

        return $this->success([
            'entries'    => $rows,
            'pagination' => [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
            'summary'    => [
                'net_balance'    => round(LedgerService::netBalance($orgId), 2),
                'opening'        => round($opening, 2),
                'closing'        => $closing,
                'period_credit'  => round(LedgerService::creditSum($orgId, $start, $end), 2),
                'period_expense' => round(LedgerService::expenseSum($orgId, $start, $end), 2),
            ],
            'window'     => [
                'overall'    => $overall,
                'start_date' => $start?->toDateString(),
                'end_date'   => $end?->toDateString(),
            ],
            'modes'            => self::MODES,
            'edit_window_days' => LedgerTransaction::EDIT_WINDOW_DAYS,
        ], 'Ledger fetched.');
    }

    /** The panel's rules for a manual entry. */
    private function validated(Request $request): array|\Illuminate\Http\JsonResponse
    {
        if ($err = $this->validateWith($request, [
            'type'         => 'required|in:credit,expense',
            'date'         => 'required|date',
            // 0 is a legitimate entry (a waived / nil line), so it saves as 0.
            'amount'       => 'required|numeric|min:0',
            'party'        => 'nullable|string|max:255',
            'party_to'     => 'nullable|string|max:255',
            'collected_by' => 'nullable|string|max:255',
            'mode'         => 'nullable|string|max:50',
            'reason'       => 'required|string|max:1000',
        ])) return $err;

        $isExpense = $request->type === 'expense';

        // party_to holds the payee "To" for an expense, or "Collected by" for a credit.
        return [
            'type'     => $isExpense ? 'expense' : 'credit',
            'amount'   => (float) $request->amount,
            'txn_date' => $request->date,
            'party'    => $request->party ?: null,
            'party_to' => $isExpense ? ($request->party_to ?: null) : ($request->collected_by ?: null),
            'mode'     => $request->mode ?: null,
            'reason'   => $request->reason,
        ];
    }

    /** POST /admin/ledger — a manual credit or expense. */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $payload = $this->validated($request);
        if (!is_array($payload)) return $payload;

        $txn = LedgerTransaction::create($payload + [
            'organization_id' => $user->organization_id,
            'created_by'      => $user->id,
        ]);

        return $this->success(['id' => $txn->id], ($payload['type'] === 'expense' ? 'Expense' : 'Credit') . ' added successfully.');
    }

    /** POST /admin/ledger/{id} — correct a manual entry inside its window. */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $txn = LedgerTransaction::where('organization_id', $user->organization_id)->find($id);
        if (!$txn) return $this->error('Entry not found.', 404);

        if (!$txn->isEditable()) {
            return $this->error('This entry is older than ' . LedgerTransaction::EDIT_WINDOW_DAYS . ' days and can no longer be edited.', 422);
        }

        $payload = $this->validated($request);
        if (!is_array($payload)) return $payload;

        $txn->update($payload);

        return $this->success(['id' => $txn->id], ($payload['type'] === 'expense' ? 'Expense' : 'Credit') . ' updated successfully.');
    }

    /** GET /admin/ledger/entry/{id} — one manual entry, to edit it. */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $txn = LedgerTransaction::where('organization_id', $user->organization_id)->find($id);
        if (!$txn) return $this->error('Entry not found.', 404);

        return $this->success([
            'id'           => $txn->id,
            'type'         => $txn->type === 'expense' ? 'expense' : 'credit',
            'date'         => Carbon::parse($txn->txn_date)->toDateString(),
            'amount'       => (float) $txn->amount,
            'party'        => $txn->party,
            'party_to'     => $txn->type === 'expense' ? $txn->party_to : null,
            'collected_by' => $txn->type === 'credit' ? $txn->party_to : null,
            'mode'         => $txn->mode,
            'reason'       => $txn->reason,
            'editable'     => $txn->isEditable(),
        ], 'Entry fetched.');
    }

    /**
     * GET /admin/ledger/statement — the panel's PDF statement for the same
     * window (?start_date=&end_date=, ?month=, ?overall=1).
     */
    public function statement(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return app(LedgerStatementController::class)->download($request, $user->organization_id);
    }
}
