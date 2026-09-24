<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminAttendanceController;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\AppPushNotifier;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Attendance with v=2 follows the panel's rules — rows start
 * blank (Sunday on Holiday), a blank row saves nothing and clears what was
 * saved, only this school's people are written, an unmarked Sunday reads as a
 * holiday, By Month is the dates × teachers grid and a person's months are the
 * panel's month cards over April → March — while the builds already on phones,
 * which send no v, get exactly what they always did.
 */
class AdminAttendanceAppPanelTest extends TestCase
{
    private int $org = 4;
    private string $monday = '2026-08-24';
    private string $sunday = '2026-08-23';
    private object $pushes;
    private AdminAttendanceController $api;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 10:00:00');

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
            $t->string('full_name')->nullable();
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

        $this->api = app(AdminAttendanceController::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function teacher(string $name, ?int $org = null): int
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@t.test', 'organization_id' => $org ?? $this->org]);

        return TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $org ?? $this->org])->id;
    }

    private function student(string $name, int $section = 2): array
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@s.test', 'organization_id' => $this->org]);
        $s = StudentDetail::create(['user_id' => $user->id, 'organization_id' => $this->org, 'standard_id' => 1, 'section_id' => $section]);

        return ['id' => $s->id, 'user_id' => $user->id];
    }

    private function hit(string $method, array $params, string $verb = 'GET'): array
    {
        return $this->api->{$method}(Request::create('/', $verb, $params))->getData(true);
    }

    public function test_a_day_starts_blank_and_sunday_on_holiday_old_builds_on_present(): void
    {
        $this->teacher('Asha');

        $new = $this->hit('teacherMarkList', ['date' => $this->monday, 'v' => 2])['data'];
        $this->assertSame('', $new['rows'][0]['status']);
        $this->assertFalse($new['existing']);
        $this->assertSame('asha@t.test', $new['rows'][0]['email']);

        $sun = $this->hit('teacherMarkList', ['date' => $this->sunday, 'v' => 2])['data'];
        $this->assertSame('holiday', $sun['rows'][0]['status']);

        $old = $this->hit('teacherMarkList', ['date' => $this->monday])['data'];
        $this->assertSame('present', $old['rows'][0]['status']);
        $this->assertArrayNotHasKey('existing', $old);
    }

    public function test_saving_a_day_writes_only_what_is_marked_and_says_saved_then_updated(): void
    {
        $asha  = $this->teacher('Asha');
        $bina  = $this->teacher('Bina');
        $other = $this->teacher('Elsewhere', 99);

        // Nothing marked: nothing to save.
        $none = $this->api->submitTeacherAttendance(Request::create('/', 'POST', ['v' => 2, 'date' => $this->monday, 'marks' => [
            ['teacher_detail_id' => $asha, 'status' => ''],
        ]]));
        $this->assertSame(422, $none->getStatusCode());

        $first = $this->hit('submitTeacherAttendance', ['v' => 2, 'date' => $this->monday, 'marks' => [
            ['teacher_detail_id' => $asha, 'status' => 'absent', 'remark' => 'sick'],
            ['teacher_detail_id' => $bina, 'status' => ''],
            ['teacher_detail_id' => $other, 'status' => 'present'],
        ]], 'POST');
        $this->assertSame('Attendance successful', $first['data']['title']);
        $this->assertSame('Teacher attendance saved for 24 Aug 2026.', $first['message']);

        $rows = DB::table('teacher_attendances')->get();
        $this->assertCount(1, $rows, 'Bina left blank and another school\'s teacher are not written');
        $this->assertSame(0, (int) $rows[0]->status);
        $this->assertSame('sick', $rows[0]->remarks);

        // Clearing Asha's row takes her day back to unmarked.
        $second = $this->hit('submitTeacherAttendance', ['v' => 2, 'date' => $this->monday, 'marks' => [
            ['teacher_detail_id' => $asha, 'status' => ''],
            ['teacher_detail_id' => $bina, 'status' => 'present'],
        ]], 'POST');
        $this->assertSame('Attendance updated', $second['data']['title']);
        $this->assertSame([$bina], DB::table('teacher_attendances')->pluck('teacher_detail_id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_marking_the_day_a_holiday_saves_everyone_on_it(): void
    {
        $asha = $this->teacher('Asha');
        $bina = $this->teacher('Bina');

        $res = $this->hit('submitTeacherAttendance', ['v' => 2, 'holiday' => 1, 'date' => $this->monday, 'marks' => [
            ['teacher_detail_id' => $asha, 'status' => '', 'remark' => 'Diwali'],
            ['teacher_detail_id' => $bina, 'status' => 'present', 'remark' => 'Diwali'],
        ]], 'POST');

        $this->assertSame('Holiday marked', $res['data']['title']);
        $this->assertSame('24 Aug 2026 is now a holiday for all teachers.', $res['message']);
        $this->assertSame([3, 3], DB::table('teacher_attendances')->orderBy('id')->pluck('status')->map(fn ($v) => (int) $v)->all());
    }

    public function test_an_unmarked_sunday_reads_as_a_holiday_in_the_day_s_records(): void
    {
        $this->teacher('Asha');

        $sun = $this->hit('teacherByDate', ['date' => $this->sunday, 'v' => 2])['data'];
        $this->assertSame('holiday', $sun['rows'][0]['status']);
        $this->assertSame(1, $sun['stats']['holiday']);

        $old = $this->hit('teacherByDate', ['date' => $this->sunday])['data'];
        $this->assertSame('not_marked', $old['rows'][0]['status']);
    }

    public function test_by_month_is_the_dates_down_the_side_and_a_column_per_teacher(): void
    {
        $asha = $this->teacher('Asha');
        $this->teacher('Bina');
        DB::table('teacher_attendances')->insert([
            'teacher_detail_id' => $asha, 'organization_id' => $this->org, 'attendance_date' => '2026-09-01', 'status' => 1,
        ]);

        $grid = $this->hit('teacherMonthGrid', ['month' => '2026-09'])['data'];
        $this->assertSame('September 2026', $grid['title']);
        $this->assertCount(2, $grid['teachers']);
        $this->assertCount(30, $grid['rows']);

        $first = $grid['rows'][0];
        $this->assertSame('present', $first['cells'][(string) $asha]);
        // 6 Sep is a Sunday; 30 Sep is still to come.
        $this->assertSame('holiday', $grid['rows'][5]['cells'][(string) $asha]);
        $this->assertNull($grid['rows'][29]['cells'][(string) $asha]);
        $this->assertSame(1, $grid['teachers'][0]['totals']['present']);

        $one = $this->hit('teacherMonthGrid', ['month' => '2026-09', 'teacher_id' => $asha])['data'];
        $this->assertCount(1, $one['teachers']);
    }

    public function test_a_person_s_year_is_april_to_march_as_month_cards(): void
    {
        $asha = $this->teacher('Asha');
        DB::table('teacher_attendances')->insert([
            ['teacher_detail_id' => $asha, 'organization_id' => $this->org, 'attendance_date' => '2026-04-01', 'status' => 1],
            ['teacher_detail_id' => $asha, 'organization_id' => $this->org, 'attendance_date' => '2026-04-02', 'status' => 2],
        ]);

        $year = $this->hit('teacherCalendar', ['teacher_id' => $asha, 'year' => 2026, 'v' => 2])['data'];
        $this->assertSame('cards', $year['type']);
        $this->assertSame('Asha', $year['person']);
        $this->assertSame('Apr 2026 – Mar 2027', $year['title']);
        // April → today (24 Sep): six months, the rest still to come.
        $this->assertCount(6, $year['months']);
        $this->assertSame('2026-04', $year['months'][0]['key']);
        $this->assertSame(3, $year['months'][0]['lead'], '1 Apr 2026 is a Wednesday');
        $this->assertSame(1, $year['months'][0]['counts']['present']);
        $this->assertSame(1, $year['months'][0]['counts']['half_day']);

        $month = $this->hit('teacherCalendar', ['teacher_id' => $asha, 'month' => '2026-04', 'v' => 2])['data'];
        $this->assertCount(1, $month['months']);
        $this->assertSame('April 2026', $month['title']);

        // Without v the old monthly calendar comes back.
        $old = $this->hit('teacherCalendar', ['teacher_id' => $asha, 'month' => '2026-04'])['data'];
        $this->assertSame('monthly', $old['type']);
    }

    public function test_a_section_s_day_writes_only_its_students_against_their_own_accounts(): void
    {
        $dev    = $this->student('Dev');
        $esha   = $this->student('Esha');
        $away   = $this->student('Away', 3);

        $res = $this->hit('submitStudentAttendance', ['v' => 2, 'standard_id' => 1, 'section_id' => 2, 'date' => $this->monday, 'marks' => [
            ['student_detail_id' => $dev['id'], 'status' => 'present', 'user_id' => 12345],
            ['student_detail_id' => $esha['id'], 'status' => ''],
            ['student_detail_id' => $away['id'], 'status' => 'absent'],
        ]], 'POST');

        $this->assertSame('Student attendance saved for 24 Aug 2026.', $res['message']);
        $rows = DB::table('student_attendances')->get();
        $this->assertCount(1, $rows);
        $this->assertSame($dev['user_id'], (int) $rows[0]->user_id, 'the account comes from the school, not the request');
        $this->assertSame([$dev['user_id']], array_column($this->pushes->rows, 'user_id'));

        $list = $this->hit('studentMarkList', ['v' => 2, 'standard_id' => 1, 'section_id' => 2, 'date' => $this->monday])['data'];
        $this->assertTrue($list['existing']);
        $byName = collect($list['rows'])->keyBy('name');
        $this->assertSame('present', $byName['Dev']['status']);
        $this->assertSame('', $byName['Esha']['status']);
    }

    public function test_a_class_teacher_keeps_to_one_class_and_a_whole_class_is_saved(): void
    {
        $asha = $this->teacher('Asha');
        $one  = Standard::create(['organization_id' => $this->org, 'name' => 'Class 1', 'order' => 1]);
        $two  = Standard::create(['organization_id' => $this->org, 'name' => 'Class 2', 'order' => 2]);

        // No section: the whole class, kept as 0.
        $this->hit('saveClassTeacher', ['teacher_detail_id' => $asha, 'standard_id' => $one->id], 'POST');
        $this->assertSame(0, (int) AssignTeacherStandard::first()->section_id);

        $res = $this->api->saveClassTeacher(Request::create('/', 'POST', ['teacher_detail_id' => $asha, 'standard_id' => $two->id]));
        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('already a class teacher of Class 1', $res->getData(true)['message']);

        $list = $this->hit('classTeachers', [])['data'];
        $this->assertNull($list['assignments'][0]['section_id']);
        $this->assertSame([['id' => AssignTeacherStandard::first()->id, 'teacher_id' => $asha]], $list['taken']);
    }
}
