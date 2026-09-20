<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\Teacher\StudentController;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A class teacher's Students screen sees, and touches, only the class they are
 * class teacher of — the admin Students module narrowed by restrict() and
 * mayTouch().
 */
class TeacherStudentsScopeTest extends TestCase
{
    private int $org = 7;
    private StudentController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_active')->default(true);
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
            $t->string('code')->nullable();
            $t->string('board')->nullable();
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->string('code')->nullable();
            $t->timestamps();
        });
        Schema::create('assign_teacher_standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('roll_no')->nullable();
            $t->string('phone')->nullable();
            $t->string('gender')->nullable();
            $t->string('email')->nullable();
            $t->boolean('transportation_required')->default(false);
            $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('route_name');
            $t->decimal('monthly_fee', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('transportation_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->timestamps();
        });

        // Class 5 has sections A (the teacher's) and B (not theirs).
        \DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5', 'order' => 5],
            ['id' => 2, 'organization_id' => $this->org, 'name' => 'Class 6', 'order' => 6],
        ]);
        \DB::table('sections')->insert([
            ['id' => 10, 'standard_id' => 1, 'organization_id' => $this->org, 'name' => 'A'],
            ['id' => 11, 'standard_id' => 1, 'organization_id' => $this->org, 'name' => 'B'],
            ['id' => 12, 'standard_id' => 2, 'organization_id' => $this->org, 'name' => 'A'],
        ]);

        $teacherUser = User::forceCreate([
            'name' => 'Meera', 'email' => 'meera@example.com', 'role' => 'teacher',
            'organization_id' => $this->org,
        ]);
        $teacher = TeacherDetail::forceCreate([
            'user_id' => $teacherUser->id, 'organization_id' => $this->org,
        ]);
        AssignTeacherStandard::forceCreate([
            'organization_id' => $this->org, 'teacher_detail_id' => $teacher->id,
            'standard_id' => 1, 'section_id' => 10,
        ]);

        $this->student('Own student', 1, 10);        // id 1 — theirs
        $this->student('Other section', 1, 11);      // id 2 — not theirs
        $this->student('Other class', 2, 12);        // id 3 — not theirs

        $this->actingAs($teacherUser);

        // The counts beside the roster run MySQL's YEAR(), which sqlite has
        // not; only the narrowing is under test here.
        $this->controller = new class (app(ResponseService::class)) extends StudentController {
            protected function stats(int $orgId): array
            {
                return ['total' => $this->restrict(StudentDetail::where('organization_id', $orgId))->count()];
            }
        };
    }

    private function student(string $name, int $standardId, int $sectionId): StudentDetail
    {
        $user = User::forceCreate([
            'name' => $name, 'email' => strtolower(str_replace(' ', '', $name)) . '@example.com',
            'role' => 'user', 'organization_id' => $this->org,
        ]);

        return StudentDetail::forceCreate([
            'user_id' => $user->id, 'organization_id' => $this->org,
            'standard_id' => $standardId, 'section_id' => $sectionId, 'full_name' => $name,
        ]);
    }

    private function body($response): array
    {
        return json_decode($response->getContent(), true)['data'] ?? [];
    }

    public function test_the_roster_is_only_the_class_they_are_class_teacher_of(): void
    {
        $data = $this->body($this->controller->index(new Request()));

        $this->assertSame(['Own student'], collect($data['students'])->pluck('full_name')->all());
        $this->assertSame(1, $data['stats']['total']);
    }

    public function test_classes_and_lookups_name_that_class_alone(): void
    {
        $classes = $this->body($this->controller->classes())['classes'];
        $this->assertCount(1, $classes);
        $this->assertSame(['standard_id' => 1, 'class' => 'Class 5', 'section_id' => 10, 'section' => 'A'], $classes[0]);

        $lookups = $this->body($this->controller->lookups(new Request()));
        $this->assertSame(['Class 5'], collect($lookups['classes'])->pluck('name')->all());
        $this->assertSame(['A'], collect($lookups['sections'])->pluck('name')->all());
    }

    public function test_a_student_of_another_class_is_not_theirs_to_open_or_delete(): void
    {
        $this->assertSame(404, $this->controller->show(2)->getStatusCode());
        $this->assertSame(404, $this->controller->destroy(3)->getStatusCode());

        // Their own opens.
        $this->assertSame(200, $this->controller->show(1)->getStatusCode());
    }

    public function test_adding_into_another_class_is_refused(): void
    {
        $request = Request::create('/teacher/students', 'POST', [
            'name' => 'New boy', 'email' => 'new@example.com', 'mobile' => '9876543210',
            'dob' => '2015-04-02', 'gender' => 'male',
            'standard_id' => 2, 'section_id' => 12,   // not this teacher's class
            'father_name' => 'His father',
        ]);

        $response = $this->controller->store($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('your own class', $response->getContent());
        $this->assertSame(3, StudentDetail::count());
    }
}
