<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student\AdmitCard;
use App\Services\Seating\SeatLocator;
use App\Support\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AdmitCardController extends Controller
{
    public function __construct(private SeatLocator $seats)
    {
    }

    public function view(Request $request, $organization, $id)
    {
        $admitCard = $this->getAdmitCard($id);
        $this->attachSeating(collect([$admitCard]));

        return view('admin.admit-card-pdf', [
            'admitCard'    => $admitCard,
            'organization' => $admitCard->organization,
        ]);
    }

    public function download(Request $request, $organization, $id)
    {
        $admitCard = $this->getAdmitCard($id);
        $this->attachSeating(collect([$admitCard]));

        $fontCache = PdfFonts::cacheDir();

        // Poppins is embedded as base64 @font-face; if that ever fails the card
        // still renders, just in dompdf's default face.
        $load = fn (string $fontCss) => Pdf::loadView('admin.admit-card-pdf', [
            'admitCard'    => $admitCard,
            'organization' => $admitCard->organization,
            'isPdf'        => true,
            'fontCss'      => $fontCss,
        ])->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontCache)
            ->setOption('fontCache', $fontCache)
            ->setOption('defaultFont', 'DejaVu Sans');

        try {
            $pdf = $load(PdfFonts::faceCss());
        } catch (\Throwable $e) {
            logger()->warning('Admit card font embedding failed: ' . $e->getMessage());
            $pdf = $load('');
        }

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
            // Natural roll order, so 2 comes before 10 on the sheet.
            ->orderByRaw('CAST(roll_number AS UNSIGNED), roll_number')
            ->get();

        $this->attachSeating($admitCards);

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
     * Hang the seating plan on every card: `seating_sessions` maps "date|shift"
     * to the room/seat for that paper, so each subject row on the card can print
     * its own seat. `seating_label` stays as the card-wide fallback.
     *
     * @param  Collection<int,AdmitCard>  $cards
     */
    private function attachSeating(Collection $cards): void
    {
        foreach ($cards->groupBy('exam_id') as $examId => $group) {
            $orgId = (int) $group->first()->organization_id;

            $studentIds = $group->flatMap(fn($c) => [
                $c->studentDetail?->user_id,
                $c->student_detail_id,
            ])->filter()->unique()->all();

            $map = $this->seats->forExam($orgId, (int) $examId, $studentIds);

            foreach ($group as $card) {
                $sessions = $map[(int) $card->studentDetail?->user_id]
                    ?? $map[(int) $card->student_detail_id]
                    ?? [];

                $card->seating_sessions = $sessions;

                $first = $sessions ? reset($sessions) : null;
                $card->seating_label = $first
                    ? SeatLocator::label($first['room'], $first['seat'])
                    : SeatLocator::label($card->room_number, $card->seat_number);
            }
        }
    }
}
