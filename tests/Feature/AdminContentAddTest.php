<?php

namespace Tests\Feature;

use App\Livewire\Admin\Content;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Content → Add Content: a chapter of the class and subject picked (or a new
 * one), a type, the content — no topic name, no chapter name to type for a
 * chapter that is there. Adding keeps what the chapter already holds.
 */
class AdminContentAddTest extends TestCase
{
    private int $org = 7;
    private int $std;
    private int $subject;
    private int $ch1;
    private int $ch2;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
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
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('chapters', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->default(0); $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('user_id')->nullable(); $t->string('name')->nullable(); $t->text('description')->nullable();
            $t->string('image_path')->nullable(); $t->string('pdf_path')->nullable(); $t->string('file_path')->nullable();
            $t->integer('order')->default(0); $t->boolean('is_published')->default(true); $t->timestamps();
        });
        Schema::create('topics', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('chapter_id');
            $t->string('topic_name')->nullable(); $t->text('topic_content')->nullable(); $t->integer('order')->default(0);
            $t->string('image_path')->nullable(); $t->string('pdf_path')->nullable(); $t->string('link')->nullable(); $t->timestamps();
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));

        $this->std     = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'X', 'order' => 10]);
        $this->subject = DB::table('subjects')->insertGetId(['organization_id' => $this->org, 'name' => 'Maths']);
        DB::table('standard_subjects')->insert(['standard_id' => $this->std, 'subject_id' => $this->subject]);
        $base = ['organization_id' => $this->org, 'standard_id' => $this->std, 'subject_id' => $this->subject];
        $this->ch1 = DB::table('chapters')->insertGetId($base + ['name' => 'Real Numbers', 'order' => 1, 'pdf_path' => 'https://cdn.test/notes.pdf']);
        $this->ch2 = DB::table('chapters')->insertGetId($base + ['name' => 'Polynomials', 'order' => 2]);
    }

    private function page()
    {
        return Livewire::test(Content::class)
            ->set('filterStandard', (string) $this->std)
            ->set('filterSubject', (string) $this->subject);
    }

    public function test_add_content_asks_a_chapter_and_a_type_but_no_name_and_keeps_what_is_there(): void
    {
        $page = $this->page()->call('openNewContent');

        // Opens on the subject's first chapter; no topic or chapter name to type.
        $this->assertSame((string) $this->ch1, $page->get('contentChapterId'));
        $html = $page->html();
        $this->assertStringContainsString('Add Content', $html);
        $this->assertStringContainsString('+ New chapter', $html);
        $this->assertStringNotContainsString('Topic Name', $html);
        $this->assertStringNotContainsString('Chapter Name', $html);
        $this->assertStringNotContainsString('Add Topic', $html);
        $this->assertStringContainsString('Already in this chapter: PDF', $html);

        $page->set('contentType', 'text')->set('contentText', 'Euclid’s lemma, step by step.')->call('onSaveContent')
            ->assertHasNoErrors()->assertSet('openContentModal', false);

        $ch = DB::table('chapters')->find($this->ch1);
        $this->assertSame('Euclid’s lemma, step by step.', $ch->description);
        $this->assertSame('https://cdn.test/notes.pdf', $ch->pdf_path);   // the PDF it had stays
        $this->assertSame('Real Numbers', $ch->name);
        $this->assertSame(0, DB::table('topics')->count());
    }

    public function test_a_chapters_add_content_picks_it_and_nothing_to_add_is_refused(): void
    {
        $page = $this->page()->call('openNewContent', $this->ch2);
        $this->assertSame((string) $this->ch2, $page->get('contentChapterId'));

        $page->set('contentType', 'url')->call('onSaveContent')->assertHasErrors(['contentType']);
        $this->assertNull(DB::table('chapters')->find($this->ch2)->file_path);

        $page->set('contentUrl', 'https://youtu.be/abc')->call('onSaveContent')->assertHasNoErrors();
        $this->assertSame('https://youtu.be/abc', DB::table('chapters')->find($this->ch2)->file_path);
    }

    public function test_a_new_chapter_takes_its_number_and_name_with_the_content(): void
    {
        $page = $this->page()->call('openNewContent')->set('contentChapterId', 'new');
        $this->assertSame('3', $page->get('contentOrder'));
        $this->assertStringContainsString('Chapter Name', $page->html());

        $page->set('contentType', 'text')->set('contentText', 'Lines and angles')->call('onSaveContent')
            ->assertHasErrors(['contentName']);

        $page->set('contentName', 'Lines')->call('onSaveContent')->assertHasNoErrors();
        $new = DB::table('chapters')->where('name', 'Lines')->first();
        $this->assertSame(3, (int) $new->order);
        $this->assertSame('Lines and angles', $new->description);
        $this->assertSame($this->subject, (int) $new->subject_id);
    }

    public function test_a_topics_add_content_asks_no_name_and_edit_still_renames(): void
    {
        $topic = DB::table('topics')->insertGetId(['organization_id' => $this->org, 'chapter_id' => $this->ch1, 'topic_name' => 'Euclid']);

        $page = $this->page()->call('onAddContent', 'topic', $topic);
        $this->assertStringNotContainsString('Topic Name', $page->html());
        $page->set('contentType', 'text')->set('contentText', 'a = bq + r')->call('onSaveContent')->assertHasNoErrors();
        $this->assertSame('a = bq + r', DB::table('topics')->find($topic)->topic_content);
        $this->assertSame('Euclid', DB::table('topics')->find($topic)->topic_name);

        $page->call('onEditContent', 'topic', $topic);
        $this->assertStringContainsString('Topic Name', $page->html());
        $page->set('contentName', "Euclid's Lemma")->call('onSaveContent')->assertHasNoErrors();
        $this->assertSame("Euclid's Lemma", DB::table('topics')->find($topic)->topic_name);
        $this->assertSame('a = bq + r', DB::table('topics')->find($topic)->topic_content);
    }

    public function test_with_no_chapter_yet_it_starts_on_a_new_one(): void
    {
        DB::table('chapters')->delete();

        $page = $this->page()->call('openNewContent');
        $this->assertSame('new', $page->get('contentChapterId'));
        $this->assertSame('1', $page->get('contentOrder'));
    }
}
