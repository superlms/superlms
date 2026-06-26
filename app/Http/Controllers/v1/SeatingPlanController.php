<?php

namespace App\Http\Controllers\v1;

use App\Models\Student\AdmitCard;
use App\Models\Student\StudentDetail;
use Illuminate\Http\Request;

class SeatingPlanController extends ApiController
{
    /**
     * GET /api/v1/seating-plan
     *
     * Returns the seating plan for the authenticated student.
     * Filters: exam_id (optional — returns latest active admit card if omitted)
     *
     * Only students (role=user) can access their own seating plan.
     */
    public function mySeating(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $query = AdmitCard::with(['exam:id,exam_name,start_date,end_date', 'standard:id,name', 'section:id,name'])
            ->where('student_detail_id', $student->id)
            ->where('organization_id', $user->organization_id)
            ->where('status', 'active');

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        $admitCard = $query->latest()->first();

        if (!$admitCard) {
            return $this->error('No active admit card / seating plan found.', 404);
        }

        return $this->success($this->formatSeating($admitCard), 'Seating plan fetched successfully.');
    }

    /**
     * GET /api/v1/seating-plan/all
     *
     * Returns all seating cards for the student across exams.
     */
    public function all(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $cards = AdmitCard::with(['exam:id,exam_name,start_date,end_date', 'standard:id,name', 'section:id,name'])
            ->where('student_detail_id', $student->id)
            ->where('organization_id', $user->organization_id)
            ->latest()
            ->paginate((int) $request->get('per_page', 10));

        $items = $cards->getCollection()->map(fn($c) => $this->formatSeating($c));

        return $this->paginated($items, $this->paginationMeta($cards), 'Seating plans fetched successfully.');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function formatSeating(AdmitCard $c): array
    {
        return [
            'id'                  => $c->id,
            'admit_card_number'   => $c->admit_card_number,
            'roll_number'         => $c->roll_number,
            'exam_roll_number'    => $c->exam_roll_number,
            'seat_number'         => $c->seat_number,
            'room_number'         => $c->room_number,
            'exam_center'         => $c->exam_center,
            'exam_center_address' => $c->exam_center_address,
            'reporting_time'      => $c->reporting_time,
            'instructions'        => $c->instructions,
            'allowed_items'       => $c->allowed_items,
            'prohibited_items'    => $c->prohibited_items,
            'issue_date'          => $c->issue_date?->format('Y-m-d'),
            'status'              => $c->status,
            'qr_code'             => $c->qr_code,
            'student_photo'       => $c->student_photo,
            'exam'                => $c->exam ? [
                'id'         => $c->exam->id,
                'name'       => $c->exam->exam_name,
                'start_date' => $c->exam->start_date?->format('Y-m-d'),
                'end_date'   => $c->exam->end_date?->format('Y-m-d'),
            ] : null,
            'subjects'            => $c->subjects ?? [],
            'class'               => ($c->standard?->name ?? '') . ($c->section ? ' - ' . $c->section->name : ''),
        ];
    }
}
