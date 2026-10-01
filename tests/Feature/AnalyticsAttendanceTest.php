<?php

namespace Tests\Feature;

use App\Livewire\Admin\Analytics;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Analytics page's attendance figures: a month's (and a class's) average
 * is the average of its days' percentages; the Student Split is today alone;
 * "vs yesterday" waits for today to be marked; and the Admissions Trend draws
 * the last fifteen admission dates.
 */
class AnalyticsAttendanceTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 11:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->date('date_of_admission')->nullable(); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->date('attendance_date'); $t->integer('status'); $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('teacher_detail_id');
            $t->date('attendance_date'); $t->integer('status'); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->decimal('amount', 10, 2)->default(0); $t->date('payment_date')->nullable(); $t->timestamps();
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Run one of the page's loaders on a bare component and hand back the component. */
    private function load(string $method, ?Analytics $page = null): Analytics
    {
        $page ??= new Analytics();
        (fn () => $this->{$method}())->call($page);

        return $page;
    }

    private function klass(string $name, int $students): array
    {
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => $name]);
        $ids = [];
        for ($i = 0; $i < $students; $i++) {
            $ids[] = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'created_at' => '2026-04-10 09:00:00']);
        }

        return $ids;
    }

    /** Mark the first $present of the students present and the next $absent absent on a day. */
    private function mark(array $students, string $date, int $present, int $absent, int $holiday = 0): void
    {
        foreach (array_values($students) as $i => $id) {
            $status = $i < $present ? 1 : ($i < $present + $absent ? 0 : ($i < $present + $absent + $holiday ? 3 : null));
            if ($status === null) {
                continue;
            }
            DB::table('student_attendances')->insert(['organization_id' => $this->org, 'student_detail_id' => $id, 'attendance_date' => $date, 'status' => $status]);
        }
    }

    public function test_a_months_figure_is_the_average_of_its_days_percentages(): void
    {
        $a = $this->klass('Class 5', 25);
        // 68% on the 1st, 70% on the 2nd, 50% on the 3rd — and a holiday on the 4th.
        $this->mark($a, '2026-09-01', 17, 8);
        $this->mark($a, '2026-09-02', 7, 3);
        $this->mark($a, '2026-09-03', 5, 5);
        $this->mark($a, '2026-09-04', 0, 0, 25);
        // Teachers: 100% and 50% → 75%.
        foreach ([['2026-09-01', [1, 1]], ['2026-09-02', [1, 0]]] as [$date, $statuses]) {
            foreach ($statuses as $i => $st) {
                DB::table('teacher_attendances')->insert(['organization_id' => $this->org, 'teacher_detail_id' => $i + 1, 'attendance_date' => $date, 'status' => $st]);
            }
        }
        // Another school's marks are not this one's.
        DB::table('student_attendances')->insert(['organization_id' => 99, 'student_detail_id' => 1, 'attendance_date' => '2026-09-01', 'status' => 0]);

        $data = $this->load('loadMonthlyAttendancePct')->monthlyAttendancePct;

        // April → October of the running session, nothing after this month.
        $this->assertSame(['Apr 2026', 'May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026'], $data['labels']);

        $sep = array_search('Sep 2026', $data['labels']);
        // (68 + 70 + 50) / 3 — not 29 present out of 45 marks (64.4).
        $this->assertSame(62.7, $data['student'][$sep]);
        $this->assertSame(3, $data['studentDays'][$sep]);
        $this->assertSame(75.0, $data['teacher'][$sep]);
        // A month nobody was marked in has no bar.
        $this->assertNull($data['student'][0]);
        $this->assertNull($data['teacher'][array_search('Oct 2026', $data['labels'])]);
    }

    public function test_the_class_ranking_is_counted_the_same_way(): void
    {
        $a = $this->klass('Class 5', 25);
        $b = $this->klass('Class 6', 10);
        $this->klass('Class 7', 4);                  // students, but never marked
        DB::table('standards')->insert(['organization_id' => $this->org, 'name' => 'Class 8']); // no students

        $this->mark($a, '2026-09-01', 17, 8);
        $this->mark($a, '2026-09-02', 7, 3);
        $this->mark($a, '2026-09-03', 5, 5);
        $this->mark($a, '2026-09-04', 0, 0, 25);     // a holiday is not a day of 0%
        $this->mark($b, '2026-09-01', 10, 0);

        $rank = $this->load('loadClassAttendanceRank')->classAttendanceRank;

        $this->assertSame(['Class 6', 'Class 5', 'Class 7'], array_column($rank, 'name'));
        $this->assertSame([100.0, 62.7, 0], array_column($rank, 'pct'));
        $this->assertSame([1, 3, 0], array_column($rank, 'days'));
    }

    public function test_the_student_split_is_today_alone(): void
    {
        $a = $this->klass('Class 5', 10);
        $this->mark($a, '2026-10-14', 2, 8);         // yesterday: not in the split

        $page = $this->load('loadStudentPie');
        $this->assertSame(['present' => 0, 'absent' => 0, 'presentPct' => 0, 'absentPct' => 0], $page->studentPieData);

        $this->mark($a, '2026-10-15', 3, 1);
        $page = $this->load('loadStudentPie');
        $this->assertSame(['present' => 3, 'absent' => 1, 'presentPct' => 75.0, 'absentPct' => 25.0], $page->studentPieData);

        // Whatever the old window dropdown held, it is today.
        $page->attendanceFilter = '30';
        $this->assertSame(3, $this->load('loadStudentPie', $page)->studentPieData['present']);
    }

    public function test_vs_yesterday_waits_for_today_to_be_marked(): void
    {
        $a = $this->klass('Class 5', 10);
        $this->mark($a, '2026-10-14', 6, 4);

        $kpis = $this->load('loadKpis')->kpis;
        $this->assertFalse($kpis['student_marked']);

        $this->mark($a, '2026-10-15', 9, 1);
        $kpis = $this->load('loadKpis')->kpis;
        $this->assertTrue($kpis['student_marked']);
        $this->assertSame([90.0, 30.0], [$kpis['student_rate'], $kpis['student_delta']]);
    }

    public function test_the_admissions_trend_draws_the_last_fifteen_admission_dates(): void
    {
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 5']);
        // Twenty admission dates this session, two students on each.
        for ($d = 1; $d <= 20; $d++) {
            for ($n = 0; $n < 2; $n++) {
                DB::table('student_details')->insert(['organization_id' => $this->org, 'standard_id' => $std,
                    'date_of_admission' => sprintf('2026-06-%02d', $d), 'created_at' => '2026-06-01 09:00:00']);
            }
        }

        $page = new Analytics();
        $page->admissionYear = '2026';
        $this->load('loadAdmissionsTrend', $page);

        $this->assertSame(40, $page->admissionsTotal);
        $this->assertSame(20, $page->admissionsTrend['dates']);
        $this->assertCount(15, $page->admissionsTrend['labels']);
        $this->assertSame(['06 Jun 2026', '20 Jun 2026'], [$page->admissionsTrend['labels'][0], $page->admissionsTrend['labels'][14]]);
        $this->assertSame(array_fill(0, 15, 2), $page->admissionsTrend['data']);
    }
}
