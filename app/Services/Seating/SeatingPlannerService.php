<?php

namespace App\Services\Seating;

use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Admin\Seating\SeatingInvigilator;
use Illuminate\Support\Collection;

/**
 * SeatingPlannerService
 *
 * Hybrid seating algorithm:
 *   1. Group students by class label
 *   2. Distribute classes across rooms by proportional share (so a room
 *      contains a mix of classes rather than one whole class together)
 *   3. Within each room, fill seats row-wise using class round-robin so
 *      neighbours are different classes by default
 *   4. Post-pass: detect adjacency conflicts (L/R/F/B) and resolve by
 *      swapping with a compatible seat in the SAME room
 *   5. Whatever conflict remains is flagged on the assignment row
 */
class SeatingPlannerService
{
    /**
     * Plan rooms × students.
     *
     * @param array $students   [{ id, name, class_label }]  (class_label e.g. "10-A")
     * @param Collection<SeatingRoom> $rooms  with seats relation eager-loaded
     * @return array            ['assignments' => [...], 'totals' => [...]]
     */
    public function plan(array $students, Collection $rooms): array
    {
        if (empty($students) || $rooms->isEmpty()) {
            return [
                'assignments' => [],
                'totals'      => ['students' => count($students), 'seats' => 0, 'conflicts' => 0, 'unseated' => count($students)],
            ];
        }

        // 1. Group students by class label
        $byClass = collect($students)->groupBy('class_label');

        // 2. Compute per-room quota for each class proportional to room capacity / total capacity
        $totalCapacity = $rooms->sum('capacity');
        $totalStudents = count($students);
        if ($totalStudents > $totalCapacity) {
            $totalStudents = $totalCapacity; // we can only seat capacity many
        }

        // queues per class — pop students from the front
        $classQueues = $byClass->map(fn($g) => collect($g)->values())->toArray();
        $classNames  = array_keys($classQueues);

        // Per-room quota of each class
        $roomQuotas = [];
        foreach ($rooms as $room) {
            $share = $room->capacity / max($totalCapacity, 1);
            $roomQuotas[$room->id] = [];
            foreach ($classNames as $cls) {
                $roomQuotas[$room->id][$cls] = (int) floor(count($classQueues[$cls]) * $share);
            }
        }

        // Distribute the remainders (rounding loss): assign extras to the largest rooms
        foreach ($classNames as $cls) {
            $assigned = array_sum(array_column($roomQuotas, $cls));
            $remaining = count($classQueues[$cls]) - $assigned;
            if ($remaining <= 0) continue;
            $sortedRooms = $rooms->sortByDesc('capacity')->values();
            $i = 0;
            while ($remaining > 0 && $i < $sortedRooms->count() * 10) {
                $r = $sortedRooms[$i % $sortedRooms->count()];
                $roomQuotas[$r->id][$cls]++;
                $remaining--;
                $i++;
            }
        }

        // 3. Fill each room row-wise with class round-robin
        $assignments = [];
        $conflictCount = 0;
        $unseated = $totalStudents;

        foreach ($rooms as $room) {
            $seats = $room->seats->sortBy([['row_no', 'asc'], ['col_no', 'asc']])->values();
            $rows = (int) $room->rows;
            $cols = (int) $room->columns;

            // Build round-robin sequence of class labels for this room
            $sequence = $this->buildRoundRobin($roomQuotas[$room->id]);

            // 2D grid: $grid[$row][$col] = ['student' => ..., 'class' => ...]
            $grid = array_fill(1, $rows, array_fill(1, $cols, null));

            // Fill seats
            $seqIdx = 0;
            foreach ($seats as $seat) {
                if ($seqIdx >= count($sequence)) break; // no more students
                $cls = $sequence[$seqIdx++];
                if (empty($classQueues[$cls])) continue;
                $student = array_shift($classQueues[$cls]);
                $grid[$seat->row_no][$seat->col_no] = [
                    'seat_id'     => $seat->id,
                    'student_id'  => $student['id'],
                    'class_label' => $cls,
                ];
                $unseated--;
            }

            // 4. Resolve adjacency conflicts by swap
            $this->resolveConflicts($grid, $rows, $cols);

            // 5. Final conflict detection (record what remains)
            foreach ($grid as $r => $rowCells) {
                foreach ($rowCells as $c => $cell) {
                    $hasConflict = $cell && $this->hasAdjacencyConflict($grid, $r, $c, $rows, $cols);
                    if ($hasConflict) $conflictCount++;
                    $assignments[] = [
                        'room_id'      => $room->id,
                        'seat_id'      => $this->seatIdAt($seats, $r, $c),
                        'student_id'   => $cell['student_id'] ?? null,
                        'class_label'  => $cell['class_label'] ?? null,
                        'has_conflict' => $hasConflict,
                    ];
                }
            }
        }

        return [
            'assignments' => $assignments,
            'totals'      => [
                'students'  => $totalStudents,
                'seats'     => $totalCapacity,
                'conflicts' => $conflictCount,
                'unseated'  => max(0, $unseated),
            ],
        ];
    }

