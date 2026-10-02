<?php

namespace Tests\Feature;

use App\Livewire\Admin\TimeTable;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Timetable: the create / edit form — a row per time slot, the times typed as
 * 24-hour, a side column for the days left over, and + to put a period into
 * other sections of the class — and the cards in plain text.
 */
class TimeTablePanelTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        foreach (['standards', 'sections', 'subjects'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('organization_id');
                if ($table === 'sections') {
                    $t->unsignedBigInteger('standard_id');
                }
                $t->string('name');
                $t->string('code')->nullable();
                $t->integer('order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->unsignedBigInteger('subject_id');
            $t->timestamps();
        });
        Schema::create('teacher_time_tables', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->unsignedBigInteger('subject_id');
            $t->integer('day_of_week');
            $t->string('start_time')->nullable();
            $t->string('end_time')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('assigned_by')->nullable();
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->timestamps();
        });

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 500;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);

        // Class 5 with sections A (1), B (2), C (3); Class 6 with section A (4).
        DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5', 'order' => 1],
            ['id' => 2, 'organization_id' => $this->org, 'name' => 'Class 6', 'order' => 2],
        ]);
        DB::table('sections')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'A'],
            ['id' => 2, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'B'],
            ['id' => 3, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'C'],
            ['id' => 4, 'organization_id' => $this->org, 'standard_id' => 2, 'name' => 'A'],
        ]);
        // Hindi (1) and Maths (2), taught in every section.
        DB::table('subjects')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'name' => 'Hindi'],
            ['id' => 2, 'organization_id' => $this->org, 'name' => 'Maths'],
        ]);
        foreach ([[1, 1], [1, 2], [1, 3], [2, 4]] as [$standard, $section]) {
            foreach ([1, 2] as $subject) {
                DB::table('section_subjects')->insert(['organization_id' => $this->org, 'standard_id' => $standard, 'section_id' => $section, 'subject_id' => $subject]);
            }
        }
        // Teachers Ravi (1) and Sonu (2).
        foreach (['Ravi', 'Sonu'] as $i => $name) {
            DB::table('users')->insert(['id' => $i + 1, 'name' => $name, 'is_active' => 1, 'organization_id' => $this->org]);
            DB::table('teacher_details')->insert(['id' => $i + 1, 'user_id' => $i + 1, 'organization_id' => $this->org]);
        }
    }

    private function period(int $section, int $teacher, int $subject, int $day, string $start = '09:00', string $end = '09:45', int $standard = 1): void
    {
        DB::table('teacher_time_tables')->insert([
            'organization_id' => $this->org, 'standard_id' => $standard, 'section_id' => $section, 'teacher_detail_id' => $teacher,
            'subject_id' => $subject, 'day_of_week' => $day, 'start_time' => $start . ':00', 'end_time' => $end . ':00', 'is_active' => 1,
        ]);
    }

    /** A section's periods as "teacher subject day start-end", sorted. */
    private function periods(int $section, int $standard = 1): array
    {
        return DB::table('teacher_time_tables')->where('standard_id', $standard)->where('section_id', $section)->get()
            ->map(fn ($r) => $r->teacher_detail_id . ' ' . $r->subject_id . ' ' . $r->day_of_week . ' ' . substr($r->start_time, 0, 5) . '-' . substr($r->end_time, 0, 5))
            ->sort()->values()->all();
    }

    /** The form, opened on Class 5 · A. */
    private function form()
    {
        return Livewire::test(TimeTable::class)->call('onCreateTimetable')->set('createStandardId', '1')->set('createSectionId', '1');
    }

    private function panel(string $html): string
    {
        return substr($html, strpos($html, 'New Timetable</h2>') ?: strpos($html, 'Edit Timetable</h2>'));
    }

    // ───────────────────────── the form ─────────────────────────

    public function test_the_form_is_plain_rows_with_the_times_typed(): void
    {
        $page = $this->form();
        $rows = $page->get('scheduleRows');

        // A row for each subject of the section, one part each.
        $this->assertCount(2, $rows);
        $this->assertSame(['09:00', '10:00', 1, []], [$rows[0]['start_time'], $rows[0]['end_time'], $rows[0]['parts'][0]['subject_id'], $rows[0]['parts'][0]['days']]);
        $this->assertCount(1, $rows[0]['parts']);

        $panel = $this->panel($page->html());

        // Duration, then start and end together, then subject, teacher, days.
        $head = trim(preg_replace('/\s+/', ' ', strip_tags(substr($panel, strpos($panel, 'uppercase tracking-wider">'), 700))));
        $this->assertStringContainsString('# Duration Start – End Subject Teacher Days', $head);

        // The times are typed, not picked.
        $this->assertStringNotContainsString('type="time"', $panel);
        $this->assertMatchesRegularExpression('/<input type="text" inputmode="numeric" maxlength="5"[^>]*wire:model\.blur="scheduleRows\.0\.start_time"/s', $panel);
        $this->assertMatchesRegularExpression('/wire:model\.blur="scheduleRows\.0\.end_time"/', $panel);

        // No table, as Mark Attendance has none.
        $this->assertStringNotContainsString('<table', $panel);
    }

    public function test_a_typed_time_is_read_as_24_hour(): void
    {
        $page = $this->form();

        foreach (['930' => '09:30', '9' => '09:00', '1330' => '13:30', '13:5' => '13:05', '0905' => '09:05', '23:59' => '23:59'] as $typed => $read) {
            $page->set('scheduleRows.0.start_time', (string) $typed)->assertSet('scheduleRows.0.start_time', $read);
        }

        // 09:00 to 13:30 is four and a half hours.
        $page->set('scheduleRows.0.start_time', '9')->set('scheduleRows.0.end_time', '1330');
        $this->assertSame('4h 30m', $page->instance()->rowDuration(0));

        // What is not a time is left as typed and the row has no duration; such a row is not saved.
        $page->set('scheduleRows.0.end_time', '2575')->assertSet('scheduleRows.0.end_time', '2575');
        $this->assertSame('', $page->instance()->rowDuration(0));
        $page->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.days', [1])->call('onSaveTimetable')->assertSet('open', true);
        $this->assertSame([], $this->periods(1));
    }

    public function test_days_left_over_open_a_column_to_the_side(): void
    {
        $page = $this->form()->set('scheduleRows.0.start_time', '0900')->set('scheduleRows.0.end_time', '0945')
            ->set('scheduleRows.1.start_time', '1000')->set('scheduleRows.1.end_time', '1045');

        // No days yet: no side column.
        $this->assertCount(1, $page->get('scheduleRows.0.parts'));

        // Mon–Wed picked: Thu–Sat are left, so a column opens for them — days, subject, teacher.
        $page->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.days', ['1', '2', '3']);
        $this->assertCount(2, $page->get('scheduleRows.0.parts'));
        $this->assertSame([4, 5, 6], $page->instance()->availableDaysForPart(0, 1));
        $panel = $this->panel($page->html());
        $side = substr($panel, strpos($panel, 'wire:key="row-0-part-1"'));
        $side = substr($side, 0, strpos($side, 'Remove row'));
        $this->assertStringContainsString('Other days', $side);
        $days = strpos($side, 'scheduleRows.0.parts.1.days');
        $subject = strpos($side, 'scheduleRows.0.parts.1.subject_id');
        $teacher = strpos($side, 'scheduleRows.0.parts.1.teacher_id');
        $this->assertTrue($days !== false && $subject !== false && $teacher !== false && $days < $subject && $subject < $teacher);

        // Thu, Fri given to Maths and Sonu: Sat is still left, so one more column.
        $page->set('scheduleRows.0.parts.1.subject_id', 2)->set('scheduleRows.0.parts.1.teacher_id', 2)->set('scheduleRows.0.parts.1.days', ['4', '5']);
        $this->assertCount(3, $page->get('scheduleRows.0.parts'));
        $this->assertSame([6], $page->instance()->availableDaysForPart(0, 2));

        // The whole week picked in the first part: nothing left, no side column.
        $page->set('scheduleRows.1.parts.0.days', ['1', '2', '3', '4', '5', '6']);
        $this->assertCount(1, $page->get('scheduleRows.1.parts'));

        // Saved, each part is its own periods.
        $page->call('removeRow', 1)->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame([
            '1 1 1 09:00-09:45', '1 1 2 09:00-09:45', '1 1 3 09:00-09:45',
            '2 2 4 09:00-09:45', '2 2 5 09:00-09:45',
        ], $this->periods(1));
    }

    public function test_edit_brings_a_slot_back_as_one_row_with_its_parts(): void
    {
        foreach ([1, 2, 3] as $day) $this->period(1, 1, 1, $day);
        foreach ([4, 5] as $day) $this->period(1, 2, 2, $day);
        $this->period(1, 2, 2, 1, '10:00', '10:45');

        $page = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->assertSet('open', true)->assertSet('isEdit', true);
        $rows = $page->get('scheduleRows');

        $this->assertCount(2, $rows);
        $this->assertSame(['09:00', '09:45'], [$rows[0]['start_time'], $rows[0]['end_time']]);
        $this->assertSame([[1, 1, [1, 2, 3]], [2, 2, [4, 5]]], array_map(fn ($p) => [$p['subject_id'], $p['teacher_id'], $p['days']], array_slice($rows[0]['parts'], 0, 2)));
        $this->assertSame([], $rows[0]['parts'][2]['days']);      // Sat is left: a column is offered for it
        $this->assertSame(['10:00', '10:45', [1]], [$rows[1]['start_time'], $rows[1]['end_time'], $rows[1]['parts'][0]['days']]);

        // Saved untouched, the timetable is as it was.
        $before = $this->periods(1);
        $page->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame($before, $this->periods(1));
    }

    public function test_a_teacher_busy_in_another_class_stops_the_save(): void
    {
        $this->period(4, 1, 1, 1, '09:00', '09:45', 2);   // Ravi is in Class 6 A on Monday at nine

        $page = $this->form()->call('removeRow', 1)
            ->set('scheduleRows.0.start_time', '0915')->set('scheduleRows.0.end_time', '1000')
            ->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.days', ['1', '2']);

        $this->assertSame('Busy with Class 6 A (Mon)', $page->instance()->getPartConflict(0, 0));
        $this->assertStringContainsString('Hindi: Busy with Class 6 A (Mon)', $this->panel($page->html()));

        $page->call('onSaveTimetable')->assertSet('open', true);
        $this->assertSame([], $this->periods(1));

        // Another teacher, and it saves.
        $page->set('scheduleRows.0.parts.0.teacher_id', 2)->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['2 1 1 09:15-10:00', '2 1 2 09:15-10:00'], $this->periods(1));
    }

    // ───────────────────────── + : other sections of the class ─────────────────────────

    public function test_plus_puts_the_period_into_other_sections_of_the_class(): void
    {
        $page = $this->form()->call('removeRow', 1)
            ->set('scheduleRows.0.start_time', '0900')->set('scheduleRows.0.end_time', '0945')
            ->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.days', ['1', '2']);

        // The + lists the class's other sections — not this one, not another class's.
        $this->assertSame(['B', 'C'], array_column($page->instance()->otherSections(), 'name'));
        $panel = $this->panel($page->html());
        $this->assertStringContainsString('wire:model.live="scheduleRows.0.sections"', $panel);
        $this->assertStringContainsString('Same teacher, subject and time, also in:', $panel);

        // B ticked: the teacher taking A and B together is not "busy", and both get the period.
        $page->set('scheduleRows.0.sections', ['2']);
        $this->assertNull($page->instance()->getPartConflict(0, 0));
        $this->assertStringContainsString('Also in B', $this->panel($page->html()));
        $page->call('onSaveTimetable')->assertSet('open', false);

        $this->assertSame(['1 1 1 09:00-09:45', '1 1 2 09:00-09:45'], $this->periods(1));
        $this->assertSame(['1 1 1 09:00-09:45', '1 1 2 09:00-09:45'], $this->periods(2));
        $this->assertSame([], $this->periods(3));

        // Opened again — from either section — the row knows it is shared, and is not a clash.
        $a = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1);
        $this->assertSame([2], $a->get('scheduleRows.0.sections'));
        $this->assertNull($a->instance()->getPartConflict(0, 0));
        $b = Livewire::test(TimeTable::class)->call('onEditSection', 1, 2);
        $this->assertSame([1], $b->get('scheduleRows.0.sections'));
        $this->assertNull($b->instance()->getPartConflict(0, 0));

        // A change to the shared row is carried to the other section: Sonu, at ten.
        $a->set('scheduleRows.0.parts.0.teacher_id', 2)->set('scheduleRows.0.start_time', '1000')->set('scheduleRows.0.end_time', '1045')
            ->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['2 1 1 10:00-10:45', '2 1 2 10:00-10:45'], $this->periods(1));
        $this->assertSame(['2 1 1 10:00-10:45', '2 1 2 10:00-10:45'], $this->periods(2));

        // B taken off, C put on: B loses the period, C gains it.
        Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->set('scheduleRows.0.sections', ['3'])
            ->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['2 1 1 10:00-10:45', '2 1 2 10:00-10:45'], $this->periods(1));
        $this->assertSame([], $this->periods(2));
        $this->assertSame(['2 1 1 10:00-10:45', '2 1 2 10:00-10:45'], $this->periods(3));
    }

    public function test_a_section_that_already_has_something_at_that_time_is_not_overwritten(): void
    {
        $this->period(2, 2, 2, 1, '09:15', '10:00');   // B has Maths with Sonu on Monday at 9:15

        $page = $this->form()->call('removeRow', 1)
            ->set('scheduleRows.0.start_time', '0900')->set('scheduleRows.0.end_time', '0945')
            ->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.days', ['1'])
            ->set('scheduleRows.0.sections', ['2']);

        $this->assertSame('B already has Maths at this time (Mon)', $page->instance()->getShareConflict(0));
        $this->assertStringContainsString('B already has Maths at this time (Mon)', $this->panel($page->html()));

        $page->call('onSaveTimetable')->assertSet('open', true);
        $this->assertSame([], $this->periods(1));
        $this->assertSame(['2 2 1 09:15-10:00'], $this->periods(2));

        // On a day B is free, it goes through.
        $page->set('scheduleRows.0.parts.0.days', ['2'])->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['1 1 2 09:00-09:45'], $this->periods(1));
        $this->assertSame(['1 1 2 09:00-09:45', '2 2 1 09:15-10:00'], $this->periods(2));
    }

    // ───────────────────────── the cards ─────────────────────────

    public function test_the_timetable_is_shown_in_plain_text(): void
    {
        foreach ([1, 2, 3] as $day) $this->period(1, 1, 1, $day);
        $this->period(1, 2, 2, 4, '10:00', '10:45');

        $html = Livewire::test(TimeTable::class)->set('filterClass', '1')->set('filterSection', '1')->html();
        $card = substr($html, strpos($html, '<table'));
        $card = substr($card, 0, strpos($card, '</table>'));
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($card)));

        $this->assertStringContainsString('1 09:00 AM – 09:45 AM Hindi Ravi Mon, Tue, Wed', $text);
        $this->assertStringContainsString('2 10:00 AM – 10:45 AM Maths Sonu Thu', $text);

        // No tags, no initials in circles.
        foreach (['bg-indigo-50', 'bg-teal-100', 'rounded-full', 'bg-blue-50'] as $class) {
            $this->assertStringNotContainsString($class, $card);
        }
        $this->assertStringContainsString('Class 5 · A', $html);
    }
}
