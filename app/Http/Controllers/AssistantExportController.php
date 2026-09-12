<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Download as PDF" for a table the LMS assistant has already shown on screen.
 *
 * The panel sends ROWS, never HTML: headers and cells arrive as plain strings,
 * are length-clamped by the validator and escaped by the Blade template, so
 * nothing from the browser can smuggle markup — or a remote URL — into dompdf.
 * Remote fetching is switched off on the renderer for the same reason.
 */
class AssistantExportController extends Controller
{
    /** The panels share one session; whichever guard is signed in owns the request. */
    protected function viewer()
    {
        foreach (['admin', 'accounts', 'superadmin', 'web'] as $guard) {
            if ($user = Auth::guard($guard)->user()) {
                return $user;
            }
        }

        return Auth::user();
    }

    public function tablePdf(Request $request)
    {
        $user = $this->viewer();

        if (! $user) {
            abort(403);
        }

        $data = $request->validate([
            'title'              => ['nullable', 'string', 'max:120'],
            'tables'             => ['required', 'array', 'min:1', 'max:10'],
            'tables.*.headers'   => ['nullable', 'array', 'max:20'],
            'tables.*.headers.*' => ['nullable', 'string', 'max:200'],
            'tables.*.rows'      => ['nullable', 'array', 'max:500'],
            'tables.*.rows.*'    => ['array', 'max:20'],
            'tables.*.rows.*.*'  => ['nullable', 'string', 'max:500'],
        ]);

        $tables = array_values(array_filter(
            $data['tables'],
            fn ($t) => ! empty($t['headers']) || ! empty($t['rows']),
        ));

        if ($tables === []) {
            abort(422, 'Nothing to print.');
        }

        // Wide tables lie down: past six columns portrait squeezes every cell.
        $widest = 0;
        foreach ($tables as $table) {
            $widest = max($widest, count($table['headers'] ?? []));
            foreach ($table['rows'] ?? [] as $row) {
                $widest = max($widest, count($row));
            }
        }

        return Pdf::loadView('pdf.assistant-table', [
            'title'   => $data['title'] ?? 'LMS Assistant',
            'tables'  => $tables,
            'school'  => $user->organization->name ?? 'SuperLMS',
            'printed' => now(),
        ])
            ->setPaper('a4', $widest > 6 ? 'landscape' : 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false)
            ->download('lms-assistant-table.pdf');
    }
}
