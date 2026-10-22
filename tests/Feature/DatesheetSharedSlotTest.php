<?php

namespace Tests\Feature;

use App\Livewire\Admin\SeatingPlan;
use App\Models\User;
use App\Services\TeacherPushNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Datesheet: within one exam a class may sit several subjects in the same slot
 * (the same date and start time, or the same shift when untimed) — electives
 * such as Hindi / Sanskrit. Papers that overlap without sharing the slot, and
 * another exam's paper at that time, are still refused.
 */
class DatesheetSharedSlotTest extends TestCase
{
    private int $org = 9;
    private int $class;
    private int $exam;
    private array $subject = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('s', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('exam_name'); $t->string('academic_year')->nullable();
            $t->string('exam_type')->nullable(); $t->date('start_date')->nullable(); $t->boolean('is_published')->default(true); $t->timestamps();
        });
        Schema::create('exam_datesheets', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('exam_datesheet_papers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('exam_datesheet_id'); $t->unsignedBigInteger('subject_id')->nullable(); $t->date('exam_date')->nullable();
            $t->string('start_time')->nullable(); $t->string('end_time')->nullable(); $t->integer('shift')->default(1); $t->timestamps();
        });
        Schema::create('seating_rooms', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('room_name'); $t->string('building')->nullable();
            $t->integer('rows')->default(1); $t->integer('columns')->default(1); $t->integer('seat_capacity')->default(1);
            $t->integer('capacity')->default(1); $t->boolean('is_active')->default(true); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('seating_invigilators', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('seating_plans', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id')->nullable(); $t->string('name');
            $t->date('exam_date')->nullable(); $t->string('session')->nullable(); $t->string('status')->default('draft');
            $t->timestamp('generated_at')->nullable(); $t->integer('total_students')->default(0); $t->integer('total_seats')->default(0);
            $t->integer('conflict_count')->default(0); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('seating_seats', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('room_id'); $t->integer('row_no'); $t->integer('col_no'); $t->string('seat_number')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('roll_no')->nullable(); $t->string('admission_no')->nullable(); $t->timestamps();
        });
        Schema::create('seat_assignments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('seating_plan_id'); $t->unsignedBigInteger('seat_id')->nullable(); $t->integer('seat_position')->default(1);
            $t->unsignedBigInteger('room_id'); $t->unsignedBigInteger('student_id')->nullable(); $t->string('class_label')->nullable();
            $t->boolean('has_conflict')->default(false); $t->boolean('is_locked')->default(false); $t->timestamps();
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
        $this->mock(TeacherPushNotifier::class, fn ($m) => $m->shouldIgnoreMissing());

        $this->class = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 9', 'order' => 9]);
        $this->exam  = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Half Yearly']);
        foreach (['Hindi', 'Sanskrit', 'Maths'] as $name) {
            $id = DB::table('subjects')->insertGetId(['organization_id' => $this->org, 'name' => $name]);
            DB::table('standard_subjects')->insert(['standard_id' => $this->class, 'subject_id' => $id]);
            $this->subject[$name] = $id;
        }
    }

    /** The datesheet form for Class 9, with these subjects' papers filled in. */
    private function form(array $papers)
    {
        $page = Livewire::test(SeatingPlan::class)->call('openDatesheetCreate')
            ->set('dsExamId', (string) $this->exam)
            ->set('dsStandardId', (string) $this->class);

        foreach ($papers as $name => [$date, $start, $end, $shift]) {
            $id = $this->subject[$name];
            $page->set("dsPapers.{$id}.exam_date", $date)
                ->set("dsPapers.{$id}.start_time", $start)
                ->set("dsPapers.{$id}.end_time", $end)
                ->set("dsPapers.{$id}.shift", $shift);
        }

        return $page;
    }

    private function savedSubjects(): array
    {
        return DB::table('exam_datesheet_papers')->join('subjects', 'subjects.id', '=', 'exam_datesheet_papers.subject_id')
            ->orderBy('subjects.name')->pluck('subjects.name')->all();
    }

    public function test_two_subjects_in_one_slot_are_saved(): void
    {
        $this->form([
            'Hindi'    => ['2026-11-12', '10:00', '13:00', 1],
            'Sanskrit' => ['2026-11-12', '10:00', '13:00', 1],
            'Maths'    => ['2026-11-14', '10:00', '13:00', 1],
        ])->call('saveDatesheet')->assertHasNoErrors();

        $this->assertSame(['Hindi', 'Maths', 'Sanskrit'], $this->savedSubjects());
    }

    public function test_untimed_subjects_in_one_shift_are_saved(): void
    {
        $this->form([
            'Hindi'    => ['2026-11-12', '', '', 2],
            'Sanskrit' => ['2026-11-12', '', '', 2],
        ])->call('saveDatesheet')->assertHasNoErrors();

        $this->assertSame(['Hindi', 'Sanskrit'], $this->savedSubjects());
    }

    public function test_papers_that_overlap_without_sharing_the_start_are_refused(): void
    {
        $this->form([
            'Hindi' => ['2026-11-12', '10:00', '13:00', 1],
            'Maths' => ['2026-11-12', '12:00', '15:00', 1],
        ])->call('saveDatesheet')->assertHasErrors('dsPapers');

        $this->assertSame([], $this->savedSubjects());
    }

    public function test_another_exams_paper_at_that_time_is_still_a_clash(): void
    {
        $unit = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Unit Test']);
        $sheet = DB::table('exam_datesheets')->insertGetId(['organization_id' => $this->org, 'exam_id' => $unit, 'standard_id' => $this->class]);
        DB::table('exam_datesheet_papers')->insert(['exam_datesheet_id' => $sheet, 'subject_id' => $this->subject['Maths'],
            'exam_date' => '2026-11-12', 'start_time' => '10:00', 'end_time' => '13:00', 'shift' => 1]);

        $this->form([
            'Hindi' => ['2026-11-12', '10:00', '13:00', 1],
        ])->call('saveDatesheet')->assertHasErrors('dsPapers');

        $this->assertSame(1, DB::table('exam_datesheet_papers')->count());   // only the Unit Test paper
    }

    public function test_the_seating_list_names_every_subject_of_the_slot(): void
    {
        $this->form([
            'Hindi'    => ['2026-11-12', '10:00', '13:00', 1],
            'Sanskrit' => ['2026-11-12', '10:00', '13:00', 1],
        ])->call('saveDatesheet')->assertHasNoErrors();

        $page = Livewire::test(SeatingPlan::class)
            ->set('filterExamId', (string) $this->exam)
            ->set('filterStandardId', (string) $this->class)
            ->instance();
        $titles = (fn () => $this->paperTitles())->call($page);

        $this->assertSame(['2026-11-12|1' => 'Hindi / Sanskrit'], $titles);
    }
}
