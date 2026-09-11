<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The seating list for one exam session: who sits where, in roll order, on a
 * landscape page. The browser copy and the download are the same view — print
 * and PDF must not drift apart.
 */
class SeatingListController extends Controller
{
    /** GET /{organization}/seating-plan/{id}/list */
    public function print(Request $request, $organization, $id)
    {
        return view('admin.seating-list', $this->payload($request, $id));
    }

    /** GET /{organization}/seating-plan/{id}/list/pdf */
    public function pdf(Request $request, $organization, $id)
    {
        $data = $this->payload($request, $id);
        $fontCache = PdfFonts::cacheDir();

        $load = fn (string $fontCss) => Pdf::loadView('admin.seating-list', $data + ['isPdf' => true, 'fontCss' => $fontCss])
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontCache)
            ->setOption('fontCache', $fontCache)
            ->setOption('defaultFont', 'DejaVu Sans');

        try {
            $pdf = $load(PdfFonts::faceCss());
        } catch (\Throwable $e) {
            logger()->warning('Seating list font embedding failed: ' . $e->getMessage());
            $pdf = $load('');
        }

        $name = 'seating_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $data['heading']) . '.pdf';

        return $pdf->download($name);
    }

    /**
     * Everything both copies of the page need.
     *
     * Query: room, standard, section, subject — a room narrows the list to that
     * room, a class narrows it to that class, and the subject is carried
     * through because a session's paper depends on which class is reading it.
     */
    private function payload(Request $request, $id): array
    {
        $orgId = Auth::user()->organization_id;

        $plan = SeatingPlan::with('exam:id,exam_name,academic_year')
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        $roomId     = (int) $request->query('room');
        $standardId = (int) $request->query('standard');
        $sectionId  = (int) $request->query('section');

        $assignments = SeatAssignment::with(['seat:id,seat_number,row_no,col_no'])
            ->where('seating_plan_id', $plan->id)
            ->whereNotNull('student_id')
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->get();

        $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->whereIn('user_id', $assignments->pluck('student_id')->unique())
            ->get()
            ->keyBy('user_id');

        // A class was asked for → only that class's candidates are listed.
        if ($standardId) {
            $assignments = $assignments->filter(function ($a) use ($students, $standardId, $sectionId) {
                $s = $students[$a->student_id] ?? null;
                if (!$s || (int) $s->standard_id !== $standardId) return false;

                return !$sectionId || (int) $s->section_id === $sectionId;
            })->values();
        }

        $rooms = SeatingRoom::whereIn('id', $assignments->pluck('room_id')->unique())
            ->get()->keyBy('id');

        // Room by room, then down each room in seat order.
        $rows = $assignments->sortBy(fn ($a) => sprintf(
            '%s|%04d|%04d|%03d',
            $rooms[$a->room_id]->room_name ?? '',
            (int) ($a->seat?->row_no ?? 0),
            (int) ($a->seat?->col_no ?? 0),
            (int) ($a->seat_position ?? 1),
        ))->values()->map(function ($a) use ($students, $rooms) {
            $s    = $students[$a->student_id] ?? null;
            $room = $rooms[$a->room_id] ?? null;
            $cap  = max(1, (int) ($room->seat_capacity ?? 1));

            return [
                'name'      => $s->full_name ?? '—',
                'admission' => $s->admission_no ?: '—',
                'roll'      => $s->roll_no ?: '—',
                'class'     => $s ? (($s->standard->name ?? '') . ($s->section ? ' - ' . $s->section->name : '')) : ($a->class_label ?? '—'),
                'room'      => $room->room_name ?? '—',
                // A shared desk needs to say which place at it.
                'seat'      => ($a->seat->seat_number ?? '—') . ($cap > 1 ? ' · ' . $a->seat_position : ''),
            ];
        });

        $room      = $roomId ? ($rooms[$roomId] ?? SeatingRoom::where('organization_id', $orgId)->find($roomId)) : null;
        $standard  = $standardId ? Standard::where('organization_id', $orgId)->find($standardId) : null;
        $section   = $sectionId ? Section::find($sectionId) : null;
        $subject   = (string) $request->query('subject', '');

        $heading = collect([
            $plan->exam->exam_name ?? 'Examination',
            $subject ?: null,
            $room->room_name ?? null,
            $standard?->name,
        ])->filter()->implode(' ');

        return [
            'plan'         => $plan,
            'rows'         => $rows,
            'room'         => $room,
            'standard'     => $standard,
            'section'      => $section,
            'subject'      => $subject,
            'showRoom'     => $room === null,
            'heading'      => $heading,
            'organization' => Auth::user()->organization,
        ];
    }
}
