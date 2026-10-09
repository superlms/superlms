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
 * subject in one row, Single / All subjects beside them, flat rows below — and
 * every save works as before (one subject; all subjects, blank titles skipped;
 * edit; the required fields).
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

        // Required fields still asked for.
        $page->call('onSave');
        $page->assertHasErrors(['title', 'standard_id', 'description', 'subject_id']);

        $page->set('standard_id', (string) $std)
            ->set('subject_id', (string) $subs['Maths'])
            ->set('title', 'Table of 7')
            ->set('description', 'Write it five times')
            ->call('onSave');

        $hw = DB::table('home_works')->first();
        $this->assertSame(['Table of 7', 'Write it five times', $std, 0, $subs['Maths']],
            [$hw->title, $hw->description, (int) $hw->standard_id, (int) $hw->section_id, (int) $hw->subject_id]);
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
        foreach (['Hindi', 'English', 'Maths'] as $name) {
            $this->assertStringContainsString('>' . $name . '</span>', $html);
        }

        $page->set('subjectHomeworks.' . $subs['Hindi'] . '.title', 'Read page 12')
            ->set('subjectHomeworks.' . $subs['English'] . '.title', 'Spellings')
            ->set('subjectHomeworks.' . $subs['English'] . '.description', 'Ten words')
            ->call('onSave');

        $rows = DB::table('home_works')->orderBy('subject_id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame([$subs['Hindi'], $subs['English']], $rows->pluck('subject_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('Ten words', $rows->firstWhere('subject_id', $subs['English'])->description);
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

        $page->set('title', 'New')->call('onSave');
        $this->assertSame('New', DB::table('home_works')->where('id', $id)->value('title'));
        $this->assertSame(1, DB::table('home_works')->count());
    }
}
