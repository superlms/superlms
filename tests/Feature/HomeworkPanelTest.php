<?php

namespace Tests\Feature;

use App\Livewire\Admin\Homework;
use App\Models\Admin\HomeWork as HomeWorkModel;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Add / Edit Homework in the Mark Attendance panel's look: class, section and
 * subject in one row, Single / All subjects beside them, flat rows below only
 * once the class (and, for one subject, the subject) is picked, no Description
 * when adding — and every save works as before (one subject; all subjects,
 * blank titles skipped; edit, an old description kept). Homework Status reads
 * as Mark Attendance does: flat rows, plain text, not done in red.
 */
class HomeworkPanelTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('h', 32))]);
        Storage::fake('s3');
        HomeWorkModel::flushEventListeners();                       // no pushes from a test

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('roll_no')->nullable(); $t->timestamps();
        });
        Schema::create('home_work_completions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('home_work_id'); $t->unsignedBigInteger('user_id'); $t->timestamps();
        });
        Schema::create('home_works', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->default(0);
            $t->unsignedBigInteger('standard_id')->default(0); $t->unsignedBigInteger('section_id')->default(0);
            $t->unsignedBigInteger('subject_id')->default(0); $t->string('title')->nullable(); $t->text('description')->nullable();
            $t->string('file')->nullable(); $t->timestamps();
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    private function klass(): array
    {
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 5']);
        DB::table('sections')->insert([['standard_id' => $std, 'name' => 'A'], ['standard_id' => $std, 'name' => 'B']]);
        $subs = [];
        foreach (['Hindi', 'English', 'Maths'] as $name) {
            $subs[$name] = DB::table('subjects')->insertGetId(['organization_id' => $this->org, 'name' => $name]);
            DB::table('standard_subjects')->insert(['standard_id' => $std, 'subject_id' => $subs[$name]]);
        }

        return [$std, $subs];
    }

    public function test_panel_look_and_single_subject_save(): void
    {
        [$std, $subs] = $this->klass();
        $page = Livewire::test(Homework::class)->call('onAddHomework');
        $html = $page->html();

        $this->assertStringContainsString('absolute top-0 right-0 bottom-0 w-full max-w-3xl', $html);
        $this->assertStringContainsString('Select class…', $html);
        $this->assertStringContainsString('Single subject', $html);
        $this->assertStringContainsString('All subjects', $html);

        // Nothing to fill until the class and the subject are picked.
        $this->assertStringContainsString('Select a class and a subject to write the homework.', $html);
        $this->assertStringNotContainsString('wire:model.defer="title"', $html);
        $this->assertStringNotContainsString('wire:model.defer="title"', $page->set('standard_id', (string) $std)->html());

        // Required fields still asked for — Description is not.
        $page->set('standard_id', '')->call('onSave');
        $page->assertHasErrors(['title', 'standard_id', 'subject_id']);
        $page->assertHasNoErrors(['description']);

        $page->set('standard_id', (string) $std)->set('subject_id', (string) $subs['Maths']);
        $html = $page->html();
        $this->assertStringContainsString('wire:model.defer="title"', $html);
        $this->assertStringNotContainsString('wire:model.defer="description"', $html);   // no Description when adding

        $page->set('title', 'Table of 7')->call('onSave');

        $hw = DB::table('home_works')->first();
        $this->assertSame(['Table of 7', $std, 0, $subs['Maths']],
            [$hw->title, (int) $hw->standard_id, (int) $hw->section_id, (int) $hw->subject_id]);
        $this->assertSame('', (string) $hw->description);
        $this->assertFalse($page->get('open'));
    }

    public function test_all_subjects_skips_blank_titles(): void
    {
        [$std, $subs] = $this->klass();
        $page = Livewire::test(Homework::class)->call('onAddHomework')
            ->set('subject_selection', 'all')
            ->set('standard_id', (string) $std);

        $html = $page->html();
        $this->assertStringContainsString('a subject left without a title is skipped', $html);
        $this->assertStringNotContainsString('.description"', $html);                      // no Description boxes
        foreach (['Hindi', 'English', 'Maths'] as $name) {
            $this->assertStringContainsString('>' . $name . '</span>', $html);
        }

        $page->set('subjectHomeworks.' . $subs['Hindi'] . '.title', 'Read page 12')
            ->set('subjectHomeworks.' . $subs['English'] . '.title', 'Spellings')
            ->call('onSave');

        $rows = DB::table('home_works')->orderBy('subject_id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame([$subs['Hindi'], $subs['English']], $rows->pluck('subject_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('Spellings', $rows->firstWhere('subject_id', $subs['English'])->title);
    }

    public function test_edit_keeps_working(): void
    {
        [$std, $subs] = $this->klass();
        $id = DB::table('home_works')->insertGetId(['organization_id' => $this->org, 'user_id' => 1, 'standard_id' => $std,
            'section_id' => 0, 'subject_id' => $subs['Hindi'], 'title' => 'Old', 'description' => 'Old text', 'created_at' => now(), 'updated_at' => now()]);

        $page = Livewire::test(Homework::class)->call('onEditHomework', $id);
        $html = $page->html();
        $this->assertStringContainsString('Edit Homework', $html);
        $this->assertStringNotContainsString('All subjects', $html);                 // editing is one homework
        $this->assertSame((string) $subs['Hindi'], (string) $page->get('subject_id'));
        $this->assertStringContainsString('wire:model.defer="description"', $html);       // an old description stays editable

        $page->set('title', 'New')->call('onSave');
        $this->assertSame('New', DB::table('home_works')->where('id', $id)->value('title'));
        $this->assertSame('Old text', DB::table('home_works')->where('id', $id)->value('description'));
        $this->assertSame(1, DB::table('home_works')->count());
    }

    public function test_status_reads_as_mark_attendance(): void
    {
        [$std, $subs] = $this->klass();
        $sec = DB::table('sections')->where('standard_id', $std)->where('name', 'A')->value('id');
        $asha = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'user_id' => 501, 'standard_id' => $std, 'section_id' => $sec, 'full_name' => 'Asha', 'roll_no' => '1']);
        DB::table('student_details')->insert(['organization_id' => $this->org, 'user_id' => 502, 'standard_id' => $std, 'section_id' => $sec, 'full_name' => 'Bilal', 'roll_no' => '2']);
        $hindi = DB::table('home_works')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'subject_id' => $subs['Hindi'], 'title' => 'Read', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('home_works')->insert(['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'subject_id' => $subs['Maths'], 'title' => 'Sums', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('home_work_completions')->insert(['home_work_id' => $hindi, 'user_id' => 501]);

        $page = Livewire::test(Homework::class)->call('switchTab', 'status')
            ->set('hwStatusStandard', (string) $std)->set('hwStatusSection', (string) $sec);
        $html = $page->html();

        $this->assertStringContainsString('Every student in this section', $html);
        $this->assertStringContainsString('Students <strong>2</strong>', $html);
        $this->assertStringNotContainsString('bg-gradient-to-r', $html);                  // a plain strip
        $this->assertStringNotContainsString('rounded-full border bg-', $html);           // no chips
        $this->assertMatchesRegularExpression('/text-red-600 font-medium">\s*1 \/ 2\s*<\/span>/', $html);   // Asha: 1 of 2
        $this->assertMatchesRegularExpression('/class="font-semibold text-gray-800">Hindi/', $html);  // done: plain, a bit bold
        $this->assertMatchesRegularExpression('/class="font-semibold text-red-600">Maths/', $html);   // not done: red
        $this->assertStringContainsString('inline-block w-1.5 h-1.5 mx-1 rounded-full bg-gray-400', $html); // a thicker dot between

        // One student: day by day.
        $html = $page->set('hwStatusStudent', (string) $asha)->html();
        $this->assertStringContainsString('Day by day', $html);
    }

    /** Section A with all three subjects mapped to it; returns [std, subs, secA, secB]. */
    private function klassWithSections(): array
    {
        [$std, $subs] = $this->klass();
        $secA = DB::table('sections')->where('standard_id', $std)->where('name', 'A')->value('id');
        $secB = DB::table('sections')->where('standard_id', $std)->where('name', 'B')->value('id');
        foreach ([$secA, $secB] as $sec) {
            foreach ($subs as $id) {
                DB::table('section_subjects')->insert(['standard_id' => $std, 'section_id' => $sec, 'subject_id' => $id]);
            }
        }

        return [$std, $subs, $secA, $secB];
    }

    public function test_add_brings_up_todays_homework_for_one_subject(): void
    {
        [$std, $subs, $secA, $secB] = $this->klassWithSections();
        $maths = DB::table('home_works')->insertGetId(['organization_id' => $this->org, 'user_id' => 77, 'standard_id' => $std, 'section_id' => $secA,
            'subject_id' => $subs['Maths'], 'title' => 'Sums', 'description' => 'Page 4', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('home_works')->insert(['organization_id' => $this->org, 'user_id' => 77, 'standard_id' => $std, 'section_id' => $secA,
            'subject_id' => $subs['Hindi'], 'title' => 'Yesterday', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

        $page = Livewire::test(Homework::class)->call('onAddHomework')
            ->set('standard_id', (string) $std)->set('section_id', (string) $secA)->set('subject_id', (string) $subs['Maths']);
        $this->assertSame($maths, (int) $page->get('matchedId'));
        $this->assertSame('Sums', $page->get('title'));
        $html = $page->html();
        $this->assertStringContainsString('Already set today for this class', $html);
        $this->assertStringContainsString('Update Homework', $html);
        $this->assertStringContainsString('wire:model.defer="description"', $html);

        // Another subject: nothing set today, the form empties again.
        $page->set('subject_id', (string) $subs['English']);
        $this->assertNull($page->get('matchedId'));
        $this->assertSame('', $page->get('title'));
        $page->set('subject_id', (string) $subs['Hindi']);                         // yesterday's doesn't count
        $this->assertNull($page->get('matchedId'));
        $page->set('subject_id', (string) $subs['Maths'])->set('section_id', (string) $secB)->set('subject_id', (string) $subs['Maths']);
        $this->assertNull($page->get('matchedId'));                               // the same class, another section

        // Back to section A: updating changes that homework, no second copy, setter kept.
        $page->set('section_id', (string) $secA)->set('subject_id', (string) $subs['Maths'])
            ->set('title', 'Sums 1-10')->call('onSave');
        $this->assertSame(2, DB::table('home_works')->count());
        $row = DB::table('home_works')->where('id', $maths)->first();
        $this->assertSame(['Sums 1-10', 'Page 4', 77], [$row->title, $row->description, (int) $row->user_id]);
        $this->assertFalse($page->get('open'));
    }

    public function test_add_all_subjects_fills_in_todays_and_updates_them(): void
    {
        [$std, $subs, $secA, $secB] = $this->klassWithSections();
        $hindi = DB::table('home_works')->insertGetId(['organization_id' => $this->org, 'user_id' => 77, 'standard_id' => $std, 'section_id' => $secA,
            'subject_id' => $subs['Hindi'], 'title' => 'Read p1', 'created_at' => now(), 'updated_at' => now()]);

        $open = fn () => Livewire::test(Homework::class)->call('onAddHomework')
            ->set('subject_selection', 'all')->set('standard_id', (string) $std)->set('section_id', (string) $secA);

        $page = $open();
        $this->assertSame('Read p1', $page->get('subjectHomeworks.' . $subs['Hindi'] . '.title'));
        $this->assertSame('', $page->get('subjectHomeworks.' . $subs['English'] . '.title'));
        $html = $page->html();
        $this->assertSame(1, substr_count($html, 'Set today · updates'));
        $this->assertStringContainsString('Save Homework', $html);

        // Another section lets go of it.
        $page->set('section_id', (string) $secB);
        $this->assertSame('', $page->get('subjectHomeworks.' . $subs['Hindi'] . '.title'));
        $this->assertSame([], $page->get('todaysHomework'));

        // Adding English leaves Hindi as one row, unchanged.
        $open()->set('subjectHomeworks.' . $subs['English'] . '.title', 'Spellings')->call('onSave');
        $this->assertSame(2, DB::table('home_works')->count());
        $this->assertSame(1, DB::table('home_works')->where('subject_id', $subs['Hindi'])->count());

        // Opened again, both come up; Hindi changed in place.
        $page = $open();
        $this->assertSame('Spellings', $page->get('subjectHomeworks.' . $subs['English'] . '.title'));
        $page->set('subjectHomeworks.' . $subs['Hindi'] . '.title', 'Read p2')->call('onSave');
        $this->assertSame(2, DB::table('home_works')->count());
        $this->assertSame('Read p2', DB::table('home_works')->where('id', $hindi)->value('title'));
        $this->assertSame(77, (int) DB::table('home_works')->where('id', $hindi)->value('user_id'));
    }

    public function test_list_has_no_assigned_column(): void
    {
        [$std, $subs, $secA] = $this->klassWithSections();
        DB::table('home_works')->insert(['organization_id' => $this->org, 'user_id' => 1, 'standard_id' => $std, 'section_id' => $secA,
            'subject_id' => $subs['Maths'], 'title' => 'Sums', 'created_at' => now(), 'updated_at' => now()]);

        $html = Livewire::test(Homework::class)->set('filterStandard', (string) $std)->set('filterSection', (string) $secA)->html();
        $this->assertStringContainsString('<p class="text-sm font-semibold text-gray-600">Sums</p>', $html);
        $this->assertStringNotContainsString('>Assigned</th>', $html);
        // The class, its section under it in small plain text; the homework column a little narrower.
        $this->assertMatchesRegularExpression('/<p class="text-sm text-gray-700">Class 5<\/p>\s*(<!--\[if BLOCK\]><!\[endif\]-->)?\s*<p class="text-xs text-gray-400">A<\/p>/', $html);
        $this->assertStringContainsString('<div class="max-w-md">', $html);
        $this->assertStringNotContainsString(now()->format('d M Y, h:i A'), $html);
    }

    public function test_both_tabs_open_on_today(): void
    {
        [$std, $subs] = $this->klass();
        $sec   = DB::table('sections')->where('standard_id', $std)->where('name', 'A')->value('id');
        $today = now()->toDateString();

        $page = Livewire::test(Homework::class);
        $this->assertSame($today, $page->get('filterDate'));
        $this->assertSame($today, $page->get('hwStatusDate'));
        $this->assertStringNotContainsString('wire:click="clearFilters"', $page->html());   // today alone: nothing to clear

        // Class + section is now enough to list today's homework.
        DB::table('home_works')->insert(['organization_id' => $this->org, 'user_id' => 1, 'standard_id' => $std, 'section_id' => $sec,
            'subject_id' => $subs['Maths'], 'title' => 'Sums today', 'created_at' => now(), 'updated_at' => now()]);
        $html = $page->set('filterStandard', (string) $std)->set('filterSection', (string) $sec)->html();
        $this->assertStringContainsString('Sums today', $html);
        $this->assertStringContainsString('p-1.5 text-red-600 hover:bg-red-50 rounded-lg', $html);   // Students-style actions
        $this->assertStringNotContainsString('rounded bg-gray-100 text-gray-700">Maths', $html);     // subject as plain text

        // × still empties the date; Clear brings back today.
        $page->set('filterDate', '');
        $this->assertStringContainsString('Pick a date, a class and a section', $page->html());
        $page->call('clearFilters');
        $this->assertSame($today, $page->get('filterDate'));
        $page->set('hwStatusDate', '')->call('clearStatusFilters');
        $this->assertSame($today, $page->get('hwStatusDate'));

        // A date in the URL still wins.
        $this->assertSame('2026-01-05', Livewire::withQueryParams(['filterDate' => '2026-01-05'])->test(Homework::class)->get('filterDate'));
    }
}
