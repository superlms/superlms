<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Services\LedgerService;
use App\Support\PdfFonts;
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

        // Resolve the window. ?overall wins (all-time, no window); then ?month;
        // then start/end; otherwise the current calendar month. Guard against
        // reversed ranges.
        $overall = $request->boolean('overall');

        if ($overall) {
            $start = null;
            $end   = null;
        } elseif ($request->filled('month')) {
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

        if ($start && $end && $start->gt($end)) {
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
            'contact'      => $this->contactLine($orgId, $org),
            'rows'         => $rows,
            'opening'      => round($opening, 2),
            'closing'      => round($balance, 2),
            'totalCredit'  => round($totalCredit, 2),
            'totalExpense' => round($totalExpense, 2),
            'start'        => $start,
            'end'          => $end,
            'overall'      => $overall,
            'generatedAt'  => now(),
            'netBalance'   => LedgerService::netBalance($orgId),
        ];

        $fileName = $overall
            ? 'ledger_statement_all_' . now()->format('Ymd') . '.pdf'
            : 'ledger_statement_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.pdf';

        return $this->render($data)->stream($fileName);
    }

    /**
     * Load the statement with our bundled Poppins faces. dompdf needs a
     * writable font cache; if that fails we still render, just with the
     * default face rather than no PDF at all.
     */
    protected function render(array $data)
    {
        $fontCache = storage_path('fonts');
        if (!is_dir($fontCache)) {
            @mkdir($fontCache, 0775, true);
        }

        $load = fn (string $fontCss) => Pdf::loadView('admin.ledger-statement', $data + compact('fontCss'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 130)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontCache)
            ->setOption('fontCache', $fontCache)
            ->setOption('defaultFont', 'DejaVu Sans');

        try {
            return $load(PdfFonts::faceCss());
        } catch (\Throwable $e) {
            logger()->warning('Ledger statement font embedding failed: ' . $e->getMessage());
            return $load('');
        }
    }

    /**
     * Address / email / website / phone for the masthead. The school record is
     * the authority; the website builder's SchoolInfo fills the gaps and is the
     * only place a website address is kept.
     */
    protected function contactLine(int $orgId, ?Organization $org): array
    {
        $info = null;
        try {
            $info = SchoolInfo::where('organization_id', $orgId)->first();
        } catch (\Throwable $e) {
            // school_infos may not exist on an older schema — masthead still renders.
        }

        return [
            'address' => $org?->address ?: ($info->school_address ?? null),
            'email'   => $org?->email ?: ($info->school_email ?? null),
            'mobile'  => $org?->mobile_number ?: ($info->school_mobile ?? null),
            'website' => $info->website_url ?? null,
        ];
    }
}
