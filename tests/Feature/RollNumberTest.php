<?php

namespace Tests\Feature;

use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\StudentNumbers;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A roll number is the class's whole code + a 3-digit serial from 001
 * (code "05" → 05001). The rolls minted the old way (the code's last digit +
 * a 2-digit serial: 502, 5118) are numbered again by the 2026-10-24
 * migration; a changed class code changes every roll of the class, and a
 * student moved to another class takes a roll of it. Admit cards follow.
 */
class RollNumberTest extends TestCase
{
    private int $org = 9;
    private int $twelve;
    private int $ten;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable(); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('roll_no')->nullable(); $t->timestamps();
        });
        Schema::create('admit_cards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('exam_id')->nullable();
            $t->string('roll_number')->nullable(); $t->timestamps();
        });

        $this->twelve = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'CLASS 12', 'code' => '05']);
        $this->ten    = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'CLASS 10', 'code' => '03']);
        foreach ([$this->twelve, $this->ten] as $class) {
            DB::table('sections')->insert(['organization_id' => $this->org, 'standard_id' => $class, 'name' => 'SECTION A']);
        }
    }

    private function student(int $class, ?string $roll, string $name): int
    {
        return DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $class,
            'section_id' => DB::table('sections')->where('standard_id', $class)->value('id'), 'full_name' => $name, 'roll_no' => $roll]);
    }

    private function roll(int $id): ?string
    {
        return DB::table('student_details')->where('id', $id)->value('roll_no');
    }

    private function card(int $student, ?string $roll): int
    {
        return DB::table('admit_cards')->insertGetId(['student_detail_id' => $student, 'exam_id' => 22, 'roll_number' => $roll]);
    }

    private function cardRoll(int $card): ?string
    {
        return DB::table('admit_cards')->where('id', $card)->value('roll_number');
    }

    public function test_a_roll_is_the_class_code_and_a_serial_from_001(): void
    {
        $this->assertSame('05001', StudentNumbers::rollNumber($this->twelve));

        $this->student($this->twelve, '05001', 'Asha');
        $this->student($this->twelve, '05002', 'Bilal');
        $this->student($this->twelve, '5118', 'Old roll');   // the old way: not counted
        $this->assertSame('05003', StudentNumbers::rollNumber($this->twelve));

        // Class 10 counts on its own.
        $this->assertSame('03001', StudentNumbers::rollNumber($this->ten));

        // Past 999 the serial just grows.
        $this->student($this->twelve, '05999', 'Chand');
        $this->assertSame('051000', StudentNumbers::rollNumber($this->twelve));
    }

    public function test_the_migration_numbers_every_class_again_in_the_order_its_rolls_stood(): void
    {
        $c = $this->student($this->twelve, '5118', 'Gaurav');
        $a = $this->student($this->twelve, '502', 'Janvi');
        $d = $this->student($this->twelve, '5125', 'Jeenal');
        $b = $this->student($this->twelve, '511', 'Jiya');
        $e = $this->student($this->twelve, null, 'No roll yet');
        $y = $this->student($this->ten, '318', 'Arush');
        $x = $this->student($this->ten, '302', 'Ashi');
        $card  = $this->card($c, '5118');
        $typed = $this->card($a, 'R-1');   // a roll typed on the card by hand stays

        (require base_path('database/migrations/2026_10_24_120000_roll_numbers_from_the_whole_class_code.php'))->up();

        $this->assertSame(['05001', '05002', '05003', '05004', '05005'], array_map(fn ($id) => $this->roll($id), [$a, $b, $c, $d, $e]));
        $this->assertSame(['03001', '03002'], [$this->roll($x), $this->roll($y)]);
        $this->assertSame('05003', $this->cardRoll($card));
        $this->assertSame('R-1', $this->cardRoll($typed));

        // Run again, nothing moves.
        (require base_path('database/migrations/2026_10_24_120000_roll_numbers_from_the_whole_class_code.php'))->up();
        $this->assertSame('05003', $this->roll($c));
    }

    public function test_a_changed_class_code_changes_every_roll_of_the_class(): void
    {
        $a = $this->student($this->twelve, '05001', 'Asha');
        $b = $this->student($this->twelve, '05007', 'Bilal');
        $n = $this->student($this->twelve, null, 'No roll yet');
        $other = $this->student($this->ten, '03001', 'Class 10');
        $card  = $this->card($b, '05007');

        Standard::find($this->twelve)->update(['code' => '12']);

        $this->assertSame(['12001', '12007', '12008'], [$this->roll($a), $this->roll($b), $this->roll($n)]);
        $this->assertSame('12007', $this->cardRoll($card));
        $this->assertSame('03001', $this->roll($other));
        $this->assertSame('12009', StudentNumbers::rollNumber($this->twelve));

        // A save that leaves the code as it was changes nothing.
        Standard::find($this->twelve)->update(['name' => 'Class 12']);
        $this->assertSame('12007', $this->roll($b));
    }

    public function test_a_student_moved_to_another_class_takes_a_roll_of_it(): void
    {
        $this->student($this->ten, '03001', 'Arush');
        $moved = $this->student($this->twelve, '05004', 'Gaurav');
        $card  = $this->card($moved, '05004');

        StudentDetail::find($moved)->update(['standard_id' => $this->ten, 'full_name' => 'Gaurav', 'roll_no' => '05004']);

        $this->assertSame('03002', $this->roll($moved));
        $this->assertSame('03002', $this->cardRoll($card));

        // An edit that keeps the class keeps the roll.
        StudentDetail::find($moved)->update(['full_name' => 'Gaurav Kumar']);
        $this->assertSame('03002', $this->roll($moved));
    }
}
