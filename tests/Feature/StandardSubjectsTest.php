<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStandardController;
use App\Livewire\Admin\Standard as StandardPage;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StandardSubject;
use App\Models\Student\Subject;
use App\Models\User;
use App\Support\SubjectMove;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Subjects on the admin Standard page (and the app's subject API): an added
 * subject lists under the section it was added to, and one edited from one
 * class into another moves — it lists under the new class and its sections
 * only.
 */
class StandardSubjectsTest extends TestCase
{
    private int $org = 9;

    protected function setUp(): void
    {
        parent::setUp();

        // Just the tables these screens touch, on the in-memory test database.
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('education_board')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->string('code')->nullable();
            $t->string('board')->nullable();
            $t->string('file_path')->nullable();
            $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->string('code')->nullable();
            $t->string('image')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->string('code')->nullable();
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->string('detail_image')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_mandatory')->default(true);
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
        foreach (['teacher_time_tables', 'teacher_assignments'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('subject_id');
                $t->unsignedBigInteger('standard_id');
                $t->timestamps();
            });
        }

        DB::table('organizations')->insert(['id' => $this->org, 'education_board' => 'UP']);

        // Rendering a Livewire component signs its snapshot.
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    /** @return array{0: Standard, 1: Section, 2: Section} */
    private function classWithSections(string $name): array
    {
        $class = Standard::create(['organization_id' => $this->org, 'name' => $name]);

        return [
            $class,
            Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'A']),
            Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'B']),
        ];
    }

    private function subjectIn(string $name, Standard $class, array $sections): Subject
    {
        $subject = Subject::create(['organization_id' => $this->org, 'name' => $name]);
        StandardSubject::create(['standard_id' => $class->id, 'subject_id' => $subject->id, 'organization_id' => $this->org]);
        foreach ($sections as $section) {
            SectionSubject::create([
                'section_id' => $section->id, 'subject_id' => $subject->id,
                'standard_id' => $class->id, 'organization_id' => $this->org,
            ]);
        }

        return $subject;
    }

    private function page(): StandardPage
    {
        $page = new StandardPage();
        $page->mount();

        return $page;
    }

    private function classesOf(Subject $subject): array
    {
        return StandardSubject::where('subject_id', $subject->id)->orderBy('standard_id')->pluck('standard_id')->all();
    }

    private function sectionsOf(Subject $subject): array
    {
        return SectionSubject::where('subject_id', $subject->id)->orderBy('section_id')->pluck('section_id')->all();
    }

    /** What the Subjects list shows for a section. */
    private function listed(StandardPage $page, Standard $class, Section $section): array
    {
        $page->filterSubjectStandard = $class->id;
        $page->filterSection = $section->id;

        return collect($page->getFilteredSubjectsProperty()->items())->pluck('name')->all();
    }

    public function test_a_subject_added_from_a_section_lists_there(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');

        Livewire::test(StandardPage::class)
            ->call('drillIntoSection', $oneA->id)
            ->call('openAddForm')
            ->set('addType', 'subject')
            ->assertSet('selectedSectionsForSubject', [$oneA->id])
            // Sent with every keystroke, so a quick Create saves what was typed.
            ->assertSeeHtml('wire:model.live="subjectName"')
            ->set('subjectName', 'Maths')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('openForm', false)
            ->assertSee('Maths');
    }

    public function test_a_section_ticked_under_another_class_is_not_saved(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two] = $this->classWithSections('Class 2');

        // Opened from class 1 section A, then switched to class 2 with no
        // section of its own ticked: before, it saved a subject no section
        // listed.
        Livewire::test(StandardPage::class)
            ->call('drillIntoSection', $oneA->id)
            ->call('openAddForm')
            ->set('addType', 'subject')
            ->set('subjectName', 'Maths')
            ->set('selectedStandardForSubject', $two->id)
            ->assertSet('selectedSectionsForSubject', [])
            ->call('save')
            ->assertHasErrors(['selectedSectionsForSubject']);

        $this->assertSame(0, Subject::count());

        // Nor can one be slipped in by hand.
        Livewire::test(StandardPage::class)
            ->call('openAddForm')
            ->set('addType', 'subject')
            ->set('subjectName', 'Maths')
            ->set('selectedStandardForSubject', $two->id)
            ->set('selectedSectionsForSubject', [$oneA->id])
            ->call('save')
            ->assertHasErrors(['selectedSectionsForSubject']);

        $this->assertSame(0, Subject::count());
    }

    public function test_the_subject_list_keeps_its_page(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        foreach (range(1, 11) as $n) {
            $this->subjectIn(sprintf('Subject %02d', $n), $one, [$oneA]);
        }

        Livewire::test(StandardPage::class)
            ->call('drillIntoSection', $oneA->id)
            ->set('perPage', 10)
            ->assertDontSee('Subject 11')
            ->call('gotoPage', 2)
            ->assertSee('Subject 11')
            // Any other request stays on page 2.
            ->call('closeViewModal')
            ->assertSee('Subject 11')
            // A new filter starts from page 1.
            ->set('filterStatus', 'active')
            ->assertDontSee('Subject 11');
    }

    public function test_editing_a_subject_into_another_class_moves_it_there(): void
    {
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA, $oneB]);
        $this->subjectIn('Hindi', $one, [$oneA]);

        $page = $this->page();
        $page->filterSubjectStandard = $one->id;
        $page->editSubject($maths->id);
        $this->assertSame([$oneA->id, $oneB->id], array_map('intval', $page->selectedSectionsForSubject));

        // Class 2 picked: class 1's ticked sections don't carry over.
        $page->selectedStandardForSubject = $two->id;
        $page->updated('selectedStandardForSubject');
        $this->assertSame([], $page->selectedSectionsForSubject);

        $page->selectedSectionsForSubject = [$twoA->id];
        $page->saveSubject();

        $this->assertSame([], $page->getErrorBag()->all());
        $this->assertSame([$two->id], $this->classesOf($maths));
        $this->assertSame([$twoA->id], $this->sectionsOf($maths));

        $this->assertSame(['Hindi'], $this->listed($page, $one, $oneA));
        $this->assertSame([], $this->listed($page, $one, $oneB));
        $this->assertSame(['Maths'], $this->listed($page, $two, $twoA));
    }

    public function test_a_subject_shared_with_other_classes_only_leaves_the_one_it_was_edited_in(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        [$three, $threeA] = $this->classWithSections('Class 3');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);
        StandardSubject::create(['standard_id' => $two->id, 'subject_id' => $maths->id, 'organization_id' => $this->org]);
        SectionSubject::create(['section_id' => $twoA->id, 'subject_id' => $maths->id, 'standard_id' => $two->id, 'organization_id' => $this->org]);

        // Opened while viewing class 2, moved to class 3.
        $page = $this->page();
        $page->filterSubjectStandard = $two->id;
        $page->editSubject($maths->id);
        $this->assertSame($two->id, (int) $page->selectedStandardForSubject);

        $page->selectedStandardForSubject = $three->id;
        $page->updated('selectedStandardForSubject');
        $page->selectedSectionsForSubject = [$threeA->id];
        $page->saveSubject();

        $this->assertSame([$one->id, $three->id], $this->classesOf($maths));
        $this->assertSame([$oneA->id, $threeA->id], $this->sectionsOf($maths));
    }

    public function test_editing_within_the_same_class_only_replaces_its_sections(): void
    {
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);

        $page = $this->page();
        $page->editSubject($maths->id);
        $page->selectedSectionsForSubject = [$oneB->id];
        $page->saveSubject();

        $this->assertSame([$one->id], $this->classesOf($maths));
        $this->assertSame([$oneB->id], $this->sectionsOf($maths));
    }

    public function test_a_subject_in_use_in_its_class_timetable_does_not_move(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);
        DB::table('teacher_time_tables')->insert(['subject_id' => $maths->id, 'standard_id' => $one->id]);

        $page = $this->page();
        $page->editSubject($maths->id);
        $page->selectedStandardForSubject = $two->id;
        $page->updated('selectedStandardForSubject');
        $page->selectedSectionsForSubject = [$twoA->id];
        $page->saveSubject();

        $this->assertStringContainsString('timetable or assignments of Class 1', $page->getErrorBag()->first('selectedStandardForSubject'));
        $this->assertSame([$one->id], $this->classesOf($maths));
        $this->assertSame([$oneA->id], $this->sectionsOf($maths));
        $this->assertNull(SubjectMove::blocker($maths->id, $two->id));
    }

    private function apiUpdate(Subject $subject, Standard $class, array $sections, array $extra = []): array
    {
        $request = Request::create('/api/v1/admin/subjects/' . $subject->id, 'POST', array_merge([
            'name' => $subject->name, 'code' => 'SUB', 'standard_id' => $class->id,
            'section_ids' => array_map(fn ($s) => $s->id, $sections),
        ], $extra));

        return app(AdminStandardController::class)->updateSubject($request, $subject->id)->getData(true);
    }

    public function test_the_app_moves_a_subject_to_another_class_too(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);

        $response = $this->apiUpdate($maths, $two, [$twoA]);

        $this->assertTrue($response['success'], json_encode($response));
        $this->assertSame([$two->id], $this->classesOf($maths));
        $this->assertSame([$twoA->id], $this->sectionsOf($maths));
    }

    public function test_the_app_editing_a_shared_subject_in_place_keeps_its_other_classes(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two, $twoA, $twoB] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);
        StandardSubject::create(['standard_id' => $two->id, 'subject_id' => $maths->id, 'organization_id' => $this->org]);
        SectionSubject::create(['section_id' => $twoA->id, 'subject_id' => $maths->id, 'standard_id' => $two->id, 'organization_id' => $this->org]);

        // The app sends every section the subject has, class 1's included.
        $response = $this->apiUpdate($maths, $two, [$oneA, $twoB]);

        $this->assertTrue($response['success'], json_encode($response));
        $this->assertSame([$one->id, $two->id], $this->classesOf($maths));
        $this->assertSame([$oneA->id, $twoB->id], $this->sectionsOf($maths));

        // Sections of no chosen class at all are refused.
        $response = $this->apiUpdate($maths, $two, [$oneA]);
        $this->assertFalse($response['success']);
        $this->assertSame('Please select sections of the selected class.', $response['message']);
    }

    public function test_the_app_moves_a_shared_subject_out_of_the_class_it_names(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        [$three, $threeA] = $this->classWithSections('Class 3');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);
        StandardSubject::create(['standard_id' => $two->id, 'subject_id' => $maths->id, 'organization_id' => $this->org]);
        SectionSubject::create(['section_id' => $twoA->id, 'subject_id' => $maths->id, 'standard_id' => $two->id, 'organization_id' => $this->org]);

        $response = $this->apiUpdate($maths, $three, [$threeA], ['from_standard_id' => $two->id]);

        $this->assertTrue($response['success'], json_encode($response));
        $this->assertSame([$one->id, $three->id], $this->classesOf($maths));
        $this->assertSame([$oneA->id, $threeA->id], $this->sectionsOf($maths));
    }

    public function test_a_section_lists_its_own_class_subjects_and_names_only_that_class_sections(): void
    {
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA, $oneB]);
        // Old data: class 1's Maths also linked under class 2's section A, and
        // class 2's Hindi linked under class 1's section A for class 2.
        SectionSubject::create(['section_id' => $twoA->id, 'subject_id' => $maths->id, 'standard_id' => $one->id, 'organization_id' => $this->org]);
        $hindi = $this->subjectIn('Hindi', $two, [$twoA]);
        SectionSubject::create(['section_id' => $oneA->id, 'subject_id' => $hindi->id, 'standard_id' => $two->id, 'organization_id' => $this->org]);

        $page = $this->page();
        $this->assertSame(['Maths'], $this->listed($page, $one, $oneA));
        $this->assertSame(['Hindi'], $this->listed($page, $two, $twoA));

        // The row names class 1's sections only, not class 2's A.
        $row = $this->listedRows($page, $one, $oneA)[0];
        $this->assertSame([$oneA->id, $oneB->id], $row->sections->pluck('id')->map(fn ($v) => (int) $v)->all());

        Livewire::test(StandardPage::class)
            ->call('drillIntoSection', $oneA->id)
            ->assertSee('Maths')
            ->assertDontSee('Hindi')
            ->assertSee('Class 1');
    }

    /** @return array<int, Subject> */
    private function listedRows(StandardPage $page, Standard $class, Section $section): array
    {
        $page->filterSubjectStandard = $class->id;
        $page->filterSection = $section->id;

        return $page->getFilteredSubjectsProperty()->items();
    }

    public function test_a_class_with_one_section_lists_its_subjects_in_one_step(): void
    {
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');
        $single = Standard::create(['organization_id' => $this->org, 'name' => 'Class 9']);
        $only = Section::create(['standard_id' => $single->id, 'organization_id' => $this->org, 'name' => 'A']);
        $this->subjectIn('Physics', $single, [$only]);

        Livewire::test(StandardPage::class)
            ->call('showTab', 'subject')
            ->set('filterSubjectStandard', $single->id)
            ->assertSet('filterSection', $only->id)
            ->assertSee('Physics')
            // Two sections: the admin picks one.
            ->set('filterSubjectStandard', $one->id)
            ->assertSet('filterSection', '')
            ->assertDontSee('Physics');
    }

    public function test_the_list_only_gives_way_to_the_spinner_for_a_new_list(): void
    {
        $html = Livewire::test(StandardPage::class)->html();

        // Plain wire:loading.flex — the form Livewire keeps hidden until a request.
        $this->assertMatchesRegularExpression('/wire:loading\.flex wire:target="[^"]*filterSection[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/wire:loading\.remove wire:target="[^"]*filterSubjectStandard[^"]*"/', $html);
        $this->assertStringNotContainsString('wire:loading.flex class', $html);
        $this->assertStringNotContainsString('<div wire:loading.remove>', $html);
    }

    public function test_the_clock_is_not_new_data_and_a_held_section_is_not_sent_again(): void
    {
        // Ask AI's "resets in N minutes" (on every panel page) made the
        // background check see a changed page each minute and refresh it all.
        $refresh = file_get_contents(resource_path('views/partials/auto-refresh.blade.php'));
        $this->assertStringContainsString("querySelectorAll('[data-ar-ignore]').forEach(function (el) { el.remove(); })", $refresh);

        // Every place the countdown is printed sits in a tag marked to be skipped.
        $ask = file_get_contents(resource_path('views/livewire/components/gemini-assistant.blade.php'));
        $found = 0;
        for ($at = strpos($ask, '{{ $resetsAt }}'); $at !== false; $at = strpos($ask, '{{ $resetsAt }}', $at + 1)) {
            $tagStart = strrpos(substr($ask, 0, $at), '<');
            $this->assertStringContainsString('data-ar-ignore', substr($ask, $tagStart, strpos($ask, '>', $tagStart) - $tagStart));
            $found++;
        }
        $this->assertGreaterThanOrEqual(3, $found);

        // The section the page itself picked is shown, not sent a second time.
        $sole = file_get_contents(resource_path('views/partials/sole-section.blade.php'));
        $this->assertStringContainsString('if (!heldAlready(el, only.value)) {', $sole);
    }

    public function test_viewing_a_subject_shows_its_own_class_mandatory_flag_and_sections(): void
    {
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);
        SectionSubject::create(['section_id' => $twoA->id, 'subject_id' => $maths->id, 'standard_id' => $one->id, 'organization_id' => $this->org]);
        $art = $this->subjectIn('Art', $one, [$oneA]);
        StandardSubject::where('subject_id', $art->id)->update(['is_mandatory' => false]);

        $page = $this->page();
        $page->onViewSubjectAdmin($maths->id);
        $this->assertSame('Yes', $page->viewData['is_mandatory']);
        $this->assertSame('Class 1', $page->viewData['class']);
        $this->assertSame('A', $page->viewData['sections']);

        $page->onViewSubjectAdmin($art->id);
        $this->assertSame('No', $page->viewData['is_mandatory']);

        $page->onViewSectionAdmin($oneA->id);
        $this->assertSame(2, $page->viewData['subjects_count']);
        $page->onViewSectionAdmin($twoA->id);
        $this->assertSame(0, $page->viewData['subjects_count']);
    }

    public function test_a_subject_saved_from_anywhere_is_shown_where_it_went(): void
    {
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');

        // Added from the Classes tab, no section being looked at.
        Livewire::test(StandardPage::class)
            ->call('openAddForm')
            ->set('addType', 'subject')
            ->set('subjectName', 'Maths')
            ->set('selectedStandardForSubject', $one->id)
            ->set('selectedSectionsForSubject', [$oneB->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'subject')
            ->assertSet('filterSubjectStandard', $one->id)
            ->assertSet('filterSection', $oneB->id)
            ->assertSee('Maths');

        // A section added from the Classes tab: its class's sections are shown.
        Livewire::test(StandardPage::class)
            ->call('openAddForm')
            ->set('addType', 'section')
            ->set('sectionName', 'C')
            ->set('selectedStandard', $one->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'section')
            ->assertSet('filterStandard', $one->id)
            ->assertSee('Class 1');
    }

    public function test_a_filled_field_drops_its_old_error(): void
    {
        [$one] = $this->classWithSections('Class 1');

        Livewire::test(StandardPage::class)
            ->call('openAddForm')->set('addType', 'subject')
            ->call('save')
            ->assertHasErrors(['subjectName', 'selectedStandardForSubject', 'selectedSectionsForSubject'])
            ->set('subjectName', 'Maths')
            ->assertHasNoErrors(['subjectName'])
            ->set('selectedStandardForSubject', $one->id)
            ->assertHasNoErrors(['selectedStandardForSubject'])
            ->assertHasErrors(['selectedSectionsForSubject']);
    }

    public function test_another_school_rows_cannot_be_reached(): void
    {
        $theirs = Standard::create(['organization_id' => 77, 'name' => 'Their Class']);
        $theirSection = Section::create(['standard_id' => $theirs->id, 'organization_id' => 77, 'name' => 'Z']);
        $theirSubject = Subject::create(['organization_id' => 77, 'name' => 'Their Maths']);
        StandardSubject::create(['standard_id' => $theirs->id, 'subject_id' => $theirSubject->id, 'organization_id' => 77]);
        SectionSubject::create(['section_id' => $theirSection->id, 'subject_id' => $theirSubject->id, 'standard_id' => $theirs->id, 'organization_id' => 77]);
        [$one, $oneA] = $this->classWithSections('Class 1');

        Livewire::test(StandardPage::class)
            ->call('drillIntoSection', $theirSection->id)
            ->assertSet('filterSection', '')
            ->call('editSubject', $theirSubject->id)
            ->assertSet('openForm', false)
            ->call('onViewSubjectAdmin', $theirSubject->id)
            ->assertSet('showViewModal', false)
            ->call('performDeleteSubject', $theirSubject->id)
            ->call('performDeleteSection', $theirSection->id)
            ->call('performDeleteStandard', $theirs->id)
            // Nor can a section be put under their class.
            ->call('openAddForm')->set('addType', 'section')
            ->set('sectionName', 'X')->set('selectedStandard', $theirs->id)
            ->call('save')
            ->assertHasErrors(['selectedStandard']);

        $this->assertNotNull(Subject::find($theirSubject->id));
        $this->assertNotNull(Section::find($theirSection->id));
        $this->assertNotNull(Standard::find($theirs->id));
        $this->assertSame(1, Section::where('standard_id', $theirs->id)->count());
    }

    public function test_a_section_with_students_keeps_its_class(): void
    {
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });
        [$one, $oneA, $oneB] = $this->classWithSections('Class 1');
        $two = Standard::create(['organization_id' => $this->org, 'name' => 'Class 2']);
        DB::table('student_details')->insert(['user_id' => 5, 'standard_id' => $one->id, 'section_id' => $oneA->id]);

        Livewire::test(StandardPage::class)
            ->call('editSection', $oneA->id)
            ->set('sectionName', 'A2')
            ->set('selectedStandard', $two->id)
            ->call('save')
            ->assertHasErrors(['selectedStandard']);
        $this->assertSame($one->id, (int) Section::find($oneA->id)->standard_id);

        // Renamed in its own class: fine. And one with no students may move.
        Livewire::test(StandardPage::class)
            ->call('editSection', $oneA->id)->set('sectionName', 'A2')->call('save')->assertHasNoErrors();
        Livewire::test(StandardPage::class)
            ->call('editSection', $oneB->id)->set('selectedStandard', $two->id)->call('save')->assertHasNoErrors();
        $this->assertSame('A2', Section::find($oneA->id)->name);
        $this->assertSame($two->id, (int) Section::find($oneB->id)->standard_id);
    }

    public function test_a_subject_does_not_move_onto_a_same_named_one_or_into_another_class_sections(): void
    {
        [$one, $oneA] = $this->classWithSections('Class 1');
        [$two, $twoA] = $this->classWithSections('Class 2');
        $maths = $this->subjectIn('Maths', $one, [$oneA]);
        $this->subjectIn('Maths', $two, [$twoA]);

        $page = $this->page();
        $page->editSubject($maths->id);
        $page->selectedStandardForSubject = $two->id;
        $page->selectedSectionsForSubject = [$twoA->id];
        $page->saveSubject();
        $this->assertSame('A subject with this name already exists in the selected class.', $page->getErrorBag()->first('subjectName'));

        $page->resetErrorBag();
        $page->selectedSectionsForSubject = [$oneA->id];
        $page->saveSubject();
        $this->assertSame('Please select sections of the selected class.', $page->getErrorBag()->first('selectedSectionsForSubject'));

        $this->assertSame([$one->id], $this->classesOf($maths));
        $this->assertSame([$oneA->id], $this->sectionsOf($maths));
    }

    /** No tabs and no filter band (2026-11-25): a class's arrow opens its sections, or its subjects when it has one. */
    public function test_cards_open_sections_or_straight_to_subjects(): void
    {
        [$two, $twoA] = $this->classWithSections('Class 2');
        $one = Standard::create(['organization_id' => $this->org, 'name' => 'Class 1']);
        $oneA = Section::create(['standard_id' => $one->id, 'organization_id' => $this->org, 'name' => 'A']);

        $page = Livewire::test(StandardPage::class);
        $html = $page->html();
        $this->assertStringNotContainsString("showTab('section')", $html);   // no tabs
        $this->assertStringNotContainsString('Filter by', $html);            // no filter band
        // The card (2026-11-26): code and students small on the right, no Order; the
        // class teacher, then the subjects; a line, then round buttons.
        $this->assertStringContainsString('class="sc-meta"', $html);
        $this->assertStringNotContainsString('class="sc-figs"', $html);
        $this->assertStringNotContainsString('>Order<', $html);
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Code — 0 students Class teacher: Not assigned', $text);
        $this->assertStringContainsString('Class teacher: Not assigned Subjects: 0', $text);
        $this->assertStringContainsString('class="sc-btn sc-view"', $html);
        $this->assertStringContainsString('class="sc-btn sc-go"', $html);
        $this->assertStringContainsString('wire:click.stop="openClass(' . $two->id . ')"', $html);
        // The page is "Classes" (2026-11-27), the header only that name — no trail;
        // every card button in the arrow's light teal, none blue / amber / rose.
        $this->assertStringContainsString('>Classes</h1>', $html);
        $this->assertStringNotContainsString('Academic Structure', $html);
        $this->assertStringNotContainsString('std-trail', $html);
        $this->assertStringContainsString('background: #e8f6f2; color: #0f766e;', $html);
        $this->assertStringNotContainsString('.sc-del  { background: #f43f5e; }', $html);
        $this->assertSame('Classes', collect(config('menu.admin'))->firstWhere('link', 'admin.standard')['title']);

        $page->call('openClass', $two->id)->assertSet('activeTab', 'section')->assertSet('filterStandard', $two->id)
            ->assertSeeHtml('wire:click.stop="drillIntoSection(' . $twoA->id . ')"');
        $page->call('drillIntoSection', $twoA->id)->assertSet('activeTab', 'subject');
        $page->call('goBack')->assertSet('activeTab', 'section');            // two sections: back to them
        $page->call('goBack')->assertSet('activeTab', 'standard');

        $page->call('openClass', $one->id)->assertSet('activeTab', 'subject')->assertSet('filterSection', $oneA->id);
        $page->call('goBack')->assertSet('activeTab', 'standard');            // one section: back to the classes
    }
}
