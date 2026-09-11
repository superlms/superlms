<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Seating\InvigilatorAssignment;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Student\StudentDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class SeatingPlanPrintController extends Controller
{
    /**
     * Printable room-wise seating chart for a plan.
     * GET /{organization}/seating-plan/{id}/print
     */
    public function print($organization, $id)
    {
        $orgId = Auth::user()->organization_id;

        $plan = SeatingPlan::with('exam')
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        $assignments = SeatAssignment::with(['seat', 'student:id,name'])
            ->where('seating_plan_id', $plan->id)
            ->orderBy('room_id')
            ->get();

        $rooms = SeatingRoom::whereIn('id', $assignments->pluck('room_id')->unique())
            ->orderBy('room_name')
            ->get();

        $invigilators = InvigilatorAssignment::with('invigilator:id,name,phone')
            ->where('seating_plan_id', $plan->id)
            ->get();

        // Roll numbers for the chart — a seat is found by roll, not by name.
        $students = StudentDetail::where('organization_id', $orgId)
            ->whereIn('user_id', $assignments->pluck('student_id')->filter()->unique())
            ->get(['user_id', 'full_name', 'roll_no', 'admission_no'])
            ->keyBy('user_id');

        return view('admin.seating-plan-print', compact('plan', 'assignments', 'rooms', 'invigilators', 'students'));
    }

    /**
     * Per-room seating chart as a downloadable PDF — every seat shows who sits
     * there with class-section and admission number.
     * GET /{organization}/seating-plan/{id}/room/{roomId}/pdf
     */
    public function roomPdf($organization, $id, $roomId)
    {
        $orgId = Auth::user()->organization_id;

        $plan = SeatingPlan::with('exam')->where('organization_id', $orgId)->findOrFail($id);
        $room = SeatingRoom::where('organization_id', $orgId)->findOrFail($roomId);

        // Seat order, occupied desks only — one row per candidate.
        $assignments = SeatAssignment::with('seat')
            ->where('seating_plan_id', $plan->id)
            ->where('room_id', $room->id)
            ->whereNotNull('student_id')
            ->get()
            ->sortBy(fn ($a) => sprintf(
                '%04d|%04d|%03d',
                (int) ($a->seat?->row_no ?? 0),
                (int) ($a->seat?->col_no ?? 0),
                (int) ($a->seat_position ?? 1),
            ))->values();

        // user_id → StudentDetail (admission no + class/section)
        $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->whereIn('user_id', $assignments->pluck('student_id')->filter()->unique())
            ->get()->keyBy('user_id');

        $pdf = Pdf::loadView('pdf.admin.seating-room', compact('plan', 'room', 'assignments', 'students'))
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 130)
            ->setOption('defaultFont', 'DejaVu Sans');

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $room->room_name);
        return $pdf->download("seating_{$safe}_plan{$plan->id}.pdf");
    }
}
