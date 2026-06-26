<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeStructurePdfController extends Controller
{
    /**
     * Printable / downloadable academic fee structure, grouped by class +
     * section with per-group totals and a grand total. ?download=1 streams a
     * PDF; otherwise a print-ready HTML page is returned.
     */
    public function show(Request $request, $organization)
    {
        $orgId = Auth::user()->organization_id;
        $org   = Organization::find($orgId);

        $rows = FeeStructure::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('fee_type', 'academic')
            ->when($request->filled('standard'), fn ($q) => $q->where('standard_id', $request->standard))
            ->when($request->filled('section'), fn ($q) => $q->where('section_id', $request->section))
            ->orderBy('standard_id')->orderBy('section_id')
            ->get();

        // Group by class + section so the PDF mirrors the on-screen cards.
        $groups = $rows->groupBy(fn ($r) => $r->standard_id . '-' . ($r->section_id ?? 0))
            ->map(function ($g) {
                $first = $g->first();
                return [
                    'class'   => $first->standard->name ?? '—',
                    'section' => $first->section->name ?? 'All Sections',
                    'year'    => $first->academic_year,
                    'rows'    => $g->values(),
                    'total'   => (float) $g->sum('amount'),
                ];
            })->values();

        $grandTotal = (float) $rows->sum('amount');

        $filterLabel = null;
        if ($request->filled('standard')) {
            $std = Standard::find($request->standard);
            $sec = $request->filled('section') ? Section::find($request->section) : null;
            $filterLabel = trim(($std->name ?? '') . ($sec ? ' — ' . $sec->name : ''));
        }

        $data = [
            'org'         => $org,
            'groups'      => $groups,
            'grandTotal'  => $grandTotal,
            'filterLabel' => $filterLabel,
            'generatedAt' => now(),
        ];

        if ($request->boolean('download')) {
            return Pdf::loadView('admin.fee-structure-pdf', $data)
                ->setPaper('a4', 'portrait')
                ->stream('fee-structure.pdf');
        }

        // Printable HTML (the view shows a Print button when not in PDF mode).
        return view('admin.fee-structure-pdf', $data + ['printable' => true]);
    }
}
