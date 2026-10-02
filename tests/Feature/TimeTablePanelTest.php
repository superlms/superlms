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
 * Timetable: the Periods tab (the school's day: its periods and the lunch
 * break), the create / edit form — a row per period shown by its serial, the
 * teacher, the subject, the sections and the days, with a line under it for
 * the days left over — and the cards as a flat list.
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

        Schema::create('timetable_periods', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('type')->default('period');
            $t->unsignedTinyInteger('period_no')->nullable();
            $t->string('start_time');
            $t->string('end_time');
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

    /** The school's day: three periods — 09:00, 09:45 and, after lunch (10:30–11:00), 11:00. */
    private function day(array $periods = [['09:00', '09:45'], ['09:45', '10:30'], ['11:00', '11:45']], ?array $lunch = ['10:30', '11:00']): void
    {
        DB::table('timetable_periods')->where('organization_id', $this->org)->delete();
        foreach ($periods as $i => [$start, $end]) {
            DB::table('timetable_periods')->insert(['organization_id' => $this->org, 'type' => 'period', 'period_no' => $i + 1, 'start_time' => $start . ':00', 'end_time' => $end . ':00']);
        }
        if ($lunch) {
            DB::table('timetable_periods')->insert(['organization_id' => $this->org, 'type' => 'lunch', 'period_no' => null, 'start_time' => $lunch[0] . ':00', 'end_time' => $lunch[1] . ':00']);
        }
    }

    /** The saved day as "1 09:00-09:45" lines, the lunch break as "lunch 10:30-11:00". */
    private function savedDay(): array
    {
        return DB::table('timetable_periods')->where('organization_id', $this->org)->orderBy('start_time')->get()
            ->map(fn ($r) => ($r->type === 'lunch' ? 'lunch' : $r->period_no) . ' ' . substr($r->start_time, 0, 5) . '-' . substr($r->end_time, 0, 5))
            ->all();
    }

    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    // ───────────────────────── the Periods tab ─────────────────────────

    public function test_periods_is_a_tab_after_teacher_view_with_its_own_add_button(): void
    {
        $page = Livewire::test(TimeTable::class);
        $html = $page->html();

        $this->assertTrue(strpos($html, 'Teacher View') < strpos($html, "setViewMode('periods')"));
        $this->assertStringContainsString('Create Timetable', $html);

        // On the tab, the header's button adds the periods; there is nothing to filter.
        $html = $page->call('setViewMode', 'periods')->assertSet('viewMode', 'periods')->html();
        $this->assertStringContainsString('wire:click="openPeriodPanel"', $html);
        $this->assertStringContainsString('Add Periods', $html);
        $this->assertStringContainsString('No periods added yet.', $html);
        $this->assertStringNotContainsString('Filter by:', $html);
        $this->assertStringNotContainsString('wire:click="onCreateTimetable"', $html);
    }

    public function test_the_periods_are_added_in_a_panel_and_listed(): void
    {
        $page = Livewire::test(TimeTable::class)->call('setViewMode', 'periods')->call('openPeriodPanel')
            ->assertSet('showPeriodPanel', true)->assertSet('periodRows', []);

        // How many periods: that many rows.
        $page->set('periodCount', '3');
        $this->assertCount(3, $page->get('periodRows'));

        // The times are typed as 24-hour; a period starts where the one before it ends…
        $page->set('periodRows.0.start', '9')->set('periodRows.0.end', '945')
            ->assertSet('periodRows.0.start', '09:00')->assertSet('periodRows.0.end', '09:45')
            ->assertSet('periodRows.1.start', '09:45');
        // …or, when that is where the lunch break starts, where the lunch break ends.
        $page->set('lunchStart', '1030')->set('lunchEnd', '11')->set('periodRows.1.end', '1030')
            ->assertSet('periodRows.2.start', '11:00')
            ->set('periodRows.2.end', '1145');

        // An end corrected takes the next period's start with it — unless that was typed to something else.
        $page->set('periodRows.0.end', '0940')->assertSet('periodRows.1.start', '09:40')
            ->set('periodRows.1.start', '0950')->set('periodRows.0.end', '0945')->assertSet('periodRows.1.start', '09:50')
            ->set('periodRows.1.start', '0945');

        $html = $page->html();
        $this->assertStringContainsString('Number of periods', $html);
        $this->assertStringContainsString('wire:model.blur="periodRows.0.start"', $html);
        $this->assertStringContainsString('wire:model.blur="lunchStart"', $html);
        $this->assertStringNotContainsString('type="time"', $html);

        $page->call('savePeriods')->assertSet('showPeriodPanel', false);
        $this->assertSame(['1 09:00-09:45', '2 09:45-10:30', 'lunch 10:30-11:00', '3 11:00-11:45'], $this->savedDay());

        // Listed in order, the lunch break where it falls — a flat list, no table.
        $html = $page->html();
        $list = $this->text(substr($html, strpos($html, '>Periods</h3>')));
        $this->assertStringContainsString('3 periods · Lunch break 10:30 AM – 11:00 AM', $list);
        $this->assertStringContainsString('1 Period 1 09:00 AM – 09:45 AM 45m 2 Period 2 09:45 AM – 10:30 AM 45m Lunch break 10:30 AM – 11:00 AM 30m 3 Period 3 11:00 AM – 11:45 AM 45m', $list);
        $this->assertStringNotContainsString('<table', $html);
        $this->assertStringContainsString('Edit Periods', $html);

        // Opened again, it holds what was saved, to be changed.
        $page->call('openPeriodPanel')->assertSet('periodCount', '3')->assertSet('lunchStart', '10:30')
            ->assertSet('periodRows', [['start' => '09:00', 'end' => '09:45'], ['start' => '09:45', 'end' => '10:30'], ['start' => '11:00', 'end' => '11:45']]);

        // Fewer periods: the rows beyond go; more: blank rows are added.
        $page->set('periodCount', '2');
        $this->assertCount(2, $page->get('periodRows'));
        $page->set('periodCount', '40');
        $this->assertCount(TimeTable::MAX_PERIODS, $page->get('periodRows'));
    }

    public function test_periods_that_do_not_make_a_day_are_not_saved(): void
    {
        $page = Livewire::test(TimeTable::class)->call('openPeriodPanel');

        // No periods at all.
        $page->call('savePeriods')->assertSet('showPeriodPanel', true);

        // A row left empty.
        $page->set('periodCount', '2')->set('periodRows.0.start', '0900')->set('periodRows.0.end', '0945')->set('periodRows.1.start', '');
        $this->assertSame([], $page->instance()->periodErrors());           // not an error while it is being filled in
        $this->assertSame([1 => 'Enter its start and end time.'], array_slice($page->instance()->periodErrors(true), 0, null, true));
        $page->call('savePeriods')->assertSet('showPeriodPanel', true);

        // Ends before it starts; starts before the one above ends; not a time.
        $page->set('periodRows.1.start', '1000')->set('periodRows.1.end', '0950');
        $this->assertSame('It has to end after it starts.', $page->instance()->periodErrors()[1]);
        $page->set('periodRows.1.start', '0930')->set('periodRows.1.end', '1015');
        $this->assertSame('It starts before period 1 ends.', $page->instance()->periodErrors()[1]);
        $page->set('periodRows.1.start', '2575');
        $this->assertStringContainsString('Not a time.', $page->instance()->periodErrors()[1]);
        $this->assertStringContainsString('Not a time.', $page->html());

        // The lunch break may not run into a period.
        $page->set('periodRows.1.start', '0945')->set('periodRows.1.end', '1030')->set('lunchStart', '1015')->set('lunchEnd', '1045');
        $this->assertSame(['lunch' => 'It runs into period 2.'], $page->instance()->periodErrors());
        $page->call('savePeriods')->assertSet('showPeriodPanel', true);
        $this->assertSame([], $this->savedDay());

        // Without a lunch break it is still a day.
        $page->set('lunchStart', '')->set('lunchEnd', '')->call('savePeriods')->assertSet('showPeriodPanel', false);
        $this->assertSame(['1 09:00-09:45', '2 09:45-10:30'], $this->savedDay());
    }

    public function test_a_period_whose_time_changes_takes_its_classes_along(): void
    {
        $this->day();
        $this->period(1, 1, 1, 1);                             // period 1
        $this->period(1, 2, 2, 1, '09:45', '10:30');           // period 2
        $this->period(4, 2, 2, 2, '09:00', '09:45', 2);        // period 1, another class
        $this->period(1, 1, 1, 3, '14:00', '14:40');           // not a period

        // Period 1 now ends at 09:40, and period 2 runs 09:40 – 10:20.
        Livewire::test(TimeTable::class)->call('openPeriodPanel')
            ->set('periodRows.0.end', '0940')->set('periodRows.1.start', '0940')->set('periodRows.1.end', '1020')
            ->call('savePeriods')->assertSet('showPeriodPanel', false);

        $this->assertSame(['1 09:00-09:40', '2 09:40-10:20', 'lunch 10:30-11:00', '3 11:00-11:45'], $this->savedDay());
        $this->assertSame(['1 1 1 09:00-09:40', '1 1 3 14:00-14:40', '2 2 1 09:40-10:20'], $this->periods(1));
        $this->assertSame(['2 2 2 09:00-09:40'], $this->periods(4, 2));
    }

    // ───────────────────────── the form ─────────────────────────

    public function test_the_form_is_a_row_a_period_shown_by_its_serial(): void
    {
        $this->day();
        $page = $this->form();
        $rows = $page->get('scheduleRows');

        // A row for each period, in order; nothing chosen; the whole week.
        $this->assertSame([1, 2, 3], array_column($rows, 'period'));
        $this->assertSame(['09:00', '09:45'], [$rows[0]['start_time'], $rows[0]['end_time']]);
        $this->assertCount(1, $rows[0]['parts']);
        $this->assertSame(['', '', [], [1, 2, 3, 4, 5, 6]], [$rows[0]['parts'][0]['teacher_id'], $rows[0]['parts'][0]['subject_id'], $rows[0]['parts'][0]['sections'], $rows[0]['parts'][0]['days']]);

        $panel = $this->panel($page->html());

        // The serial, then the teacher, the subject, the sections and the days.
        $head = $this->text(substr($panel, strpos($panel, 'uppercase tracking-wider">'), 700));
        $this->assertStringContainsString('> Period Teacher Subject Section Days', $head);
        $teacher = strpos($panel, 'scheduleRows.0.parts.0.teacher_id');
        $subject = strpos($panel, 'scheduleRows.0.parts.0.subject_id');
        $section = strpos($panel, 'scheduleRows.0.parts.0.sections');
        $days    = strpos($panel, 'scheduleRows.0.parts.0.days');
        $this->assertTrue($teacher < $subject && $subject < $section && $section < $days);

        // Only the serial: no time in the form, and none to type.
        foreach (['09:00', '09:45', '11:45', 'start_time', 'type="time"', '<table'] as $absent) {
            $this->assertStringNotContainsString($absent, $panel);
        }
        $this->assertStringContainsString('Lunch break', $panel);
        $this->assertStringContainsString('All days', $panel);
    }

    public function test_a_class_with_one_section_has_no_section_field(): void
    {
        $this->day();

        $one = Livewire::test(TimeTable::class)->call('onCreateTimetable')->set('createStandardId', '2')->set('createSectionId', '4');
        $panel = $this->panel($one->html());
        $this->assertStringContainsString('scheduleRows.0.parts.0.teacher_id', $panel);
        $this->assertStringNotContainsString('scheduleRows.0.parts.0.sections', $panel);
        $this->assertStringContainsString('> Period Teacher Subject Days', $this->text(substr($panel, strpos($panel, 'uppercase tracking-wider">'), 700)));

        // More than one: the field is there, this section in it and the others to tick.
        $panel = $this->panel($this->form()->html());
        $this->assertStringContainsString('wire:model.live="scheduleRows.0.parts.0.sections"', $panel);
        $this->assertSame(['B', 'C'], array_column($this->form()->instance()->otherSections(), 'name'));
    }

    public function test_days_left_over_open_a_line_under_the_period(): void
    {
        $this->day();
        $page = $this->form()->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.subject_id', 1);

        // The whole week: no line under it.
        $this->assertCount(1, $page->get('scheduleRows.0.parts'));

        // Mon–Wed only: Thu–Sat are left, and a line opens under it holding them.
        $page->set('scheduleRows.0.parts.0.days', ['1', '2', '3']);
        $this->assertCount(2, $page->get('scheduleRows.0.parts'));
        $this->assertSame([4, 5, 6], $page->get('scheduleRows.0.parts.1.days'));
        $panel = $this->panel($page->html());
        $under = substr($panel, strpos($panel, 'wire:key="row-0-part-1"'));
        $under = substr($under, 0, strpos($under, 'wire:key="row-1"'));
        $teacher = strpos($under, 'scheduleRows.0.parts.1.teacher_id');
        $subject = strpos($under, 'scheduleRows.0.parts.1.subject_id');
        $section = strpos($under, 'scheduleRows.0.parts.1.sections');
        $this->assertTrue($teacher !== false && $teacher < $subject && $subject < $section);
        $this->assertStringContainsString('Thu, Fri, Sat', $under);

        // Its teacher and subject chosen, it still holds the days left.
        $page->set('scheduleRows.0.parts.1.teacher_id', 2)->set('scheduleRows.0.parts.1.subject_id', 2);
        $this->assertSame([4, 5, 6], $page->get('scheduleRows.0.parts.1.days'));

        // Sat unticked there: one more line, for Sat.
        $page->set('scheduleRows.0.parts.1.days', ['4', '5']);
        $this->assertCount(3, $page->get('scheduleRows.0.parts'));
        $this->assertSame([6], $page->get('scheduleRows.0.parts.2.days'));

        // The first line takes Thu back: it leaves the line under it.
        $page->set('scheduleRows.0.parts.0.days', ['1', '2', '3', '4']);
        $this->assertSame([5], $page->get('scheduleRows.0.parts.1.days'));
        $this->assertSame([6], $page->get('scheduleRows.0.parts.2.days'));

        // Saved, each line is its own periods, at the period's time; a line left blank is nothing.
        $page->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame([
            '1 1 1 09:00-09:45', '1 1 2 09:00-09:45', '1 1 3 09:00-09:45', '1 1 4 09:00-09:45',
            '2 2 5 09:00-09:45',
        ], $this->periods(1));

        // The whole week again: the lines under it go.
        $again = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->set('scheduleRows.0.parts.0.days', ['1', '2', '3', '4', '5', '6']);
        $this->assertCount(1, $again->get('scheduleRows.0.parts'));
    }

    public function test_edit_brings_each_period_back_with_its_lines(): void
    {
        $this->day();
        foreach ([1, 2, 3] as $day) $this->period(1, 1, 1, $day);
        foreach ([4, 5] as $day) $this->period(1, 2, 2, $day);
        $this->period(1, 2, 2, 1, '11:00', '11:45');

        $page = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->assertSet('open', true)->assertSet('isEdit', true);
        $rows = $page->get('scheduleRows');

        $this->assertSame([1, 2, 3], array_column($rows, 'period'));
        $this->assertSame([[1, 1, [1, 2, 3]], [2, 2, [4, 5]]], array_map(fn ($p) => [$p['subject_id'], $p['teacher_id'], $p['days']], array_slice($rows[0]['parts'], 0, 2)));
        $this->assertSame(['', [6]], [$rows[0]['parts'][2]['teacher_id'], $rows[0]['parts'][2]['days']]);   // Sat is left: a line is offered for it
        $this->assertSame(['', [1, 2, 3, 4, 5, 6]], [$rows[1]['parts'][0]['teacher_id'], $rows[1]['parts'][0]['days']]);   // period 2 is free
        $this->assertSame([2, [1]], [$rows[2]['parts'][0]['teacher_id'], $rows[2]['parts'][0]['days']]);

        // Saved untouched, the timetable is as it was.
        $before = $this->periods(1);
        $page->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame($before, $this->periods(1));
    }

    public function test_a_teacher_busy_in_another_class_stops_the_save(): void
    {
        $this->day();
        $this->period(4, 1, 1, 1, '09:00', '09:45', 2);   // Ravi is in Class 6 A on Monday in period 1
        $this->period(4, 1, 1, 1, '09:45', '10:30', 2);   // and in period 2

        $page = $this->form()->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.subject_id', 1);

        $this->assertSame('Busy with Class 6 A (Mon)', $page->instance()->getPartConflict(0, 0));
        $this->assertStringContainsString('Busy with Class 6 A (Mon)', $this->panel($page->html()));
        $page->call('onSaveTimetable')->assertSet('open', true);
        $this->assertSame([], $this->periods(1));

        // The period after the one he is busy in is not "busy" (they only touch)…
        $this->assertNull($page->set('scheduleRows.2.parts.0.teacher_id', 1)->instance()->getPartConflict(2, 0));
        $page->set('scheduleRows.2.parts.0.teacher_id', '');

        // …and on the days he is free, it saves.
        $page->set('scheduleRows.0.parts.0.days', ['2', '3'])->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['1 1 2 09:00-09:45', '1 1 3 09:00-09:45'], $this->periods(1));
    }

    // ───────────────────────── sections of the class ─────────────────────────

    public function test_a_section_ticked_gets_the_same_teacher_subject_and_time(): void
    {
        $this->day();
        $page = $this->form()->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.subject_id', 1)
            ->set('scheduleRows.0.parts.0.days', ['1', '2']);

        // B ticked: the teacher taking A and B together is not "busy", and both get the period.
        $page->set('scheduleRows.0.parts.0.sections', ['2']);
        $this->assertNull($page->instance()->getPartConflict(0, 0));
        $this->assertStringContainsString('A &amp; B', $this->panel($page->html()));
        $page->call('onSaveTimetable')->assertSet('open', false);

        $this->assertSame(['1 1 1 09:00-09:45', '1 1 2 09:00-09:45'], $this->periods(1));
        $this->assertSame(['1 1 1 09:00-09:45', '1 1 2 09:00-09:45'], $this->periods(2));
        $this->assertSame([], $this->periods(3));

        // Adding B's timetable, the period is there already — and it knows it is shared with A.
        $b = Livewire::test(TimeTable::class)->call('onCreateTimetable')->set('createStandardId', '1')->set('createSectionId', '2')->assertSet('isEdit', true);
        $this->assertSame([1, 1, [1], [1, 2]], [$b->get('scheduleRows.0.parts.0.teacher_id'), $b->get('scheduleRows.0.parts.0.subject_id'), $b->get('scheduleRows.0.parts.0.sections'), $b->get('scheduleRows.0.parts.0.days')]);
        $this->assertNull($b->instance()->getPartConflict(0, 0));
        $a = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1);
        $this->assertSame([2], $a->get('scheduleRows.0.parts.0.sections'));

        // A change is carried to the other section: Sonu, and Maths.
        $a->set('scheduleRows.0.parts.0.teacher_id', 2)->set('scheduleRows.0.parts.0.subject_id', 2)->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['2 2 1 09:00-09:45', '2 2 2 09:00-09:45'], $this->periods(1));
        $this->assertSame(['2 2 1 09:00-09:45', '2 2 2 09:00-09:45'], $this->periods(2));

        // The line for the days left has its own sections: Wed–Sat with Ravi, in C too.
        Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)
            ->set('scheduleRows.0.parts.1.teacher_id', 1)->set('scheduleRows.0.parts.1.subject_id', 1)->set('scheduleRows.0.parts.1.sections', ['3'])
            ->call('onSaveTimetable')->assertSet('open', false);
        $this->assertCount(6, $this->periods(1));
        $this->assertSame(['2 2 1 09:00-09:45', '2 2 2 09:00-09:45'], $this->periods(2));
        $this->assertSame(['1 1 3 09:00-09:45', '1 1 4 09:00-09:45', '1 1 5 09:00-09:45', '1 1 6 09:00-09:45'], $this->periods(3));

        // B taken off the first line: B loses the period; A and C keep theirs.
        Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->set('scheduleRows.0.parts.0.sections', [])
            ->call('onSaveTimetable')->assertSet('open', false);
        $this->assertCount(6, $this->periods(1));
        $this->assertSame([], $this->periods(2));
        $this->assertCount(4, $this->periods(3));
    }

    public function test_a_section_that_already_has_something_at_that_time_is_not_overwritten(): void
    {
        $this->day();
        $this->period(2, 2, 2, 1);   // B has Maths with Sonu on Monday in period 1

        $page = $this->form()->set('scheduleRows.0.parts.0.teacher_id', 1)->set('scheduleRows.0.parts.0.subject_id', 1)
            ->set('scheduleRows.0.parts.0.days', ['1'])->set('scheduleRows.0.parts.0.sections', ['2']);

        $this->assertSame('B already has Maths at this time (Mon)', $page->instance()->getShareConflict(0));
        $this->assertStringContainsString('B already has Maths at this time (Mon)', $this->panel($page->html()));

        $page->call('onSaveTimetable')->assertSet('open', true);
        $this->assertSame([], $this->periods(1));
        $this->assertSame(['2 2 1 09:00-09:45'], $this->periods(2));

        // On a day B is free, it goes through.
        $page->set('scheduleRows.0.parts.0.days', ['2'])->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['1 1 2 09:00-09:45'], $this->periods(1));
        $this->assertSame(['1 1 2 09:00-09:45', '2 2 1 09:00-09:45'], $this->periods(2));
    }

    // ───────────────────────── timetables made before the periods ─────────────────────────

    public function test_a_time_that_is_not_a_period_is_kept_and_can_be_taken_off(): void
    {
        $this->day();
        $this->period(1, 1, 1, 1);                         // period 1
        $this->period(1, 2, 2, 2, '14:00', '14:40');       // a time of its own

        $page = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1);
        $rows = $page->get('scheduleRows');
        $this->assertSame([1, 2, 3, null], array_column($rows, 'period'));
        $this->assertSame(['14:00', '14:40', 2, [2]], [$rows[3]['start_time'], $rows[3]['end_time'], $rows[3]['parts'][0]['teacher_id'], $rows[3]['parts'][0]['days']]);
        $this->assertStringContainsString("14:00 – 14:40 · not one of the school's periods", html_entity_decode($this->panel($page->html()), ENT_QUOTES));

        // Saved untouched, it stays.
        $page->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['1 1 1 09:00-09:45', '2 2 2 14:00-14:40'], $this->periods(1));

        // Taken off, it goes; a period cannot be taken off.
        Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->call('removeRow', 0)->call('removeRow', 3)
            ->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['1 1 1 09:00-09:45'], $this->periods(1));
    }

    public function test_without_periods_the_form_asks_for_them_first(): void
    {
        $page = $this->form();
        $this->assertSame([], $page->get('scheduleRows'));
        $panel = $this->panel($page->html());
        $this->assertStringContainsString('periods are not added yet', $panel);
        $this->assertStringContainsString('wire:click="goAddPeriods"', $panel);

        $page->call('onSaveTimetable')->assertSet('open', true);

        // Over to the Periods tab, its panel open.
        $page->call('goAddPeriods')->assertSet('open', false)->assertSet('viewMode', 'periods')->assertSet('showPeriodPanel', true);

        // A timetable made before the periods is still shown and edited, by its times.
        $this->period(1, 1, 1, 1, '08:00', '08:40');
        $old = Livewire::test(TimeTable::class)->call('onEditSection', 1, 1)->assertSet('open', true);
        $this->assertSame([null], array_column($old->get('scheduleRows'), 'period'));
        $old->set('scheduleRows.0.parts.0.teacher_id', 2)->call('onSaveTimetable')->assertSet('open', false);
        $this->assertSame(['2 1 1 08:00-08:40'], $this->periods(1));
    }

    // ───────────────────────── the cards ─────────────────────────

    public function test_the_timetable_is_shown_as_a_flat_list_of_periods(): void
    {
        $this->day();
        foreach ([1, 2, 3] as $day) $this->period(1, 1, 1, $day);
        foreach ([4, 5] as $day) $this->period(1, 2, 2, $day);
        $this->period(1, 2, 2, 4, '11:00', '11:45');

        $html = Livewire::test(TimeTable::class)->set('filterClass', '1')->set('filterSection', '1')->html();
        $card = substr($html, strpos($html, 'Class 5 · A</h3>'));
        $text = $this->text($card);

        $this->assertStringContainsString('2 periods', $text);
        $this->assertStringContainsString('# Time Subject Teacher Days', $text);
        // Period 1 with its two lines, then the lunch break where it falls, then period 3.
        $this->assertStringContainsString('1 09:00 AM – 09:45 AM Hindi Ravi Mon, Tue, Wed Maths Sonu Thu, Fri 10:30 AM – 11:00 AM Lunch break 3 11:00 AM – 11:45 AM Maths Sonu Thu', $text);

        // A flat list: no table, no tags, no initials in circles. Edit and print are on the card.
        foreach (['<table', 'bg-indigo-50', 'bg-teal-100', 'rounded-full', 'bg-blue-50'] as $class) {
            $this->assertStringNotContainsString($class, $card);
        }
        $this->assertStringContainsString('wire:click="onEditSection(1, 1)"', $html);
        $this->assertStringContainsString('title="Print"', $html);

        // A teacher's own periods: no lunch line, the class beside each.
        $teacher = Livewire::test(TimeTable::class)->call('setViewMode', 'teacher')->set('filterTeacher', '2')->html();
        $text = $this->text(substr($teacher, strpos($teacher, 'Class 5 · A</h3>')));
        $this->assertStringContainsString('1 09:00 AM – 09:45 AM Maths Sonu Class 5 · A Thu, Fri 3 11:00 AM – 11:45 AM Maths Sonu Class 5 · A Thu', $text);
        $this->assertStringNotContainsString('Lunch break', $text);
    }

    public function test_a_school_with_no_periods_set_sees_its_timetable_as_before(): void
    {
        foreach ([1, 2, 3] as $day) $this->period(1, 1, 1, $day);
        $this->period(1, 2, 2, 4, '10:00', '10:45');

        $html = Livewire::test(TimeTable::class)->set('filterClass', '1')->set('filterSection', '1')->html();
        $text = $this->text(substr($html, strpos($html, 'Class 5 · A</h3>')));

        $this->assertStringContainsString('1 09:00 AM – 09:45 AM Hindi Ravi Mon, Tue, Wed 2 10:00 AM – 10:45 AM Maths Sonu Thu', $text);
        $this->assertStringNotContainsString('Lunch break', $text);
    }
}
