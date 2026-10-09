<?php

namespace Tests\Feature;

use App\Livewire\Admin\SeatingPlan;
use App\Models\Admin\Seating\SeatingPlan as SeatingPlanModel;
use App\Models\User;
use App\Services\Seating\GeneratedSeating;
use App\Services\StudentPushNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Seating: a class is seated for an exam once — once its plan is generated it
 * is shown as "already generated" and not generated again; plans made twice
 * before that list once (the newest copy); the list's actions are View,
 * Download and Delete icons (no status, Print or Publish — a generated plan is
 * published as it is made, and its students hear it once); a student's name,
 * once an exam is picked, finds where they sit.
 */
class SeatingGenerateOnceTest extends TestCase
{
    private int $org = 9;

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
        Schema::create('invigilator_assignments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('seating_plan_id'); $t->unsignedBigInteger('room_id'); $t->unsignedBigInteger('invigilator_id'); $t->timestamps();
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
    }

    /** Class 1 and Class 10 with datesheets for one exam, a room, and Class 1 seated twice on one day. */
    private function school(): array
    {
        $c1  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 1', 'order' => 1]);
        $c10 = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 10', 'order' => 10]);
        $exam  = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Unit Test']);
        $other = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Annual']);
        foreach ([$c1, $c10] as $std) {
            $ds = DB::table('exam_datesheets')->insertGetId(['organization_id' => $this->org, 'exam_id' => $exam, 'standard_id' => $std]);
            DB::table('exam_datesheet_papers')->insert(['exam_datesheet_id' => $ds, 'exam_date' => '2026-09-01', 'shift' => 1]);
        }
        $room = DB::table('seating_rooms')->insertGetId(['organization_id' => $this->org, 'room_name' => 'Room 1', 'capacity' => 30]);

        $plan = fn (string $date, array $labels) => tap(DB::table('seating_plans')->insertGetId([
            'organization_id' => $this->org, 'exam_id' => $exam, 'name' => 'Unit Test — ' . $date,
            'exam_date' => $date, 'session' => 'Shift 1',
        ]), function ($id) use ($labels, $room) {
            foreach ($labels as $i => $label) {
                DB::table('seat_assignments')->insert(['seating_plan_id' => $id, 'room_id' => $room, 'student_id' => 100 + $i, 'class_label' => $label]);
            }
        });

        DB::table('student_details')->insert([
            ['organization_id' => $this->org, 'user_id' => 100, 'standard_id' => $c1, 'full_name' => 'Asha Rao', 'roll_no' => '1'],
            ['organization_id' => $this->org, 'user_id' => 101, 'standard_id' => $c1, 'full_name' => 'Bilal Khan', 'roll_no' => '2'],
        ]);

        $old  = $plan('2026-09-01', ['Class 1-A', 'Class 1-A']);   // generated, then generated again
        $new  = $plan('2026-09-01', ['Class 1-A']);
        $next = $plan('2026-09-02', ['Class 1-A']);

        return compact('c1', 'c10', 'exam', 'other', 'room', 'old', 'new', 'next');
    }

    public function test_classes_already_seated_are_read_off_the_seats(): void
    {
        $s = $this->school();

        $this->assertSame([$s['c1']], GeneratedSeating::standardIds($this->org, $s['exam']));   // Class 1, not Class 10
        $this->assertSame([], GeneratedSeating::standardIds($this->org, $s['other']));

        $plans = SeatingPlanModel::where('exam_id', $s['exam'])->get();
        $this->assertEqualsCanonicalizing([$s['new'], $s['next']], GeneratedSeating::currentPlanIds($plans));
    }

