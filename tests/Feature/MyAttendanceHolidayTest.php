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
 * A person's own attendance in the app (GET attendance/my): a holiday the
 * panel marked for a teacher (code 3) is a holiday there too, as the panel
 * shows it, not an absence. A student's codes read as they always have.
 */
class MyAttendanceHolidayTest extends TestCase
{
    private int $org = 5;

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
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        foreach (['teacher_attendances' => 'teacher_detail_id', 'student_attendances' => 'student_detail_id'] as $table => $fk) {
            Schema::create($table, function (Blueprint $t) use ($fk) {
                $t->id();
                $t->unsignedBigInteger($fk);
                $t->unsignedBigInteger('organization_id');
                $t->date('attendance_date');
                $t->tinyInteger('status');
                $t->timestamps();
            });
        }
    }

    /** Day-by-day statuses of August 2026 for this user. */
    private function days(User $user): array
    {
        Auth::setUser($user);
        $res = app(AttendanceController::class)->myAttendance(new Request(['month' => '2026-08']));

        return collect($res->getData(true)['data']['days'])->pluck('status', 'date')->all();
    }

    private function user(string $role): User
    {
        return User::forceCreate(['name' => $role, 'role' => $role, 'organization_id' => $this->org]);
    }

    public function test_a_teachers_holiday_from_the_panel_is_a_holiday_not_an_absence(): void
    {
        $teacher = $this->user('teacher');
        $detail  = DB::table('teacher_details')->insertGetId(['user_id' => $teacher->id, 'organization_id' => $this->org]);

        // Tue 25 Aug present, Wed 26 absent, Thu 27 holiday — as the panel saves them.
        foreach (['2026-08-25' => 1, '2026-08-26' => 0, '2026-08-27' => 3] as $date => $status) {
            DB::table('teacher_attendances')->insert([
                'teacher_detail_id' => $detail, 'organization_id' => $this->org,
                'attendance_date' => $date, 'status' => $status,
            ]);
        }

        $days = $this->days($teacher);
        $this->assertSame('present', $days['2026-08-25']);
        $this->assertSame('absent', $days['2026-08-26']);
        $this->assertSame('holiday', $days['2026-08-27']);
    }

    public function test_a_students_codes_read_as_before(): void
    {
        $student = $this->user('user');
        $detail  = DB::table('student_details')->insertGetId(['user_id' => $student->id, 'organization_id' => $this->org]);

        // 4 is the teacher app's holiday; 3 is left as it always read.
        foreach (['2026-08-25' => 4, '2026-08-26' => 3, '2026-08-27' => 1] as $date => $status) {
            DB::table('student_attendances')->insert([
                'student_detail_id' => $detail, 'organization_id' => $this->org,
                'attendance_date' => $date, 'status' => $status,
            ]);
        }

        $days = $this->days($student);
        $this->assertSame('holiday', $days['2026-08-25']);
        $this->assertSame('absent', $days['2026-08-26']);
        $this->assertSame('present', $days['2026-08-27']);
    }
}
