<?php

namespace App\Services\Seating;

use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Admin\Seating\SeatingInvigilator;
use Illuminate\Support\Collection;

/**
 * SeatingPlannerService
 *
 * Hybrid seating algorithm:
 *   1. Group students by class label into one continuous round-robin
 *      sequence — the whole exam's seating order, e.g. 10-A, 10-B, 9-A,
 *      10-A, 10-B, 9-A, … — so any two consecutive candidates are (so far
 *      as the mix allows) from different classes.
 *   2. Walk the rooms in order and fill each one from the front of that
 *      same sequence. A room that runs out of desks simply hands the rest
 *      of the sequence to the next room — nobody's seat depends on a
 *      quota computed ahead of time, so nobody can be shorted by one.
 *   3. Within a room, seats fill in boustrophedon order (left to right,
 *      then right to left) so the round-robin alternates classes down the
 *      room as well as across it, and a desk that seats more than one
 *      candidate gets consecutive — and therefore different — classes.
 *   4. Post-pass: detect adjacency conflicts (L/R/F/B) and resolve by
 *      swapping with a compatible seat in the SAME room.
 *   5. Whatever conflict remains is flagged on the assignment row.
 */
class SeatingPlannerService
{
    /**
     * Plan rooms × students.
     *
     * @param array $students   [{ id, name, class_label }]  (class_label e.g. "10-A")
     * @param Collection<SeatingRoom> $rooms  with seats relation eager-loaded, in the
     *                                        order a room should fill before the next
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

        // 1. Group students by class label into queues, and lay out one
        //    round-robin sequence across every class for the whole exam —
        //    not a slice per room, so a room's own capacity never has to be
        //    guessed ahead of the actual seating pass.
        $classQueues = collect($students)->groupBy('class_label')
            ->map(fn ($g) => collect($g)->values())->toArray();
        $sequence = $this->buildRoundRobin(array_map('count', $classQueues));

        $totalCapacity = $rooms->sum('capacity');
        $totalStudents = min(count($students), $totalCapacity);

        // 2-3. Fill rooms in order from the front of the shared sequence —
        //    a room that fills up hands the rest straight to the next one.
        $assignments = [];
        $conflictCount = 0;
        $seqIdx = 0;
        $seqTotal = count($sequence);

        foreach ($rooms as $room) {
            if ($seqIdx >= $seqTotal) break; // everyone is already seated

            $seats = $room->seats->sortBy([['row_no', 'asc'], ['col_no', 'asc']])->values();
            $rows = (int) $room->rows;
            $cols = (int) $room->columns;
            $cap  = max(1, (int) ($room->seat_capacity ?? 1)); // candidates per desk

            // 3D grid: $grid[$row][$col][$place]. A desk that seats two has two
            // places; a desk that seats one behaves exactly as it always did.
            $grid = array_fill(1, $rows, array_fill(1, $cols, array_fill(1, $cap, null)));

            // A room with room to spare seats one candidate to a desk; once
            // what's left of the sequence outgrows the room's own desks it
            // uses every place at them instead.
            $remaining = $seqTotal - $seqIdx;
            $spread = $cap > 1 && $remaining <= $seats->count();
            $places = $this->placeOrder($seats, $rows, $cols, $spread ? 1 : $cap);

            // The places run in boustrophedon order — left to right along one
            // row, right to left along the next — so the round-robin alternates
            // classes down the room as well as across it. Filling every row left
            // to right instead would sit a class directly behind itself whenever
            // the row holds an even number of places.
            foreach ($places as [$seat, $pos]) {
                if ($seqIdx >= $seqTotal) break; // no more students anywhere
                $cls = $sequence[$seqIdx];
                if (empty($classQueues[$cls])) { $seqIdx++; continue; }
                $student = array_shift($classQueues[$cls]);
                $grid[$seat->row_no][$seat->col_no][$pos] = [
                    'seat_id'     => $seat->id,
                    'student_id'  => $student['id'],
                    'class_label' => $cls,
                ];
                $seqIdx++;
            }

            // 4. Resolve adjacency conflicts by swap
            $this->resolveConflicts($grid, $rows, $cols, $cap);

            // 5. Final conflict detection (record what remains)
            foreach ($grid as $r => $rowCells) {
                foreach ($rowCells as $c => $places) {
                    for ($pos = 1; $pos <= $cap; $pos++) {
                        $cell = $places[$pos] ?? null;
                        $hasConflict = $cell && $this->hasAdjacencyConflict($grid, $r, $c, $pos, $rows, $cols, $cap);
                        if ($hasConflict) $conflictCount++;
                        $assignments[] = [
                            'room_id'       => $room->id,
                            'seat_id'       => $this->seatIdAt($seats, $r, $c),
                            'seat_position' => $pos,
                            'student_id'    => $cell['student_id'] ?? null,
                            'class_label'   => $cell['class_label'] ?? null,
                            'has_conflict'  => $hasConflict,
                        ];
                    }
                }
            }
        }

        $unseated = array_sum(array_map('count', $classQueues));

        return [
            'assignments' => $assignments,
            'totals'      => [
                'students'  => $totalStudents,
                'seats'     => $totalCapacity,
                'conflicts' => $conflictCount,
                'unseated'  => $unseated,
            ],
        ];
    }

    /**
     * Every place in the room, in boustrophedon order: row 1 left to right, row
     * 2 right to left, and so on. Places at a desk run left to right, so a row
     * of c desks seating n each is one run of c × n places — which is what makes
     * the round-robin land as a chequerboard, and a chequerboard is what keeps
     * classmates apart in every direction.
     *
     * Pass $cap = 1 to walk one place per desk.
     *
     * @return array<int, array{0: object, 1: int}>  [seat, place]
     */
    private function placeOrder(Collection $seats, int $rows, int $cols, int $cap): array
    {
        $byPosition = [];
        foreach ($seats as $seat) {
            $byPosition[$seat->row_no][$seat->col_no] = $seat;
        }

        $order = [];
        $width = max(1, $cols) * max(1, $cap);

        for ($r = 1; $r <= $rows; $r++) {
            $line = range(1, $width);
            if ($r % 2 === 0) {
                $line = array_reverse($line);
            }
            foreach ($line as $i) {
                $c = intdiv($i - 1, $cap) + 1;
                $p = ($i - 1) % $cap + 1;
                if (isset($byPosition[$r][$c])) {
                    $order[] = [$byPosition[$r][$c], $p];
                }
            }
        }

        return $order;
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
     * Try to swap conflicting places with non-conflicting ones in the same room.
     * A place is a desk and a position at it, so two candidates sharing a desk
     * get moved apart the same way two neighbours do.
     */
    private function resolveConflicts(array &$grid, int $rows, int $cols, int $cap): void
    {
        // Limit total swap attempts to avoid pathological loops
        $maxPasses = 3;
        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $anySwap = false;
            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 1; $c <= $cols; $c++) {
                    for ($p = 1; $p <= $cap; $p++) {
                        $cell = $grid[$r][$c][$p] ?? null;
                        if (!$cell) continue;
                        if (!$this->hasAdjacencyConflict($grid, $r, $c, $p, $rows, $cols, $cap)) continue;

                        // try swapping with any other place in this room that
                        // (a) is not the same class as me and
                        // (b) does not create a new conflict either way
                        for ($r2 = 1; $r2 <= $rows; $r2++) {
                            for ($c2 = 1; $c2 <= $cols; $c2++) {
                                for ($p2 = 1; $p2 <= $cap; $p2++) {
                                    if ($r === $r2 && $c === $c2 && $p === $p2) continue;
                                    $other = $grid[$r2][$c2][$p2] ?? null;
                                    if (!$other) continue;
                                    if ($other['class_label'] === $cell['class_label']) continue;

                                    // try swap
                                    $grid[$r][$c][$p]    = $other;
                                    $grid[$r2][$c2][$p2] = $cell;

                                    $iConflict = $this->hasAdjacencyConflict($grid, $r, $c, $p, $rows, $cols, $cap);
                                    $jConflict = $this->hasAdjacencyConflict($grid, $r2, $c2, $p2, $rows, $cols, $cap);

                                    if (!$iConflict && !$jConflict) {
                                        $anySwap = true;
                                        break 3;
                                    }
                                    // revert
                                    $grid[$r][$c][$p]    = $cell;
                                    $grid[$r2][$c2][$p2] = $other;
                                }
                            }
                        }
                    }
                }
            }
            if (!$anySwap) break;
        }
    }

    /**
     * A candidate conflicts with anyone of their own class they can actually
     * reach: whoever shares their desk, the candidate on the other side of each
     * aisle, and the ones at the same place in the desks in front and behind.
     *
     * Places at a desk run left to right, so only the outermost place of a desk
     * is beside the next desk along — with one place per desk this is exactly
     * the left / right / front / back it has always been.
     */
    private function hasAdjacencyConflict(array $grid, int $r, int $c, int $p, int $rows, int $cols, int $cap): bool
    {
        $me = $grid[$r][$c][$p] ?? null;
        if (!$me) return false;

        $sameClass = function (?int $rr, ?int $cc, int $pp) use ($grid, $me, $rows, $cols, $cap): bool {
            if ($rr < 1 || $rr > $rows || $cc < 1 || $cc > $cols || $pp < 1 || $pp > $cap) return false;
            $n = $grid[$rr][$cc][$pp] ?? null;
            return $n !== null && $n['class_label'] === $me['class_label'];
        };

        // The other places at my own desk — the closest neighbours of all.
        for ($pp = 1; $pp <= $cap; $pp++) {
            if ($pp !== $p && $sameClass($r, $c, $pp)) return true;
        }

        // Across the aisle: only from the ends of the desk.
        if ($p === 1 && $sameClass($r, $c - 1, $cap)) return true;
        if ($p === $cap && $sameClass($r, $c + 1, 1)) return true;

        // In front and behind, at the same place along the desk.
        if ($sameClass($r - 1, $c, $p)) return true;
        if ($sameClass($r + 1, $c, $p)) return true;

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
