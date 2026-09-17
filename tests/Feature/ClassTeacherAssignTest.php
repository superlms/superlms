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
 * already a class teacher is not offered again, and can't be assigned twice.
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

        $page->openAssignPanel();
        $page->assignTeacherId = $bina->id;
        $page->assignStandardId = $six->id;
        $page->saveAssign();
        $this->assertSame(2, AssignTeacherStandard::count());
    }
}
