<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The midnight `id-cards:generate-missing`: a school that has generated a
 * batch still has everyone without a card given one (as before); every other
 * school has the students, teachers and staff added from 4 Oct 2026 given
 * theirs — not those added before, not a teacher's staff row — and its
 * setting is left off. A second run issues nothing more.
 */
class IdCardNightlyTest extends TestCase
{
    private int $newSchool = 6;

    private int $autoSchool = 7;

    protected function setUp(): void
    {
        parent::setUp();

        // The run at midnight that ends 9 October; cards run to 31 March 2027.
        $this->travelTo('2026-10-10 00:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('address')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('admission_no')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('employee_id')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('assign_teacher_standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('type');
            $t->unsignedBigInteger('teacher_detail_id')->nullable(); $t->string('designation')->nullable();
            $t->string('mobile')->nullable(); $t->timestamps();
        });
        foreach (['student_id_cards' => 'student_detail_id', 'teacher_id_cards' => 'teacher_detail_id', 'employee_id_cards' => 'admin_employee_id'] as $table => $holder) {
            Schema::create($table, function (Blueprint $t) use ($holder) {
                $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->default(0);
                $t->unsignedBigInteger($holder); $t->string('card_number'); $t->date('issue_date')->nullable();
                $t->date('expiry_date')->nullable(); $t->string('status')->default('active'); $t->text('qr_code')->nullable();
                $t->timestamps();
            });
        }
        Schema::create('id_card_generation_settings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('type');
            $t->boolean('auto_enabled')->default(false); $t->date('expiry_date')->nullable();
            $t->timestamp('last_generated_at')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert([
            ['id' => $this->newSchool, 'name' => 'Swatantrata Sainani Inter College'],
            ['id' => $this->autoSchool, 'name' => 'Delhi Public School'],
        ]);
        DB::table('id_card_generation_settings')->insert([
            'organization_id' => $this->autoSchool, 'type' => 'student', 'auto_enabled' => true, 'expiry_date' => '2027-03-31',
        ]);
    }

    private function student(int $org, string $name, string $added): int
    {
        return DB::table('student_details')->insertGetId([
            'organization_id' => $org, 'full_name' => $name, 'created_at' => $added, 'updated_at' => $added,
        ]);
    }

    private function teacher(int $org, string $added): int
    {
        return DB::table('teacher_details')->insertGetId(['organization_id' => $org, 'created_at' => $added, 'updated_at' => $added]);
    }

    private function employee(int $org, string $name, string $type, string $added, ?int $teacher = null): int
    {
        return DB::table('admin_employees')->insertGetId([
            'organization_id' => $org, 'name' => $name, 'type' => $type, 'teacher_detail_id' => $teacher,
            'created_at' => $added, 'updated_at' => $added,
        ]);
    }

    private function holders(string $table, string $holder, int $org): array
    {
        return DB::table($table)->where('organization_id', $org)->where('status', 'active')
            ->orderBy($holder)->pluck($holder)->all();
    }

    public function test_a_school_that_never_generated_gets_cards_for_the_people_added_from_4_october_only(): void
    {
        $this->student($this->newSchool, 'Old Student', '2026-10-03 23:59:00');
        $today     = $this->student($this->newSchool, 'New Student', '2026-10-04 10:15:00');
        $later     = $this->student($this->newSchool, 'Later Student', '2026-10-09 18:00:00');
        $this->teacher($this->newSchool, '2026-09-01 09:00:00');
        $newTch    = $this->teacher($this->newSchool, '2026-10-04 11:00:00');
        $this->employee($this->newSchool, 'Old Manager', 'management', '2026-08-01 09:00:00');
        $mgmt      = $this->employee($this->newSchool, 'New Manager', 'management', '2026-10-04 12:00:00');
        $driver    = $this->employee($this->newSchool, 'New Driver', 'driver', '2026-10-05 08:00:00');
        $staff     = $this->employee($this->newSchool, 'New Clerk', 'employee', '2026-10-05 09:00:00');
        // A teacher's staff (payroll) row: the teacher has a card of their own.
        $this->employee($this->newSchool, 'New Teacher', 'teacher', '2026-10-04 11:00:00', $newTch);

        $this->artisan('id-cards:generate-missing')->assertSuccessful();

        $this->assertSame([$today, $later], $this->holders('student_id_cards', 'student_detail_id', $this->newSchool));
        $this->assertSame([$newTch], $this->holders('teacher_id_cards', 'teacher_detail_id', $this->newSchool));
        $this->assertSame([$mgmt, $driver, $staff], $this->holders('employee_id_cards', 'admin_employee_id', $this->newSchool));

        $card = DB::table('student_id_cards')->where('student_detail_id', $today)->first();
        $this->assertSame('2027-03-31', substr($card->expiry_date, 0, 10));
        $this->assertNotNull($card->qr_code);

        // The school is not switched onto "everyone without a card".
        $this->assertSame(0, DB::table('id_card_generation_settings')->where('organization_id', $this->newSchool)->count());

        // A second run issues nothing more.
        $this->artisan('id-cards:generate-missing')->assertSuccessful();
        $this->assertSame(2, DB::table('student_id_cards')->where('organization_id', $this->newSchool)->count());
        $this->assertSame(1, DB::table('teacher_id_cards')->where('organization_id', $this->newSchool)->count());
        $this->assertSame(3, DB::table('employee_id_cards')->where('organization_id', $this->newSchool)->count());
    }

    public function test_a_school_that_generated_a_batch_still_gives_everyone_without_a_card_one(): void
    {
        $old   = $this->student($this->autoSchool, 'Old Student', '2026-06-01 09:00:00');
        $new   = $this->student($this->autoSchool, 'New Student', '2026-10-04 10:00:00');
        $tch   = $this->teacher($this->autoSchool, '2026-05-01 09:00:00');
        $mgmt  = $this->employee($this->autoSchool, 'Old Manager', 'management', '2026-05-01 09:00:00');

        $this->artisan('id-cards:generate-missing')->assertSuccessful();

        $this->assertSame([$old, $new], $this->holders('student_id_cards', 'student_detail_id', $this->autoSchool));
        $this->assertSame([$tch], $this->holders('teacher_id_cards', 'teacher_detail_id', $this->autoSchool));
        $this->assertSame([$mgmt], $this->holders('employee_id_cards', 'admin_employee_id', $this->autoSchool));
        $this->assertTrue((bool) DB::table('id_card_generation_settings')
            ->where('organization_id', $this->autoSchool)->where('type', 'teacher')->value('auto_enabled'));
    }
}
