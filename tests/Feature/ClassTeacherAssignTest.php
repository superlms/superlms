<?php

namespace Tests\Feature;

use App\Livewire\Accounts\Attendance as AccountsAttendancePage;
use App\Livewire\Admin\Attendance as AdminAttendancePage;
use App\Models\Student\Standard;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Assign Class Teacher (admin and accounts Attendance): a teacher who is
 * already a class teacher is not offered again, and can't be assigned twice;
 * one teacher can hold several sections of a class, a section has one class
 * teacher, and the list shows each teacher's class, sections and active
 * students.
 */
class ClassTeacherAssignTest extends TestCase
{
    private int $org = 3;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('assign_teacher_standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->default(0);
            $t->timestamps();
        });

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);
    }

    private function teacher(string $name): TeacherDetail
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@example.com', 'organization_id' => $this->org]);

        return TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org]);
    }

    private function assign(TeacherDetail $t, Standard $class): AssignTeacherStandard
    {
        return AssignTeacherStandard::create(['organization_id' => $this->org, 'teacher_detail_id' => $t->id, 'standard_id' => $class->id, 'section_id' => 0]);
    }

    /** Names the assign panel's teacher dropdown offers. */
    private function offered($page): array
    {
        $page->mainTab = 'class_teachers';

        return $page->render()->getData()['assignTeachers']->map(fn ($t) => $t->user->name)->all();
    }

    public static function pages(): array
    {
        return [
            'admin'    => [AdminAttendancePage::class],
            'accounts' => [AccountsAttendancePage::class],
        ];
    }

    #[DataProvider('pages')]
    public function test_class_teachers_and_the_class_pickers_follow_the_standard_pages_order(string $pageClass): void
    {
        // Added out of order: the Standard page lists them 1, 2, 10.
        $ten = Standard::create(['organization_id' => $this->org, 'name' => '10', 'order' => 3]);
        $one = Standard::create(['organization_id' => $this->org, 'name' => '1', 'order' => 1]);
        $two = Standard::create(['organization_id' => $this->org, 'name' => '2', 'order' => 2]);
        $b = \App\Models\Student\Section::create(['standard_id' => $one->id, 'organization_id' => $this->org, 'name' => 'B']);

        $this->assign($this->teacher('Asha'), $ten);
        AssignTeacherStandard::create(['organization_id' => $this->org, 'teacher_detail_id' => $this->teacher('Bina')->id, 'standard_id' => $one->id, 'section_id' => $b->id]);
        $this->assign($this->teacher('Chetan'), $two);
        $this->assign($this->teacher('Deepa'), $one);

        $page = new $pageClass();
        $page->mount();
        $page->mainTab = 'class_teachers';
        $data = $page->render()->getData();

        $this->assertSame(['1', '2', '10'], $data['standards']->pluck('name')->all());
        $this->assertSame(
            ['Deepa · 1', 'Bina · 1', 'Chetan · 2', 'Asha · 10'],
            $data['assignments']->map(fn ($a) => $a->teacher->user->name . ' · ' . $a->standard->name)->all()
        );
    }

    #[DataProvider('pages')]
    public function test_an_assigned_teacher_is_not_offered_again(string $pageClass): void
    {
        $asha  = $this->teacher('Asha');
        $this->teacher('Bina');
        $this->assign($asha, Standard::create(['organization_id' => $this->org, 'name' => '5']));

        $page = new $pageClass();
        $page->mount();
        $page->openAssignPanel();

        $this->assertSame(['Bina'], $this->offered($page));
    }

    #[DataProvider('pages')]
    public function test_editing_an_assignment_still_offers_its_own_teacher(string $pageClass): void
    {
        $asha = $this->teacher('Asha');
        $bina = $this->teacher('Bina');
        $this->teacher('Chetan');
        $five = Standard::create(['organization_id' => $this->org, 'name' => '5']);
        $mine = $this->assign($asha, $five);
        $this->assign($bina, Standard::create(['organization_id' => $this->org, 'name' => '6']));

        $page = new $pageClass();
        $page->mount();
        $page->editAssign($mine->id);

        $this->assertSame(['Asha', 'Chetan'], $this->offered($page));
    }

    #[DataProvider('pages')]
    public function test_a_class_teacher_cannot_be_assigned_to_a_second_class(string $pageClass): void
    {
        $asha = $this->teacher('Asha');
        $this->assign($asha, Standard::create(['organization_id' => $this->org, 'name' => '5']));
        $six = Standard::create(['organization_id' => $this->org, 'name' => '6']);

        $page = new $pageClass();
        $page->mount();
        $page->openAssignPanel();
        $page->assignTeacherId = $asha->id;
        $page->assignStandardId = $six->id;
        $page->saveAssign();

        $this->assertSame(1, AssignTeacherStandard::count());
        $this->assertTrue($page->showAssignPanel);
    }

    #[DataProvider('pages')]
    public function test_an_assignment_can_be_moved_to_another_class_and_a_new_teacher_assigned(string $pageClass): void
    {
        $asha = $this->teacher('Asha');
        $bina = $this->teacher('Bina');
        $mine = $this->assign($asha, Standard::create(['organization_id' => $this->org, 'name' => '5']));
        $six = Standard::create(['organization_id' => $this->org, 'name' => '6']);

        $page = new $pageClass();
        $page->mount();
        $page->editAssign($mine->id);
        $page->assignStandardId = $six->id;
        $page->saveAssign();
        $this->assertSame($six->id, (int) $mine->fresh()->standard_id);

        // Six has its class teacher now; five is free again.
        $page->openAssignPanel();
        $page->assignTeacherId = $bina->id;
        $page->assignStandardId = $six->id;
        $page->saveAssign();
        $this->assertSame(1, AssignTeacherStandard::count());

        $page->assignStandardId = $mine->fresh()->standard_id === $six->id ? Standard::where('name', '5')->value('id') : $six->id;
        $page->saveAssign();
        $this->assertSame(2, AssignTeacherStandard::count());
    }

    /** What the page hands its view on the Class Teachers tab (with the assign panel as it stands). */
    private function data($page): array
    {
        $page->mainTab = 'class_teachers';

        return $page->render()->getData();
    }

    private function section(Standard $class, string $name): \App\Models\Student\Section
    {
        return \App\Models\Student\Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => $name]);
    }

    private function students(Standard $class, ?\App\Models\Student\Section $section, int $active, int $inactive = 0): void
    {
        foreach ([[$active, true], [$inactive, false]] as [$n, $on]) {
            for ($i = 0; $i < $n; $i++) {
                $user = User::forceCreate(['name' => 'S', 'organization_id' => $this->org, 'is_active' => $on]);
                \Illuminate\Support\Facades\DB::table('student_details')->insert([
                    'organization_id' => $this->org, 'user_id' => $user->id, 'standard_id' => $class->id, 'section_id' => $section?->id,
                ]);
            }
        }
    }

    #[DataProvider('pages')]
    public function test_a_teacher_can_hold_several_sections_of_one_class(string $pageClass): void
    {
        $asha = $this->teacher('Asha');
        $five = Standard::create(['organization_id' => $this->org, 'name' => '5']);
        $a = $this->section($five, 'Section A');
        $b = $this->section($five, 'Section B');
        $c = $this->section($five, 'Section C');
        $this->students($five, $a, 2, 1);   // one of A's three cannot log in: not counted
        $this->students($five, $b, 1);

        $page = new $pageClass();
        $page->mount();
        $page->openAssignPanel();
        $page->assignTeacherId = $asha->id;
        $page->assignStandardId = $five->id;

        // A class with sections needs one picked.
        $page->saveAssign();
        $this->assertSame(0, AssignTeacherStandard::count());

        // Ticked out of order: kept in the sections' own order, which is the
        // order the app lists their students in.
        $page->assignSectionIds = [(string) $b->id, (string) $a->id];
        $page->saveAssign();
        $this->assertSame([$a->id, $b->id], AssignTeacherStandard::orderBy('id')->pluck('section_id')->map(fn ($s) => (int) $s)->all());
        $this->assertFalse($page->showAssignPanel);

        // One row in the list: the class and its sections as text, the active
        // students of each by the section's last letter.
        $rows = $this->data($page)['ctRows'];
        $this->assertCount(1, $rows);
        $this->assertSame(['Asha', '5', 'Section A & B', 'A - 2 / B - 1'],
            [$rows[0]->teacher->user->name, $rows[0]->class, $rows[0]->sections, $rows[0]->students]);

        // Edit opens on both sections; C is added and A taken off, B's row left as it was.
        $bRow = AssignTeacherStandard::where('section_id', $b->id)->first();
        $page->editAssign($rows[0]->id);
        $this->assertEqualsCanonicalizing([(string) $a->id, (string) $b->id], $page->assignSectionIds);
        $page->assignSectionIds = [(string) $b->id, (string) $c->id];
        $page->saveAssign();
        $this->assertEqualsCanonicalizing([$b->id, $c->id], AssignTeacherStandard::pluck('section_id')->map(fn ($s) => (int) $s)->all());
        $this->assertNotNull(AssignTeacherStandard::find($bRow->id));
        $this->assertSame((int) $b->id, (int) $bRow->fresh()->section_id);

        // Remove takes the teacher off the class altogether.
        $page->confirmDeleteAssign($bRow->id);
        $page->executeDeleteAssign();
        $this->assertSame(0, AssignTeacherStandard::count());
    }

    #[DataProvider('pages')]
    public function test_one_section_shows_its_number_alone_and_a_class_without_sections_its_whole_strength(string $pageClass): void
    {
        $five = Standard::create(['organization_id' => $this->org, 'name' => '5', 'order' => 1]);
        $six  = Standard::create(['organization_id' => $this->org, 'name' => '6', 'order' => 2]);
        $a = $this->section($five, 'Section A');
        $this->section($five, 'Section B');
        $this->students($five, $a, 4);
        $this->students($six, null, 3, 2);

        AssignTeacherStandard::create(['organization_id' => $this->org, 'teacher_detail_id' => $this->teacher('Asha')->id, 'standard_id' => $five->id, 'section_id' => $a->id]);
        $this->assign($this->teacher('Bina'), $six);

        $page = new $pageClass();
        $page->mount();
        $rows = $this->data($page)['ctRows'];

        $this->assertSame([['5', 'Section A', '4'], ['6', '', '3']], $rows->map(fn ($r) => [$r->class, $r->sections, $r->students])->all());
    }

    #[DataProvider('pages')]
    public function test_the_form_offers_only_what_is_free_with_teachers_a_to_z(string $pageClass): void
    {
        $this->teacher('zara');          // lower case: still last
        $asha = $this->teacher('Asha');
        $this->teacher('Meera');
        $bina = $this->teacher('Bina');

        $five  = Standard::create(['organization_id' => $this->org, 'name' => '5', 'order' => 1]);   // one section, taken
        $six   = Standard::create(['organization_id' => $this->org, 'name' => '6', 'order' => 2]);   // two sections, one taken
        $seven = Standard::create(['organization_id' => $this->org, 'name' => '7', 'order' => 3]);   // no sections, free
        $eight = Standard::create(['organization_id' => $this->org, 'name' => '8', 'order' => 4]);   // no sections, taken
        $fiveA = $this->section($five, 'A');
        $sixA  = $this->section($six, 'A');
        $sixB  = $this->section($six, 'B');
        AssignTeacherStandard::create(['organization_id' => $this->org, 'teacher_detail_id' => $asha->id, 'standard_id' => $five->id, 'section_id' => $fiveA->id]);
        AssignTeacherStandard::create(['organization_id' => $this->org, 'teacher_detail_id' => $bina->id, 'standard_id' => $six->id, 'section_id' => $sixA->id]);
        $this->assign($this->teacher('Chetan'), $eight);

        $page = new $pageClass();
        $page->mount();
        $page->openAssignPanel();
        $data = $this->data($page);

        // Teachers not assigned yet, A to Z.
        $this->assertSame(['Meera', 'zara'], $data['assignTeachers']->map(fn ($t) => $t->user->name)->all());
        // 5 (its only section has a class teacher) and 8 are not offered.
        $this->assertSame(['6', '7'], $data['assignStandards']->pluck('name')->all());

        // Of 6, only the section still free.
        $page->assignStandardId = $six->id;
        $this->assertSame(['B'], $this->data($page)['assignSections']->pluck('name')->all());

        // A section someone holds is refused even if it is sent.
        $page->assignTeacherId = TeacherDetail::whereHas('user', fn ($q) => $q->where('name', 'Meera'))->value('id');
        $page->assignSectionIds = [(string) $sixA->id];
        $page->saveAssign();
        $this->assertSame(3, AssignTeacherStandard::count());

        $page->assignSectionIds = [(string) $sixB->id];
        $page->saveAssign();
        $this->assertSame(4, AssignTeacherStandard::count());

        // Editing Bina's 6-A: her own class and section are offered to her.
        $mine = AssignTeacherStandard::where('teacher_detail_id', $bina->id)->first();
        $page->editAssign($mine->id);
        $data = $this->data($page);
        $this->assertSame(['6', '7'], $data['assignStandards']->pluck('name')->all());
        $this->assertSame(['A'], $data['assignSections']->pluck('name')->all());
    }

    #[DataProvider('pages')]
    public function test_a_section_filter_still_shows_everything_that_teacher_holds(string $pageClass): void
    {
        $asha = $this->teacher('Asha');
        $five = Standard::create(['organization_id' => $this->org, 'name' => '5']);
        $a = $this->section($five, 'Section A');
        $b = $this->section($five, 'Section B');
        foreach ([$a, $b] as $sec) {
            AssignTeacherStandard::create(['organization_id' => $this->org, 'teacher_detail_id' => $asha->id, 'standard_id' => $five->id, 'section_id' => $sec->id]);
        }

        $page = new $pageClass();
        $page->mount();
        $page->ctFilterStandard = $five->id;
        $page->ctFilterSection = $b->id;
        $rows = $this->data($page)['ctRows'];

        $this->assertCount(1, $rows);
        $this->assertSame('Section A & B', $rows[0]->sections);
    }
}
