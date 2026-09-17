<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminAttendanceController;
use App\Livewire\Accounts\Attendance as AccountsAttendancePage;
use App\Livewire\Admin\Attendance as AdminAttendancePage;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\AppPushNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Marking a day that is already marked (admin and accounts Attendance, and
 * the app's admin screens): the saved statuses come back as they are — a
 * holiday the teacher app saved (code 4) included — and changing one person
 * rewrites only that person, never the rest of the day.
 */
class AttendanceKeepSavedTest extends TestCase
{
    private int $org = 4;
    private string $day = '2026-08-27';
    private object $pushes;

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
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->string('roll_no')->nullable();
            $t->timestamps();
        });
        foreach (['teacher_attendances' => 'teacher_detail_id', 'student_attendances' => 'student_detail_id'] as $table => $fk) {
            Schema::create($table, function (Blueprint $t) use ($fk, $table) {
                $t->id();
                $t->unsignedBigInteger($fk);
                if ($table === 'student_attendances') {
                    $t->unsignedBigInteger('user_id')->default(0);
                }
                $t->unsignedBigInteger('organization_id');
                $t->date('attendance_date');
                $t->integer('status');
                $t->string('remarks')->nullable();
                $t->unsignedBigInteger('marked_by')->nullable();
                $t->timestamps();
            });
        }

        $this->pushes = new class {
            public array $rows = [];
            public function attendanceMarked(array $rows): void { $this->rows = array_merge($this->rows, $rows); }
        };
        $this->app->instance(AppPushNotifier::class, $this->pushes);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    public static function pages(): array
    {
        return ['admin' => [AdminAttendancePage::class], 'accounts' => [AccountsAttendancePage::class]];
    }

    /** Three teachers saved present / absent / half day, stored an hour ago. */
    private function savedTeacherDay(): array
    {
        $ids = [];
        foreach (['Asha' => 1, 'Bina' => 0, 'Chetan' => 2] as $name => $status) {
            $user = User::create(['name' => $name, 'email' => strtolower($name) . '@t.test', 'organization_id' => $this->org]);
            $t = TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org]);
            DB::table('teacher_attendances')->insert([
                'teacher_detail_id' => $t->id, 'organization_id' => $this->org, 'attendance_date' => $this->day,
                'status' => $status, 'remarks' => $name === 'Bina' ? 'sick' : '', 'marked_by' => 1,
                'created_at' => now()->subHour(), 'updated_at' => now()->subHour(),
            ]);
            $ids[$name] = $t->id;
        }

        return $ids;
    }

    /** A section the teacher app marked: two on holiday (code 4), one present. */
    private function appMarkedStudentDay(): array
    {
        $ids = [];
        foreach (['Dev' => 4, 'Esha' => 4, 'Faiz' => 1] as $name => $status) {
            $user = User::create(['name' => $name, 'email' => strtolower($name) . '@s.test', 'organization_id' => $this->org]);
            $s = StudentDetail::create(['user_id' => $user->id, 'organization_id' => $this->org, 'standard_id' => 1, 'section_id' => 2]);
            DB::table('student_attendances')->insert([
                'student_detail_id' => $s->id, 'user_id' => $user->id, 'organization_id' => $this->org,
                'attendance_date' => $this->day, 'status' => $status, 'remarks' => $status === 4 ? 'Holiday' : '',
                'marked_by' => 1, 'created_at' => now()->subHour(), 'updated_at' => now()->subHour(),
            ]);
            $ids[$name] = ['id' => $s->id, 'user_id' => $user->id];
        }

        return $ids;
    }

    private function teacherRow(int $id): object
    {
        return DB::table('teacher_attendances')->where('teacher_detail_id', $id)->first();
    }

    private function studentRow(int $id): object
    {
        return DB::table('student_attendances')->where('student_detail_id', $id)->first();
    }

    #[DataProvider('pages')]
    public function test_editing_one_teacher_keeps_the_rest_of_a_marked_day(string $pageClass): void
    {
        $ids = $this->savedTeacherDay();
        $before = DB::table('teacher_attendances')->get()->keyBy('teacher_detail_id');

        $page = new $pageClass();
        $page->mount();
        $page->openTeacherMark();
        $page->tMarkDate = $this->day;
        $page->updatedTMarkDate();

        $this->assertTrue($page->teacherMarkExisting);
        $this->assertSame(['present', 'absent', 'half_day'], array_column(array_values($page->teacherMark), 'status'));
        $this->assertSame('sick', $page->teacherMark[$ids['Bina']]['remark']);

        // Only Bina changes, from absent to present.
        $page->teacherMark[$ids['Bina']]['status'] = 'present';
        $page->submitTeacherAttendance();

        $this->assertSame(1, (int) $this->teacherRow($ids['Bina'])->status);
        $this->assertNotEquals($before[$ids['Bina']]->updated_at, $this->teacherRow($ids['Bina'])->updated_at);
        foreach (['Asha', 'Chetan'] as $name) {
            $this->assertEquals((array) $before[$ids[$name]], (array) $this->teacherRow($ids[$name]), "{$name} was rewritten");
        }
        $this->assertSame(3, DB::table('teacher_attendances')->count());
    }

    #[DataProvider('pages')]
    public function test_a_holiday_the_teacher_app_saved_shows_as_holiday_and_is_kept(string $pageClass): void
    {
        $ids = $this->appMarkedStudentDay();
        $before = DB::table('student_attendances')->get()->keyBy('student_detail_id');

        $page = new $pageClass();
        $page->mount();
        $page->openStudentMark();
        $page->sMarkStandard = 1;
        $page->sMarkSection = 2;
        $page->sMarkDate = $this->day;
        $page->loadStudentMark();

        $this->assertTrue($page->studentMarkExisting);
        $this->assertSame('holiday', $page->studentMark[$ids['Dev']['id']]['status']);
        $this->assertSame('holiday', $page->studentMark[$ids['Esha']['id']]['status']);
        $this->assertSame('present', $page->studentMark[$ids['Faiz']['id']]['status']);

        // Faiz is changed to absent; the two holidays stay exactly as the app saved them.
        $page->studentMark[$ids['Faiz']['id']]['status'] = 'absent';
        $page->submitStudentAttendance();

        $this->assertSame(0, (int) $this->studentRow($ids['Faiz']['id'])->status);
        foreach (['Dev', 'Esha'] as $name) {
            $this->assertEquals((array) $before[$ids[$name]['id']], (array) $this->studentRow($ids[$name]['id']), "{$name} was rewritten");
        }
        // Only the changed student is notified.
        $this->assertSame([['user_id' => $ids['Faiz']['user_id'], 'status' => 0]], $this->pushes->rows);
    }

    public function test_the_app_admin_screens_keep_a_marked_day_too(): void
    {
        $api = app(AdminAttendanceController::class);
        $teachers = $this->savedTeacherDay();
        $students = $this->appMarkedStudentDay();
        $tBefore = DB::table('teacher_attendances')->get()->keyBy('teacher_detail_id');
        $sBefore = DB::table('student_attendances')->get()->keyBy('student_detail_id');

        // The list the app shows reads the app's holiday as a holiday.
        $list = $api->studentMarkList(Request::create('/', 'GET', ['standard_id' => 1, 'section_id' => 2, 'date' => $this->day]))->getData(true);
        $this->assertSame(['holiday', 'holiday', 'present'], array_column($list['data']['rows'], 'status'));

        // The app sends every row back; only Chetan and Faiz differ.
        $api->submitTeacherAttendance(Request::create('/', 'POST', ['date' => $this->day, 'marks' => [
            ['teacher_detail_id' => $teachers['Asha'], 'status' => 'present', 'remark' => ''],
            ['teacher_detail_id' => $teachers['Bina'], 'status' => 'absent', 'remark' => 'sick'],
            ['teacher_detail_id' => $teachers['Chetan'], 'status' => 'present', 'remark' => ''],
        ]]));
        $api->submitStudentAttendance(Request::create('/', 'POST', ['standard_id' => 1, 'section_id' => 2, 'date' => $this->day, 'marks' => [
            ['student_detail_id' => $students['Dev']['id'], 'user_id' => $students['Dev']['user_id'], 'status' => 'holiday', 'remark' => 'Holiday'],
            ['student_detail_id' => $students['Esha']['id'], 'user_id' => $students['Esha']['user_id'], 'status' => 'holiday', 'remark' => 'Holiday'],
            ['student_detail_id' => $students['Faiz']['id'], 'user_id' => $students['Faiz']['user_id'], 'status' => 'absent', 'remark' => ''],
        ]]));

        $this->assertSame(1, (int) $this->teacherRow($teachers['Chetan'])->status);
        $this->assertEquals((array) $tBefore[$teachers['Asha']], (array) $this->teacherRow($teachers['Asha']));
        $this->assertEquals((array) $tBefore[$teachers['Bina']], (array) $this->teacherRow($teachers['Bina']));

        $this->assertSame(0, (int) $this->studentRow($students['Faiz']['id'])->status);
        $this->assertEquals((array) $sBefore[$students['Dev']['id']], (array) $this->studentRow($students['Dev']['id']));
        $this->assertEquals((array) $sBefore[$students['Esha']['id']], (array) $this->studentRow($students['Esha']['id']));
        $this->assertSame([['user_id' => $students['Faiz']['user_id'], 'status' => 0]], $this->pushes->rows);
    }
}
