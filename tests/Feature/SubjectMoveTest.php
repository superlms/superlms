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
use Tests\TestCase;

/**
 * A subject edited from one class into another moves: it lists under the new
 * class and its sections only.
 */
class SubjectMoveTest extends TestCase
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
}
