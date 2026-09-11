<?php

namespace App\Services\Seating;

use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Support\SeatLabel;

/**
 * Where a student sits, session by session.
 *
 * A seating plan is generated per exam session (date + shift), so a student
 * gets one room/seat per paper rather than one for the whole exam. This looks
 * those up in bulk — two queries for an entire class's admit cards — and hands
 * back a map keyed by "Y-m-d|shift" that the admit card lines up against the
 * datesheet rows it already carries.
 */
class SeatLocator
{
    /**
     * @param  array<int|string>  $studentIds  seat_assignments.student_id values to look for.
     *                            Plans store users.id; admit cards also pass the
     *                            student_detail id so older rows still resolve.
     * @return array<int, array<string, array{room:?string,seat:?string,date:string,shift:int,plan_id:int}>>
     *         student id → "Y-m-d|shift" → seat
     */
    public function forExam(int $orgId, int $examId, array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if (empty($studentIds)) {
            return [];
        }

        $plans = SeatingPlan::where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->get(['id', 'exam_date', 'session']);

        if ($plans->isEmpty()) {
            return [];
        }

        $assignments = SeatAssignment::with(['room:id,room_name', 'seat:id,seat_number,row_no,col_no'])
            ->whereIn('seating_plan_id', $plans->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->get();

        $map = [];
        foreach ($assignments as $a) {
            $plan = $plans->firstWhere('id', $a->seating_plan_id);
            if (! $plan) {
                continue;
            }
            $date  = $plan->exam_date?->toDateString() ?? '';
            $shift = static::shiftOf($plan->session);

            $map[(int) $a->student_id][$date . '|' . $shift] = [
                'room'    => $a->room?->room_name,
                'seat'    => $a->seat?->seat_number,
                // What the card actually prints — "1- A1 (1)". Built from the
                // row and column rather than the stored seat number, so rooms
                // saved before seats were lettered this way still read right.
                'label'   => SeatLabel::full(
                    $a->room?->room_name,
                    $a->seat?->row_no,
                    $a->seat?->col_no,
                    $a->seat_position,
                ),
                'date'    => $date,
                'shift'   => $shift,
                'plan_id' => (int) $a->seating_plan_id,
            ];
        }

        // Chronological, so the first entry is the first paper.
        foreach ($map as $studentId => $sessions) {
            ksort($sessions);
            $map[$studentId] = $sessions;
        }

        return $map;
    }

    /**
     * The seat for one paper: the exact date+shift, else any seat that day.
     *
     * @param  array<string, array<string,mixed>>  $sessions
     */
    public static function seatFor(array $sessions, ?string $date, $shift = 1): ?array
    {
        if (! $date) {
            return null;
        }

        $key = $date . '|' . (int) ($shift ?: 1);
        if (isset($sessions[$key])) {
            return $sessions[$key];
        }

        foreach ($sessions as $row) {
            if (($row['date'] ?? null) === $date) {
                return $row;
            }
        }

        return null;
    }

    /** "R(Hall A)/ S(B3)" — the compact label the admit card prints. */
    public static function label(?string $room, ?string $seat): ?string
    {
        if ($room && $seat) return 'R(' . $room . ')/ S(' . $seat . ')';
        if ($seat)          return 'S(' . $seat . ')';
        if ($room)          return 'R(' . $room . ')';
        return null;
    }

    /** "Shift 2" → 2. */
    public static function shiftOf(?string $session): int
    {
        if (! $session) {
            return 1;
        }

        return (int) (preg_replace('/\D+/', '', $session) ?: 1);
    }
}