    /**
     * Build a round-robin sequence from per-class quotas:
     *   {10-A: 3, 10-B: 3, 9-A: 2} → [10-A, 10-B, 9-A, 10-A, 10-B, 9-A, 10-A, 10-B]
     */
    private function buildRoundRobin(array $quotas): array
    {
        $sequence = [];
        $remaining = $quotas;
        while (array_sum($remaining) > 0) {
            // sort by remaining desc so the most-populous class is placed first this round
            arsort($remaining);
            foreach ($remaining as $cls => $n) {
                if ($n <= 0) continue;
                $sequence[] = $cls;
                $remaining[$cls]--;
            }
        }
        return $sequence;
    }

    /**
     * Try to swap conflicting seats with non-conflicting ones in the same room.
     */
    private function resolveConflicts(array &$grid, int $rows, int $cols): void
    {
        // Limit total swap attempts to avoid pathological loops
        $maxPasses = 3;
        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $anySwap = false;
            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 1; $c <= $cols; $c++) {
                    $cell = $grid[$r][$c] ?? null;
                    if (!$cell) continue;
                    if (!$this->hasAdjacencyConflict($grid, $r, $c, $rows, $cols)) continue;

                    // try swapping with any other cell in this room that
                    // (a) is not the same class as me and
                    // (b) does not create a new conflict either way
                    for ($r2 = 1; $r2 <= $rows; $r2++) {
                        for ($c2 = 1; $c2 <= $cols; $c2++) {
                            if ($r === $r2 && $c === $c2) continue;
                            $other = $grid[$r2][$c2] ?? null;
                            if (!$other) continue;
                            if ($other['class_label'] === $cell['class_label']) continue;

                            // try swap
                            $grid[$r][$c]   = $other;
                            $grid[$r2][$c2] = $cell;

                            $iConflict = $this->hasAdjacencyConflict($grid, $r, $c, $rows, $cols);
                            $jConflict = $this->hasAdjacencyConflict($grid, $r2, $c2, $rows, $cols);

                            if (!$iConflict && !$jConflict) {
                                $anySwap = true;
                                break 2;
                            }
                            // revert
                            $grid[$r][$c]   = $cell;
                            $grid[$r2][$c2] = $other;
                        }
                    }
                }
            }
            if (!$anySwap) break;
        }
    }

    private function hasAdjacencyConflict(array $grid, int $r, int $c, int $rows, int $cols): bool
    {
        $me = $grid[$r][$c] ?? null;
        if (!$me) return false;
        $neighbours = [
            [$r, $c - 1], // left
            [$r, $c + 1], // right
            [$r - 1, $c], // front
            [$r + 1, $c], // back
        ];
        foreach ($neighbours as [$rr, $cc]) {
            if ($rr < 1 || $rr > $rows || $cc < 1 || $cc > $cols) continue;
            $n = $grid[$rr][$cc] ?? null;
            if (!$n) continue;
            if ($n['class_label'] === $me['class_label']) return true;
        }
        return false;
    }

    private function seatIdAt(Collection $seats, int $r, int $c)
    {
        $hit = $seats->first(fn($s) => $s->row_no == $r && $s->col_no == $c);
        return $hit?->id;
    }

    /**
     * Assign invigilators to rooms for a given exam date.
     *
     * Rules:
     *   - At least 1 invigilator per room (2 if capacity >= 40)
     *   - Only invigilators whose available_dates contains $examDate
     *   - Respect each invigilator's max_rooms across the whole plan
     *   - Distribute load evenly — pick least-loaded available invigilator first
     *
     * @param Collection<SeatingRoom> $rooms
     * @param Collection<SeatingInvigilator> $candidates
     * @param string $examDate Y-m-d
     * @return array [room_id => [invigilator_id, ...]]
     */
    public function assignInvigilators(Collection $rooms, Collection $candidates, string $examDate): array
    {
        $eligible = $candidates->filter(fn($i) => $i->is_active && $i->isAvailableOn($examDate))->values();
        $load = []; // invigilator_id => count
        foreach ($eligible as $i) $load[$i->id] = 0;

        $result = [];
        foreach ($rooms->sortByDesc('capacity') as $room) {
            $needed = $room->capacity >= 40 ? 2 : 1;
            $result[$room->id] = [];
            for ($k = 0; $k < $needed; $k++) {
                // sort eligible by load asc, then pick first one not at max and not already in this room
                $sorted = $eligible->sortBy(fn($i) => $load[$i->id])->values();
                $picked = null;
                foreach ($sorted as $i) {
                    if (in_array($i->id, $result[$room->id], true)) continue;
                    if ($load[$i->id] >= $i->max_rooms) continue;
                    $picked = $i;
                    break;
                }
                if (!$picked) break;
                $result[$room->id][] = $picked->id;
                $load[$picked->id]++;
            }
        }

        return $result;
    }
}
