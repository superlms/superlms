<?php

namespace Tests\Feature;

use App\Livewire\Admin\Teacher as TeachersPage;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Teachers list's Class Teacher column: a class teacher of one section
 * reads "CLASS 12-A"; of two sections of a class, "CLASS 6 A & B".
 */
class TeacherListClassTeacherTest extends TestCase
{
    private int $org = 3;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->string('gender')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->string('employee_id')->nullable();
            $t->string('phone')->nullable();
            $t->date('date_of_joining')->nullable();
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

        // The page's header counts read it.
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);
    }

    private function classTeacher(string $name, Standard $class, array $sections): void
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@example.com', 'organization_id' => $this->org]);
        $teacher = TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org]);

        foreach ($sections as $section) {
            AssignTeacherStandard::create([
                'organization_id'   => $this->org,
                'teacher_detail_id' => $teacher->id,
                'standard_id'       => $class->id,
                'section_id'        => $section->id,
            ]);
        }
    }

    public function test_two_sections_of_a_class_share_one_line(): void
    {
        $six = Standard::create(['organization_id' => $this->org, 'name' => 'CLASS 6']);
        $twelve = Standard::create(['organization_id' => $this->org, 'name' => 'CLASS 12']);
        $sixA = Section::create(['standard_id' => $six->id, 'name' => 'A']);
        $sixB = Section::create(['standard_id' => $six->id, 'name' => 'B']);
        $twelveA = Section::create(['standard_id' => $twelve->id, 'name' => 'A']);

        $this->classTeacher('Nisha', $six, [$sixA, $sixB]);
        $this->classTeacher('Karua', $twelve, [$twelveA]);

        Livewire::test(TeachersPage::class)
            ->assertSee('CLASS 6 A & B')
            ->assertDontSee('CLASS 6-A')
            ->assertSee('CLASS 12-A');
    }
}
