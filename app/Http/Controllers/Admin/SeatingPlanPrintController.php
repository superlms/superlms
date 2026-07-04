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

        return view('admin.seating-plan-print', compact('plan', 'assignments', 'rooms', 'invigilators'));
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

        $assignments = SeatAssignment::with('seat')
            ->where('seating_plan_id', $plan->id)
            ->where('room_id', $room->id)
            ->get();

        // user_id → StudentDetail (admission no + class/section)
        $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->whereIn('user_id', $assignments->pluck('student_id')->filter()->unique())
            ->get()->keyBy('user_id');

        $cells = [];
        foreach ($assignments as $a) {
            if ($a->seat) $cells[$a->seat->row_no][$a->seat->col_no] = $a;
        }

        $pdf = Pdf::loadView('pdf.admin.seating-room', compact('plan', 'room', 'cells', 'students'))
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 130)
            ->setOption('defaultFont', 'DejaVu Sans');

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $room->room_name);
        return $pdf->download("seating_{$safe}_plan{$plan->id}.pdf");
    }
}
