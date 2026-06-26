<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\LedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LedgerStatementController extends Controller
{
    /**
     * Professional bank-statement style ledger PDF for a date range.
     *
     * GET .../ledger/statement?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     * Optional ?month=YYYY-MM overrides the range with that whole month.
     */
    public function download(Request $request, $organization)
    {
        $orgId = Auth::user()->organization_id;

        // Resolve the window. ?month wins; otherwise start/end; otherwise the
        // current calendar month. Guard against reversed ranges.
        if ($request->filled('month')) {
            try {
                $m     = Carbon::createFromFormat('Y-m', $request->month);
                $start = $m->copy()->startOfMonth();
                $end   = $m->copy()->endOfMonth();
            } catch (\Throwable $e) {
                $start = now()->startOfMonth();
                $end   = now()->endOfMonth();
            }
        } else {
            $start = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : now()->startOfMonth();
            $end = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : now()->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $org      = Organization::find($orgId);
        $entries  = LedgerService::entries($orgId, $start, $end);
        $opening  = LedgerService::openingBalance($orgId, $start);

        // Running balance + period totals.
        $balance = $opening;
        $totalCredit = 0.0;
        $totalExpense = 0.0;
        $rows = $entries->map(function ($row) use (&$balance, &$totalCredit, &$totalExpense) {
            if ($row['type'] === 'credit') {
                $balance += $row['amount'];
                $totalCredit += $row['amount'];
            } else {
                $balance -= $row['amount'];
                $totalExpense += $row['amount'];
            }
            $row['balance'] = round($balance, 2);
            return $row;
        });

        // Resolve a dompdf-friendly logo source (http URL or local file path).
        $logoSrc = null;
        if (!empty($org?->logo)) {
            if (\Illuminate\Support\Str::startsWith($org->logo, ['http://', 'https://'])) {
                $logoSrc = $org->logo;
            } elseif (file_exists(public_path('storage/' . $org->logo))) {
                $logoSrc = public_path('storage/' . $org->logo);
            }
        }

        $data = [
            'org'          => $org,
            'logoSrc'      => $logoSrc,
            'rows'         => $rows,
            'opening'      => round($opening, 2),
            'closing'      => round($balance, 2),
            'totalCredit'  => round($totalCredit, 2),
            'totalExpense' => round($totalExpense, 2),
            'start'        => $start,
            'end'          => $end,
            'generatedAt'  => now(),
            'netBalance'   => LedgerService::netBalance($orgId),
        ];

        $pdf = Pdf::loadView('admin.ledger-statement', $data)->setPaper('a4', 'portrait');

        $fileName = 'ledger_statement_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.pdf';

        return $pdf->stream($fileName);
    }
}
