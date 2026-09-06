<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SchoolInfo;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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

    /**
     * Download a teacher's own weekly timetable as PDF.
     * Route: GET /admin/{organization}/timetable/teacher/{teacher}/pdf
     */
    public function downloadTeacher(int $organization, int $teacher): Response
    {
        $orgId = Auth::user()?->organization_id;
        abort_if(!$orgId || $orgId !== $organization, 403);

        $org        = Organization::find($orgId);
        $schoolInfo = SchoolInfo::where('organization_id', $orgId)->first();
        $teacherModel = \App\Models\Teacher\TeacherDetail::with('user:id,name')
            ->where('organization_id', $orgId)->findOrFail($teacher);

        $entries = TeacherTimeTable::with(['standard:id,name', 'section:id,name', 'subject:id,name,code'])
            ->where('organization_id', $orgId)
            ->where('teacher_detail_id', $teacher)
            ->get();

        $daysShort = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

        $slots = $entries
            ->map(fn($e) => ['start_time' => $e->start_time, 'end_time' => $e->end_time])
            ->unique(fn($s) => $s['start_time'] . '|' . $s['end_time'])
            ->sortBy('start_time')
            ->values()
            ->all();

        // Cell = class · section + subject (the teacher's own schedule).
        $grid = [];
        foreach ($entries as $e) {
            $key = $e->start_time . '|' . $e->end_time;
            $where = trim(($e->standard?->name ?? '') . ($e->section ? ' · ' . $e->section->name : ''));
            $grid[$key][(int) $e->day_of_week] = [
                'subject' => $e->subject?->name ?? '—',
                'teacher' => $where !== '' ? $where : '—',
            ];
        }

        $pdf = Pdf::loadView('pdf.admin.teacher-timetable', [
            'organization' => $org,
            'schoolInfo'   => $schoolInfo,
            'teacher'      => $teacherModel,
            'days'         => $daysShort,
            'slots'        => $slots,
            'grid'         => $grid,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $name = $teacherModel->user?->name ?? ('teacher_' . $teacher);
        return $pdf->download('timetable_' . str_replace(' ', '_', $name) . '.pdf');
    }

    /**
     * Printable list view of a timetable — the same rows the Class/Teacher view
     * shows on screen (S.No · Time · Subject · Teacher(s) · Days), rendered in
     * landscape with the school logo centred so it can be printed as-is.
     *
     * Route: GET /admin/{organization}/timetable/print?mode=&standard=&section=&teacher=&days=
     */
    public function printList(int $organization, Request $request): Response
    {
        $orgId = Auth::user()?->organization_id;
        abort_if(!$orgId || $orgId !== $organization, 403);

        $mode = $request->query('mode') === 'teacher' ? 'teacher' : 'class';
        $days = array_values(array_filter(
            array_map('intval', explode(',', (string) $request->query('days', ''))),
            fn ($d) => $d >= 1 && $d <= 7
        ));

        $query = TeacherTimeTable::with([
            'teacher.user:id,name',
            'standard:id,name',
            'section:id,name',
            'subject:id,name,code',
        ])
            ->where('organization_id', $orgId)
            ->when(!empty($days), fn ($q) => $q->whereIn('day_of_week', $days));

        $heading = null;

        if ($mode === 'teacher') {
            $teacherId = (int) $request->query('teacher');
            abort_if(!$teacherId, 404);
            $teacherModel = \App\Models\Teacher\TeacherDetail::with('user:id,name')
                ->where('organization_id', $orgId)->findOrFail($teacherId);
            $query->where('teacher_detail_id', $teacherId);
            $heading = $teacherModel->user?->name ?? 'Teacher';
        } else {
            $standardId = (int) $request->query('standard');
            $sectionId  = (int) $request->query('section');
            abort_if(!$standardId || !$sectionId, 404);
            $standardModel = Standard::where('organization_id', $orgId)->findOrFail($standardId);
            $sectionModel  = Section::where('organization_id', $orgId)->findOrFail($sectionId);
            $query->where('standard_id', $standardId)->where('section_id', $sectionId);
            $heading = $standardModel->name . ' · ' . $sectionModel->name;
        }

        // Group exactly the way the on-screen table does: one row per
        // (class·section, subject, time slot), with every teacher and every day
        // that slot runs on collapsed into that single row.
        $rows = $query->get()
            ->groupBy(fn ($e) => $e->standard_id . '|' . $e->section_id . '|' . $e->subject_id . '|' . $e->start_time . '|' . $e->end_time)
            ->map(function ($g) {
                $first = $g->first();

                return [
                    'standard_id'   => (int) $first->standard_id,
                    'section_id'    => (int) $first->section_id,
                    'class_section' => trim(($first->standard?->name ?? '—') . ($first->section ? ' · ' . $first->section->name : '')),
                    'start_time'    => $first->start_time,
                    'end_time'      => $first->end_time,
                    'subject'       => $first->subject?->name ?? '—',
                    'teachers'      => $g->map(fn ($e) => $e->teacher?->user?->name)
                        ->filter()->unique()->values()->all(),
                    'days'          => $g->pluck('day_of_week')->map(fn ($d) => (int) $d)
                        ->unique()->sort()->values()->all(),
                ];
            })
            ->sortBy([['standard_id', 'asc'], ['section_id', 'asc'], ['start_time', 'asc']])
            ->values();

        $pdf = Pdf::loadView('pdf.admin.timetable-list', [
            'organization' => Organization::find($orgId),
            'schoolInfo'   => SchoolInfo::where('organization_id', $orgId)->first(),
            'mode'         => $mode,
            'heading'      => $heading,
            'rows'         => $rows,
            'daysShort'    => [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'],
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', trim((string) $heading, ' ·'));

        // Streamed (not downloaded) so it opens in the browser's PDF viewer and
        // can be sent straight to the printer.
        return $pdf->stream('timetable_' . trim($slug, '_') . '.pdf');
    }
}
