<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AttendanceController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The teacher app's Mark Attendance list: a teacher who is class teacher of
 * several sections of one class gets them as one list — the section assigned
 * first leading, each section's students A to Z — and another class apart.
 */
class MarkAttendanceSectionsTest extends TestCase
{
    private int $org = 6;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->string('employee_id')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
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
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->string('full_name');
            $t->string('roll_no')->nullable();
            $t->string('admission_no')->nullable();
            $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date');
            $t->tinyInteger('status');
            $t->string('remarks')->nullable();
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->timestamps();
        });
    }

    private function student(string $name, int $standard, int $section, string $roll): void
    {
        $user = DB::table('users')->insertGetId(['name' => $name, 'role' => 'user', 'organization_id' => $this->org]);
        DB::table('student_details')->insert([
            'user_id' => $user, 'organization_id' => $this->org, 'standard_id' => $standard,
            'section_id' => $section, 'full_name' => $name, 'roll_no' => $roll,
        ]);
    }

    public function test_sections_of_one_class_come_as_one_list_first_assigned_first_names_a_to_z(): void
    {
        $five  = DB::table('standards')->insertGetId(['name' => '5']);
        $six   = DB::table('standards')->insertGetId(['name' => '6']);
        $a     = DB::table('sections')->insertGetId(['standard_id' => $five, 'name' => 'Section A']);
        $b     = DB::table('sections')->insertGetId(['standard_id' => $five, 'name' => 'Section B']);
        $sixA  = DB::table('sections')->insertGetId(['standard_id' => $six, 'name' => 'A']);

        $teacher = User::forceCreate(['name' => 'Meera', 'role' => 'teacher', 'organization_id' => $this->org]);
        $detail  = DB::table('teacher_details')->insertGetId(['user_id' => $teacher->id, 'organization_id' => $this->org]);

        // B was assigned first, then 6-A, then A.
        foreach ([[$five, $b], [$six, $sixA], [$five, $a]] as [$std, $sec]) {
            DB::table('assign_teacher_standards')->insert([
                'organization_id' => $this->org, 'teacher_detail_id' => $detail,
                'standard_id' => $std, 'section_id' => $sec,
            ]);
        }

        // Roll numbers out of name order, and a name in lower case.
        $this->student('Zoya', $five, $a, '1');
        $this->student('aarav', $five, $a, '2');
        $this->student('Yash', $five, $b, '1');
        $this->student('Bela', $five, $b, '2');
        $this->student('Kabir', $six, $sixA, '1');
        $this->student('Other Section', $five, 99, '1');

        Auth::setUser($teacher);
        $res     = app(AttendanceController::class)->getStudentsForAttendance(new Request(['date' => '2026-08-27']));
        $classes = $res->getData(true)['data']['classes'];

        $this->assertCount(2, $classes);

        // Class 5: section B (assigned first) then A, each A to Z.
        $this->assertSame('5 - Section B, Section A', $classes[0]['class_info']['class_display']);
        $this->assertSame(['Bela', 'Yash', 'aarav', 'Zoya'], array_column($classes[0]['students'], 'full_name'));
        $this->assertSame(['Section B', 'Section B', 'Section A', 'Section A'], array_column($classes[0]['students'], 'section_name'));

        // Class 6 on its own.
        $this->assertSame('6 - A', $classes[1]['class_info']['class_display']);
        $this->assertSame(['Kabir'], array_column($classes[1]['students'], 'full_name'));
    }
}
