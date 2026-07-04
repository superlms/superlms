<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Student\AdmitCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdmitCardController extends Controller
{
    public function view(Request $request, $organization, $id)
    {
        $admitCard = $this->getAdmitCard($id);
        $admitCard->seating_label = $this->resolveSeating($admitCard);
        return view('admin.admit-card-pdf', [
            'admitCard'    => $admitCard,
            'organization' => $admitCard->organization,
        ]);
    }

    public function download(Request $request, $organization, $id)
    {
        $admitCard = $this->getAdmitCard($id);
        $admitCard->seating_label = $this->resolveSeating($admitCard);

        $pdf = Pdf::loadView('admin.admit-card-pdf', [
            'admitCard'    => $admitCard,
            'organization' => $admitCard->organization,
            'isPdf'        => true,
        ])->setPaper('a4', 'portrait')->setOption('isRemoteEnabled', true);

        $name = str_replace(' ', '_', $admitCard->student_name ?? 'admit_card');

        return $pdf->download("admit_card_{$name}.pdf");
    }

    public function printAll(Request $request, $organization)
    {
        $orgId = Auth::user()->organization_id;

        // A specific set of cards (from the print-selection modal), or filters.
        $ids = array_filter(explode(',', (string) $request->query('ids')));

        $admitCards = AdmitCard::with(['studentDetail.standard', 'studentDetail.section', 'organization'])
            ->where('organization_id', $orgId)
            ->when($ids, fn($q) => $q->whereIn('id', $ids))
            ->when(!$ids && $request->exam_id, fn($q) => $q->where('exam_id', $request->exam_id))
            ->when(!$ids && $request->standard_id, fn($q) => $q->where('standard_id', $request->standard_id))
            ->when(!$ids && $request->section_id, fn($q) => $q->where('section_id', $request->section_id))
            ->orderBy('roll_number')
            ->get();

        $admitCards->each(fn($card) => $card->seating_label = $this->resolveSeating($card));

        $organization = Auth::user()->organization;

        return view('admin.admit-card-print-all', compact('admitCards', 'organization'));
    }

    /**
     * Delete an admit card from the print/view page, then return to the listing.
     * POST /{organization}/admit-card/{id}/delete
     */
    public function destroy(Request $request, $organization, $id)
    {
        AdmitCard::where('organization_id', Auth::user()->organization_id)
            ->where('id', $id)
            ->delete();

        return redirect()
            ->route('admin.admit-card', ['organization' => $organization])
            ->with('success', 'Admit card deleted.');
    }

    private function getAdmitCard($id)
    {
        return AdmitCard::with([
            'studentDetail',
            'studentDetail.standard',
            'studentDetail.section',
            'organization',
        ])
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);
    }

    /**
     * Resolve "R(room)/ S(seat)" for the card's exam+student from the seating plan.
     * Falls back to the card's own room/seat when no assignment exists.
     */
    private function resolveSeating(AdmitCard $admitCard): ?string
    {
        $planIds = SeatingPlan::where('organization_id', $admitCard->organization_id)
            ->where('exam_id', $admitCard->exam_id)
            ->pluck('id');

        if ($planIds->isNotEmpty()) {
            $studentUserId = $admitCard->studentDetail?->user_id;

            $assignment = SeatAssignment::with(['room', 'seat'])
                ->whereIn('seating_plan_id', $planIds)
                ->where(function ($q) use ($admitCard, $studentUserId) {
                    $q->where('student_id', $admitCard->student_detail_id);
                    if ($studentUserId) {
                        $q->orWhere('student_id', $studentUserId);
                    }
                })
                ->first();

            if ($assignment) {
                $room = $assignment->room?->room_name;
                $seat = $assignment->seat?->seat_number;
                if ($room && $seat) return 'R(' . $room . ')/ S(' . $seat . ')';
                if ($seat) return 'S(' . $seat . ')';
                if ($room) return 'R(' . $room . ')';
            }
        }

        // Fallback to manually set room/seat on the card
        if ($admitCard->room_number && $admitCard->seat_number) {
            return 'R(' . $admitCard->room_number . ')/ S(' . $admitCard->seat_number . ')';
        }
        if ($admitCard->seat_number) return 'S(' . $admitCard->seat_number . ')';
        if ($admitCard->room_number) return 'R(' . $admitCard->room_number . ')';

        return null;
    }
}
