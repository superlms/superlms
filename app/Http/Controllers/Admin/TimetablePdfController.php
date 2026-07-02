<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SchoolInfo;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TimetablePdfController extends Controller
{
    /**
     * Download a class-section timetable as PDF.
     * Route: GET /admin/{organization}/timetable/{standard}/{section}/pdf
     */
    public function download(int $organization, int $standard, int $section): Response
    {
        $orgId = Auth::user()?->organization_id;
        abort_if(!$orgId || $orgId !== $organization, 403);

        $org           = Organization::find($orgId);
        $schoolInfo    = SchoolInfo::where('organization_id', $orgId)->first();
        $standardModel = Standard::where('organization_id', $orgId)->findOrFail($standard);
        $sectionModel  = Section::where('organization_id', $orgId)->findOrFail($section);

        $entries = TeacherTimeTable::with(['teacher.user:id,name', 'subject:id,name,code'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $standard)
            ->where('section_id',  $section)
            ->get();

        $daysShort = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

        // Weekly grid: rows are the distinct time slots (sorted by start time),
        // columns are the weekdays, and each cell holds the subject + teacher
        // scheduled for that slot on that day. This is the conventional
        // school-timetable layout.
        $slots = $entries
            ->map(fn($e) => ['start_time' => $e->start_time, 'end_time' => $e->end_time])
            ->unique(fn($s) => $s['start_time'] . '|' . $s['end_time'])
            ->sortBy('start_time')
            ->values()
            ->all();

        $grid = [];
        foreach ($entries as $e) {
            $key = $e->start_time . '|' . $e->end_time;
            $grid[$key][(int) $e->day_of_week] = [
                'subject' => $e->subject?->name ?? '—',
                'teacher' => $e->teacher?->user?->name ?? '—',
            ];
        }

        $pdf = Pdf::loadView('pdf.admin.timetable', [
            'organization' => $org,
            'schoolInfo'   => $schoolInfo,
            'standard'     => $standardModel,
            'section'      => $sectionModel,
            'days'         => $daysShort,
            'slots'        => $slots,
            'grid'         => $grid,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'timetable_' . str_replace(' ', '_', $standardModel->name) . '_' . str_replace(' ', '_', $sectionModel->name) . '.pdf';
        return $pdf->download($filename);
    }
}
