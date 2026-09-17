<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminSyllabusController;
use App\Livewire\Admin\Assignments as AssignmentsPage;
use App\Livewire\Admin\Content as ContentPage;
use App\Livewire\Admin\Syllabus as SyllabusPage;
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
 * A class and section picked in the Syllabus filters (and the chapter and
 * topic forms, Content, Assignments and the app's curriculum list) offer that
 * section's own subjects — not the subjects of other classes an old save left
 * linked to the section.
 */
class SyllabusSubjectsTest extends TestCase
{
    private int $org = 6;
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->string('code')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('section_id');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('chapters', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('subject_id');
            $t->string('name');
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('topics', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('chapter_id');
            $t->string('topic_name');
            $t->timestamps();
        });

        // Class 5 (sections A, B) and class 9 (section Z).
        $five = Standard::create(['organization_id' => $this->org, 'name' => '5', 'order' => 1]);
        $nine = Standard::create(['organization_id' => $this->org, 'name' => '9', 'order' => 2]);
        $a = Section::create(['standard_id' => $five->id, 'organization_id' => $this->org, 'name' => 'A']);
        $b = Section::create(['standard_id' => $five->id, 'organization_id' => $this->org, 'name' => 'B']);
        $z = Section::create(['standard_id' => $nine->id, 'organization_id' => $this->org, 'name' => 'Z']);
        $this->ids = ['five' => $five->id, 'nine' => $nine->id, 'A' => $a->id, 'B' => $b->id, 'Z' => $z->id];

        $link = function (string $name, array $classes, array $sectionsByClass) {
            $s = Subject::create(['organization_id' => $this->org, 'name' => $name]);
            foreach ($classes as $class) {
                DB::table('standard_subjects')->insert(['standard_id' => $class, 'subject_id' => $s->id, 'organization_id' => $this->org]);
            }
            foreach ($sectionsByClass as [$section, $class]) {
                DB::table('section_subjects')->insert(['section_id' => $section, 'subject_id' => $s->id, 'standard_id' => $class, 'organization_id' => $this->org]);
            }
            $this->ids[$name] = $s->id;
        };
        $link('Hindi', [$five->id, $nine->id], [[$a->id, $five->id], [$b->id, $five->id], [$z->id, $nine->id]]);
        $link('English', [$five->id], [[$a->id, $five->id]]);
        // Linked to class 5's section B only, with no class-level row.
        $link('Computer', [], [[$b->id, $five->id]]);
        // A class 9 subject that was saved while class 5's sections A and B were
        // still ticked: those links carry class 9, not class 5.
        $link('Physics', [$nine->id], [[$z->id, $nine->id], [$a->id, $nine->id], [$b->id, $nine->id]]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    private function names($subjects): array
    {
        return collect($subjects)->map(fn ($s) => is_array($s) ? $s['name'] : $s->name)->sort()->values()->all();
    }

    public function test_the_filter_lists_only_the_sections_own_subjects(): void
    {
        $page = new SyllabusPage();
        $page->mount();

        $page->filterStandard = $this->ids['five'];
        $page->updatedFilterStandard();
        $this->assertSame(['Computer', 'English', 'Hindi'], $this->names($page->filterSubjectsList));

        $page->filterSection = $this->ids['A'];
        $page->updatedFilterSection();
        $this->assertSame(['English', 'Hindi'], $this->names($page->filterSubjectsList));

        $page->filterSection = $this->ids['B'];
        $page->updatedFilterSection();
        $this->assertSame(['Computer', 'Hindi'], $this->names($page->filterSubjectsList));

        $page->filterStandard = $this->ids['nine'];
        $page->updatedFilterStandard();
        $this->assertSame(['Hindi', 'Physics'], $this->names($page->filterSubjectsList));
    }

    public function test_the_chapter_and_topic_forms_list_the_same(): void
    {
        $page = new SyllabusPage();
        $page->mount();

        $page->chapterStandardId = (string) $this->ids['five'];
        $page->updatedChapterStandardId();
        $page->chapterSectionId = (string) $this->ids['A'];
        $page->updatedChapterSectionId();
        $this->assertSame(['English', 'Hindi'], $this->names($page->chapterSubjects));

        $page->topicStandardId = (string) $this->ids['five'];
        $page->updatedTopicStandardId();
        $page->topicSectionId = (string) $this->ids['B'];
        $page->updatedTopicSectionId();
        $this->assertSame(['Computer', 'Hindi'], $this->names($page->topicSubjects));
    }

    public function test_the_syllabus_view_and_a_reload_keep_to_the_section(): void
    {
        // As after a refresh: the filters come from the URL.
        $page = new SyllabusPage();
        $page->filterStandard = (string) $this->ids['five'];
        $page->filterSection = (string) $this->ids['A'];
        $page->mount();
        $this->assertSame(['A', 'B'], $page->filterSections->pluck('name')->all());
        $this->assertSame(['English', 'Hindi'], $this->names($page->filterSubjectsList));

        $page->filterSubject = (string) $this->ids['Physics'];
        $this->assertSame(0, $page->render()->getData()['subjects']->total());

        $page->filterSubject = (string) $this->ids['Hindi'];
        $this->assertSame(['Hindi'], $this->names($page->render()->getData()['subjects']->items()));
    }

    public function test_performance_keeps_the_sections_subjects_after_upload_marks(): void
    {
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('exam_name')->nullable();
            $t->boolean('is_published')->default(true);
            $t->date('start_date')->nullable();
            $t->integer('total_marks')->nullable();
            $t->timestamps();
        });
        Schema::create('exam_copies', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('exam_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->decimal('percentage', 5, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->string('full_name')->nullable();
            $t->string('roll_no')->nullable();
            $t->timestamps();
        });

        $page = new \App\Livewire\Admin\Performance();
        $page->mount();
        $page->filterStandard = (string) $this->ids['five'];
        $page->updated('filterStandard', $page->filterStandard);
        $page->filterSection = (string) $this->ids['A'];
        $page->updated('filterSection', $page->filterSection);
        $this->assertSame(['English', 'Hindi'], $this->names($page->subjects));

        // The Upload Marks panel borrows the lists; closing it gives them back.
        $page->openUploadMarks();
        $this->assertSame(['Computer', 'English', 'Hindi', 'Physics'], $this->names($page->subjects));
        $page->closeUploadModal();
        $this->assertSame(['English', 'Hindi'], $this->names($page->subjects));
        $this->assertSame(['A', 'B'], collect($page->sections)->pluck('name')->all());

        $page->showTab('performers');
        $page->showTab('subject');
        $this->assertSame(['English', 'Hindi'], $this->names($page->subjects));
    }

    public function test_content_assignments_and_the_app_list_the_same(): void
    {
        $content = new ContentPage();
        $content->mount();
        $content->filterStandard = $this->ids['five'];
        $content->updatedFilterStandard();
        $content->filterSection = $this->ids['A'];
        $content->updatedFilterSection();
        $this->assertSame(['English', 'Hindi'], $this->names($content->filterSubjects));

        $subjectsFor = new \ReflectionMethod(AssignmentsPage::class, 'subjectsFor');
        $this->assertSame(['Computer', 'Hindi'], $this->names($subjectsFor->invoke(new AssignmentsPage(), $this->ids['five'], $this->ids['B'])));

        $api = app(AdminSyllabusController::class)->subjects(Request::create('/', 'GET', [
            'standard_id' => $this->ids['five'], 'section_id' => $this->ids['A'],
        ]))->getData(true);
        $this->assertSame(['English', 'Hindi'], array_column($api['data']['subjects'], 'name'));
    }
}
