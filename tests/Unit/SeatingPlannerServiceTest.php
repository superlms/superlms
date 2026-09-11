<?php

namespace Tests\Unit;

use App\Services\Seating\SeatingPlannerService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * The planner takes plain rooms and students and hands back places, so it can
 * be exercised without a database: the rooms here are stand-ins carrying the
 * same fields the model does.
 */
class SeatingPlannerServiceTest extends TestCase
{
    /** A room of rows × columns desks, each seating $perSeat candidates. */
    private function room(int $id, int $rows, int $cols, int $perSeat): object
    {
        $seats = collect();
        $seatId = $id * 1000;

        for ($r = 1; $r <= $rows; $r++) {
            for ($c = 1; $c <= $cols; $c++) {
                $seats->push((object) [
                    'id'          => ++$seatId,
                    'row_no'      => $r,
                    'col_no'      => $c,
                    'seat_number' => chr(64 + $r) . $c,
                ]);
            }
        }

        return (object) [
            'id'            => $id,
            'room_name'     => 'Hall ' . $id,
            'rows'          => $rows,
            'columns'       => $cols,
            'seat_capacity' => $perSeat,
            'capacity'      => $rows * $cols * $perSeat,
            'seats'         => $seats,
        ];
    }

    /** @param array<string,int> $counts class label => how many students */
    private function students(array $counts): array
    {
        $out = [];
        $i = 1;
        foreach ($counts as $label => $n) {
            for ($k = 0; $k < $n; $k++) {
                $out[] = ['id' => $i, 'name' => 'Student ' . $i, 'class_label' => $label];
                $i++;
            }
        }

        return $out;
    }

    private function plan(array $students, Collection $rooms): array
    {
        return (new SeatingPlannerService())->plan($students, $rooms);
    }

    private function seated(array $result): array
    {
        return array_values(array_filter($result['assignments'], fn ($a) => $a['student_id']));
    }

    public function test_a_desk_that_seats_two_takes_two_candidates(): void
    {
        $result = $this->plan(
            $this->students(['10-A' => 8, '10-B' => 8]),
            collect([$this->room(1, 4, 2, 2)])
        );

        $seated = $this->seated($result);

        $this->assertCount(16, $seated, 'a 4 × 2 room of two-seaters holds 16');
        $this->assertSame([1, 2], collect($seated)->pluck('seat_position')->unique()->sort()->values()->all());
    }

    public function test_every_place_is_written_once_and_no_student_twice(): void
    {
        $result = $this->plan(
            $this->students(['10-A' => 9, '10-B' => 9]),
            collect([$this->room(1, 3, 3, 2)])
        );

        $keys = array_map(
            fn ($a) => $a['room_id'] . ':' . $a['seat_id'] . ':' . $a['seat_position'],
            $result['assignments']
        );
        $this->assertSame(count($keys), count(array_unique($keys)), 'a place is assigned more than once');

        $ids = array_column($this->seated($result), 'student_id');
        $this->assertSame(count($ids), count(array_unique($ids)), 'a student is seated more than once');
    }

    public function test_a_room_with_space_seats_one_to_a_desk(): void
    {
        $result = $this->plan(
            $this->students(['10-A' => 4, '10-B' => 4]),
            collect([$this->room(1, 4, 2, 2)])   // 8 desks, 16 places
        );

        $desks = collect($this->seated($result))->pluck('seat_id')->unique();

        $this->assertCount(8, $desks, 'eight candidates in a half-full room should have a desk each');
    }

    public function test_two_classes_are_seated_without_a_single_conflict(): void
    {
        foreach ([[3, 4, 1], [4, 2, 2], [3, 3, 2], [4, 1, 2]] as [$rows, $cols, $perSeat]) {
            $places = $rows * $cols * $perSeat;
            $result = $this->plan(
                $this->students(['10-A' => $places / 2, '10-B' => $places / 2]),
                collect([$this->room(1, $rows, $cols, $perSeat)])
            );

            $this->assertSame(
                0,
                $result['totals']['conflicts'],
                "a full {$rows}×{$cols} room seating {$perSeat} per desk should have no classmate side by side"
            );
        }
    }

    public function test_classmates_never_share_a_desk_while_another_class_is_free(): void
    {
        $result = $this->plan(
            $this->students(['10-A' => 9, '10-B' => 9]),
            collect([$this->room(1, 3, 3, 2)])
        );

        $byDesk = [];
        foreach ($this->seated($result) as $a) {
            $byDesk[$a['seat_id']][] = $a['class_label'];
        }

        foreach ($byDesk as $seatId => $labels) {
            $this->assertSame(
                count($labels),
                count(array_unique($labels)),
                "desk {$seatId} seats two of the same class"
            );
        }
    }

    public function test_it_seats_what_it_can_and_reports_the_rest(): void
    {
        $result = $this->plan(
            $this->students(['10-A' => 20, '10-B' => 20]),
            collect([$this->room(1, 3, 3, 2)])   // 18 places
        );

        $this->assertCount(18, $this->seated($result));
        $this->assertSame(18, $result['totals']['students']);
        $this->assertSame(18, $result['totals']['seats']);
    }

    public function test_no_students_and_no_rooms_are_both_harmless(): void
    {
        $this->assertSame([], $this->plan([], collect([$this->room(1, 3, 3, 2)]))['assignments']);
        $this->assertSame([], $this->plan($this->students(['10-A' => 3]), collect())['assignments']);
    }
}
