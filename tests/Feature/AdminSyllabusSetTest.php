<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminSyllabusController;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Syllabus reads and saves as the panel's does: a subject's
 * whole outline once a class and subject are picked, and the chapter and
 * topic managers saving their rows as one set — removed, renamed, added.
 */
class AdminSyllabusSetTest extends TestCase
{
    private int $org = 7;
    private array $ids = [];
    private object $push;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('section_id'); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('chapters', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('name'); $t->text('description')->nullable(); $t->integer('order')->default(0);
            $t->boolean('is_published')->default(true); $t->timestamps();
        });
        Schema::create('topics', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('chapter_id');
            $t->string('topic_name'); $t->integer('order')->default(0); $t->timestamps();
        });

        // Pushes are recorded, never sent.
        $this->push = new class {
            public array $calls = [];
            public function __call($name, $args) { $this->calls[] = $name; return $name === 'outlineSnapshot' || $name === 'outlineSnapshotOfChapter' ? ['snap'] : null; }
        };
        $this->app->instance(\App\Services\TeacherPushNotifier::class, $this->push);

        $five = Standard::create(['organization_id' => $this->org, 'name' => '5', 'order' => 1]);
        $a = Section::create(['standard_id' => $five->id, 'organization_id' => $this->org, 'name' => 'A']);
        $b = Section::create(['standard_id' => $five->id, 'organization_id' => $this->org, 'name' => 'B']);
        $maths = Subject::create(['organization_id' => $this->org, 'name' => 'Maths']);
        DB::table('standard_subjects')->insert(['standard_id' => $five->id, 'subject_id' => $maths->id, 'organization_id' => $this->org]);
        DB::table('section_subjects')->insert(['section_id' => $a->id, 'subject_id' => $maths->id, 'standard_id' => $five->id, 'organization_id' => $this->org]);
        $this->ids = ['five' => $five->id, 'A' => $a->id, 'B' => $b->id, 'maths' => $maths->id];

        $c2 = DB::table('chapters')->insertGetId(['organization_id' => $this->org, 'standard_id' => $five->id, 'subject_id' => $maths->id, 'name' => 'Fractions', 'order' => 2]);
        $c1 = DB::table('chapters')->insertGetId(['organization_id' => $this->org, 'standard_id' => $five->id, 'subject_id' => $maths->id, 'name' => 'Numbers', 'order' => 1]);
        DB::table('topics')->insert([
            ['organization_id' => $this->org, 'chapter_id' => $c1, 'topic_name' => 'Place value', 'order' => 2],
            ['organization_id' => $this->org, 'chapter_id' => $c1, 'topic_name' => 'Counting', 'order' => 1],
            ['organization_id' => $this->org, 'chapter_id' => $c2, 'topic_name' => 'Halves', 'order' => 1],
        ]);
        $this->ids += ['numbers' => $c1, 'fractions' => $c2];

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    private function api(): AdminSyllabusController
    {
        return app(AdminSyllabusController::class);
    }

    public function test_the_outline_is_the_subjects_chapters_in_order_with_topics_as_added(): void
    {
        $d = $this->api()->outline(new Request([
            'standard_id' => $this->ids['five'], 'section_id' => $this->ids['A'], 'subject_id' => $this->ids['maths'],
        ]))->getData(true)['data'];

        $this->assertSame('Maths', $d['subject']['name']);
        $this->assertSame(['Numbers', 'Fractions'], array_column($d['chapters'], 'name'));
        $this->assertSame(['Place value', 'Counting'], array_column($d['chapters'][0]['topics'], 'name'));
        $this->assertSame(2, $d['stats']['chapters']);
        $this->assertSame(3, $d['stats']['topics']);

        // Section B is not taught Maths.
        $none = $this->api()->outline(new Request([
            'standard_id' => $this->ids['five'], 'section_id' => $this->ids['B'], 'subject_id' => $this->ids['maths'],
        ]))->getData(true)['data'];
        $this->assertNull($none['subject']);
        $this->assertSame([], $none['chapters']);
    }

    public function test_the_chapter_manager_saves_its_rows_as_one_set(): void
    {
        $base = ['standard_id' => $this->ids['five'], 'section_id' => $this->ids['A'], 'subject_id' => $this->ids['maths']];

        $blank = $this->api()->saveChapterSet(new Request($base + ['rows' => [['id' => null, 'name' => ' ', 'order' => 3]]]));
        $this->assertSame(422, $blank->status());
        $this->assertSame('Chapter 1: Name is required.', $blank->getData(true)['message']);

        $ok = $this->api()->saveChapterSet(new Request($base + [
            'rows' => [
                ['id' => $this->ids['numbers'], 'name' => 'Whole Numbers', 'order' => 1],
                ['id' => null, 'name' => 'Decimals', 'order' => 2],
            ],
            'deleted_ids' => [$this->ids['fractions']],
        ]));
        $this->assertSame(200, $ok->status());
        $this->assertSame('Chapters saved! 1 added. 1 updated. 1 deleted.', $ok->getData(true)['message']);

        $this->assertSame(['Whole Numbers', 'Decimals'], DB::table('chapters')->orderBy('order')->pluck('name')->all());
        // The removed chapter's topics went with it.
        $this->assertSame(0, DB::table('topics')->where('chapter_id', $this->ids['fractions'])->count());
        $this->assertSame($this->ids['A'], (int) DB::table('chapters')->where('name', 'Decimals')->value('section_id'));
        $this->assertContains('outlineSaved', $this->push->calls);
    }

    public function test_the_topic_manager_saves_its_rows_as_one_set(): void
    {
        $counting = DB::table('topics')->where('topic_name', 'Counting')->value('id');
        $place    = DB::table('topics')->where('topic_name', 'Place value')->value('id');

        $empty = $this->api()->saveTopicSet(new Request(['chapter_id' => $this->ids['numbers'], 'rows' => []]));
        $this->assertSame(422, $empty->status());

        $ok = $this->api()->saveTopicSet(new Request([
            'chapter_id'  => $this->ids['numbers'],
            'rows'        => [
                ['id' => $counting, 'name' => 'Counting to 100', 'order' => 1],
                ['id' => null, 'name' => 'Rounding', 'order' => 2],
            ],
            'deleted_ids' => [$place],
        ]));
        $this->assertSame(200, $ok->status());
        $this->assertSame('Topics saved! 1 added. 1 updated. 1 deleted.', $ok->getData(true)['message']);
        $this->assertSame(
            ['Counting to 100', 'Rounding'],
            DB::table('topics')->where('chapter_id', $this->ids['numbers'])->orderBy('order')->pluck('topic_name')->all()
        );
    }
}
