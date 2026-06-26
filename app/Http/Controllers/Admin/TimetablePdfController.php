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

        // One row per (subject, teacher, start, end). Days column shows the range
        // (first day → last day) of that teacher's coverage for that slot.
        $rows = $entries
            ->groupBy(fn($e) => $e->subject_id . '|' . $e->teacher_detail_id . '|' . $e->start_time . '|' . $e->end_time)
            ->map(function ($items) use ($daysShort) {
                $first    = $items->first();
                $dayNums  = $items->pluck('day_of_week')->map(fn($d) => (int) $d)->unique()->sort()->values()->all();
                $firstDay = $dayNums[0] ?? null;
                $lastDay  = $dayNums[count($dayNums) - 1] ?? null;

                $daysRange = match (true) {
                    $firstDay === null              => '—',
                    $firstDay === $lastDay          => $daysShort[$firstDay] ?? '',
                    default                          => ($daysShort[$firstDay] ?? '') . ' – ' . ($daysShort[$lastDay] ?? ''),
                };

                return [
                    'subject'      => $first->subject?->name ?? '—',
                    'teacher'      => $first->teacher?->user?->name ?? '—',
                    'start_time'   => $first->start_time,
                    'end_time'     => $first->end_time,
                    'days_range'   => $daysRange,
                    'sort_key'     => $first->start_time . '|' . ($firstDay ?? 99),
                ];
            })
            ->sortBy('sort_key')
            ->values()
            ->all();

        $pdf = Pdf::loadView('pdf.admin.timetable', [
            'organization' => $org,
            'schoolInfo'   => $schoolInfo,
            'standard'     => $standardModel,
            'section'      => $sectionModel,
            'rows'         => $rows,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'timetable_' . str_replace(' ', '_', $standardModel->name) . '_' . str_replace(' ', '_', $sectionModel->name) . '.pdf';
        return $pdf->download($filename);
    }
}
