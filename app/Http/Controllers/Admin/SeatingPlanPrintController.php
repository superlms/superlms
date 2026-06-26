<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Seating\InvigilatorAssignment;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Admin\Seating\SeatingRoom;
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
}