    public function test_generate_panel_leaves_out_and_refuses_a_generated_class(): void
    {
        $s = $this->school();

        $page = Livewire::test(SeatingPlan::class)->call('openGeneratePanel')
            ->set('generateForm.exam_id', (string) $s['exam']);
        $this->assertSame([(string) $s['c10']], $page->get('generateForm.standard_ids'));   // Class 1 not ticked
        $html = $page->html();
        $this->assertSame(1, substr_count($html, '>already generated</span>'));
        $this->assertMatchesRegularExpression('/value="' . $s['c1'] . '" wire:model="generateForm.standard_ids" disabled/', $html);
        $this->assertStringNotContainsString('Reads the exam datesheet', $html);

        $page->call('selectAllClasses');
        $this->assertSame([(string) $s['c10']], $page->get('generateForm.standard_ids'));

        // Asked for anyway: refused, nothing made.
        $before = DB::table('seating_plans')->count();
        $page->set('generateForm.standard_ids', [(string) $s['c1']])->set('generateForm.name', 'Again')->call('generatePlan');
        $this->assertSame($before, DB::table('seating_plans')->count());
        $this->assertTrue($page->get('showGeneratePanel'));
    }

    public function test_list_shows_a_plan_made_twice_once_with_icon_actions(): void
    {
        $s = $this->school();

        $html = Livewire::test(SeatingPlan::class)
            ->set('filterExamId', (string) $s['exam'])->set('filterRoomId', (string) $s['room'])->html();

        $this->assertStringNotContainsString('wire:key="sess-' . $s['old'] . '"', $html);
        $this->assertStringContainsString('wire:key="sess-' . $s['new'] . '"', $html);
        $this->assertStringContainsString('wire:key="sess-' . $s['next'] . '"', $html);
        $this->assertStringContainsString('title="View"' . "\n" . '                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg', $html);
        $this->assertStringNotContainsString('wire:click="publishPlan(', $html);                  // no Publish
        $this->assertStringNotContainsString('title="Print"', $html);                             // no Print
        $this->assertStringNotContainsString("Draft — not published yet", $html);                 // no status dot
        $this->assertStringContainsString('title="Download"', $html);
        $this->assertStringContainsString('wire:click="confirmDeletePlan(' . $s['new'] . ')"', $html);
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-600 hover:bg-blue-50', $html);   // no boxed buttons
    }

    public function test_student_search_finds_where_they_sit(): void
    {
        $s    = $this->school();
        $page = Livewire::test(SeatingPlan::class);

        $this->assertStringContainsString('wire:model.live.debounce.300ms="filterSearch" disabled', $page->html());   // needs an exam

        $html = $page->set('filterExamId', (string) $s['exam'])->set('filterSearch', 'asha')->html();
        $this->assertStringNotContainsString('wire:model.live.debounce.300ms="filterSearch" disabled', $html);
        $this->assertSame(2, substr_count($html, '>Asha Rao</p>'));                     // 01 Sep (the newest copy) and 02 Sep
        $this->assertStringContainsString('wire:key="find-' . $s['new'] . '-', $html);
        $this->assertStringNotContainsString('wire:key="find-' . $s['old'] . '-', $html);
        $this->assertStringContainsString('Class 1-A · Roll 1', $html);
        $this->assertStringNotContainsString('Bilal Khan', $html);

        $html = $page->set('filterSearch', 'nobody')->html();
        $this->assertStringContainsString('No seat found', $html);

        $page->call('clearGraphFilters');
        $this->assertSame('', $page->get('filterSearch'));
    }

    public function test_a_generated_plan_is_published_and_students_hear_it_once(): void
    {
        $s = $this->school();
        // Room 1's desks, and two Class 10 students to seat.
        for ($r = 1; $r <= 5; $r++) {
            for ($c = 1; $c <= 6; $c++) {
                DB::table('seating_seats')->insert(['room_id' => $s['room'], 'row_no' => $r, 'col_no' => $c, 'seat_number' => chr(64 + $c) . $r]);
            }
        }
        DB::table('seating_rooms')->where('id', $s['room'])->update(['rows' => 5, 'columns' => 6, 'capacity' => 30]);
        DB::table('student_details')->insert([
            ['organization_id' => $this->org, 'user_id' => 500, 'standard_id' => $s['c10'], 'full_name' => 'Chand'],
            ['organization_id' => $this->org, 'user_id' => 501, 'standard_id' => $s['c10'], 'full_name' => 'Dev'],
        ]);

        $this->mock(StudentPushNotifier::class, function ($m) {
            $m->shouldReceive('seatingGenerated')->once()->with(\Mockery::on(fn ($ids) => count($ids) === 1));
            $m->shouldIgnoreMissing();
        });

        $before = DB::table('seating_plans')->pluck('id');
        Livewire::test(SeatingPlan::class)->call('openGeneratePanel')
            ->set('generateForm.exam_id', (string) $s['exam'])->set('generateForm.name', 'Unit Test — Seating')
            ->call('generatePlan');

        $made = DB::table('seating_plans')->whereNotIn('id', $before)->get();
        $this->assertCount(1, $made);                                                    // 01 Sep, Shift 1
        $this->assertSame('published', $made->first()->status);
        $this->assertSame(2, DB::table('seat_assignments')->where('seating_plan_id', $made->first()->id)->whereNotNull('student_id')->count());
    }

    public function test_a_student_is_not_at_the_same_desk_for_the_next_paper(): void
    {
        $s = $this->school();
        for ($r = 1; $r <= 5; $r++) {
            for ($c = 1; $c <= 6; $c++) {
                DB::table('seating_seats')->insert(['room_id' => $s['room'], 'row_no' => $r, 'col_no' => $c, 'seat_number' => chr(64 + $c) . $r]);
            }
        }
        DB::table('seating_rooms')->where('id', $s['room'])->update(['rows' => 5, 'columns' => 6, 'capacity' => 30]);
        foreach (['Chand', 'Dev', 'Esha', 'Farah'] as $i => $name) {
            DB::table('student_details')->insert(['organization_id' => $this->org, 'user_id' => 500 + $i, 'standard_id' => $s['c10'], 'full_name' => $name]);
        }
        // Class 10 sits two papers: 01 Sep and 02 Sep.
        $sheet = DB::table('exam_datesheets')->where('exam_id', $s['exam'])->where('standard_id', $s['c10'])->value('id');
        DB::table('exam_datesheet_papers')->insert(['exam_datesheet_id' => $sheet, 'exam_date' => '2026-09-02', 'shift' => 1]);

        $this->mock(StudentPushNotifier::class, fn ($m) => $m->shouldIgnoreMissing());
        $before = DB::table('seating_plans')->pluck('id');
        Livewire::test(SeatingPlan::class)->call('openGeneratePanel')
            ->set('generateForm.exam_id', (string) $s['exam'])->set('generateForm.name', 'Unit Test — Seating')
            ->call('generatePlan');

        $made = DB::table('seating_plans')->whereNotIn('id', $before)->orderBy('exam_date')->pluck('id');
        $this->assertCount(2, $made);
        $desk = fn ($plan) => DB::table('seat_assignments')->where('seating_plan_id', $plan)->whereNotNull('student_id')->pluck('seat_id', 'student_id')->all();
        [$day1, $day2] = [$desk($made[0]), $desk($made[1])];
        $this->assertCount(4, $day2);
        foreach ($day2 as $student => $seat) {
            $this->assertNotEquals($day1[$student], $seat, "student {$student} is at the same desk on the next paper");
        }
    }

    public function test_the_push_is_one_per_student_for_the_exam(): void
    {
        $s = $this->school();
        DB::table('users')->insert(['id' => 100, 'name' => 'Asha Rao', 'role' => 'user', 'organization_id' => $this->org]);   // students sign in as 'user'
        DB::table('seating_plans')->whereIn('id', [$s['new'], $s['next']])->update(['status' => 'published']);

        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public array $sent = [];
            public function notifyUserIds(...$args) { $this->sent[] = $args; }
        });

        $notifier = app(StudentPushNotifier::class);
        $notifier->seatingGenerated([$s['new'], $s['next']]);
        $notifier->flush();

        $sent = app(\App\Services\FirebaseNotificationService::class)->sent;
        $this->assertCount(1, $sent);                                                  // one push, not one a session
        [$users, $type, $payload] = $sent[0];
        $this->assertSame([100], $users);
        $this->assertSame('seating_published', $type);
        $this->assertStringContainsString('Your seats for Unit Test are ready.', $payload['body']);
        $this->assertStringContainsString('Tue, 01 Sep 2026 · Shift 1', $payload['body']);
        $this->assertStringContainsString('Wed, 02 Sep 2026 · Shift 1', $payload['body']);
    }
}
