<?php

namespace Tests\Feature;

use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Home;
use App\Models\Organization;
use App\Models\User;
use App\Support\SchoolMonthly;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The school admin's Home and Analytics in the Super Admin's dashboard look
 * (2026-11-04): KPI cards with a sparkline and their change, soft cards, a
 * dark card, and Chart.js charts kept drawn from a figures element
 * (partials.dash-charts-js). The pages are rendered with their figures set
 * directly (their own queries need MySQL), and the new monthly figures
 * (App\Support\SchoolMonthly) are read from a small database.
 */
class AdminDashboardLookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-04 10:00:00');
        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);

        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('fee_type');
            $t->decimal('amount', 12, 2);
            $t->date('payment_date');
            $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->decimal('amount', 12, 2);
            $t->date('payment_date');
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(): void
    {
        $org = new Organization(['name' => 'Test School']);
        $org->id = 8;

        $user = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $user->id = 500;
        $user->role = 'admin';
        $user->organization_id = 8;
        $user->setRelation('organization', $org);
        $this->actingAs($user);
    }

    /** The figures element's JSON, as the charts read it. */
    private function payload(string $html, string $name): array
    {
        $this->assertMatchesRegularExpression('/data-sa-charts="' . $name . '" data-payload="([^"]*)"/', $html);
        preg_match('/data-sa-charts="' . $name . '" data-payload="([^"]*)"/', $html, $m);

        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }

    /** Thirty days up to today; Sundays are not marked. */
    private function days(bool $marked = true): array
    {
        $out = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $on  = $marked && !$day->isSunday();
            [$sp, $sa, $sh, $tp, $ta] = $on ? [900 + $i, 80 - $i, 4, 44, 2] : [0, 0, 0, 0, 0];
            $sm = $sp + $sa + $sh;
            $tm = $tp + $ta;
            $out[] = [
                'date' => $day->toDateString(), 'label' => $day->format('d M'), 'day' => $day->format('D'),
                'student_present' => $sp, 'student_absent' => $sa, 'student_half' => $sh, 'student_total' => $sm,
                'student_pct' => $sm > 0 ? round(($sp + 0.5 * $sh) / $sm * 100, 1) : 0,
                'teacher_present' => $tp, 'teacher_absent' => $ta, 'teacher_half' => 0, 'teacher_total' => $tm,
                'teacher_pct' => $tm > 0 ? round($tp / $tm * 100, 1) : 0,
                'fee_collected' => 0,
            ];
        }

        return $out;
    }

    private function months(array $students, array $teachers, array $fee): array
    {
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $labels[] = now()->startOfMonth()->subMonthsNoOverflow($i)->format('M y');
        }

        return compact('labels', 'students', 'teachers', 'fee');
    }

    public function test_the_monthly_figures_and_students_by_class(): void
    {
        DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => 8, 'name' => 'Class 5'],
            ['id' => 2, 'organization_id' => 8, 'name' => 'Class 6'],
            ['id' => 3, 'organization_id' => 9, 'name' => 'Elsewhere'],
        ]);
        $student = fn ($class, $at, $org = 8) => ['organization_id' => $org, 'standard_id' => $class, 'created_at' => $at, 'updated_at' => $at];
        DB::table('student_details')->insert([
            $student(1, '2026-11-01 09:00:00'), $student(1, '2026-11-02 09:00:00'), $student(1, '2026-11-03 09:00:00'), $student(1, '2026-11-04 08:00:00'),
            $student(null, '2026-11-02 09:00:00'),
            $student(2, '2026-10-10 09:00:00'), $student(2, '2026-10-11 09:00:00'),
            $student(2, '2025-10-01 09:00:00'),          // before the twelve months
            $student(3, '2026-11-01 09:00:00', 9),       // another school
        ]);
        DB::table('teacher_details')->insert(['organization_id' => 8, 'created_at' => '2026-09-15 09:00:00', 'updated_at' => now()]);
        DB::table('fee_payments')->insert([
            ['organization_id' => 8, 'fee_type' => 'academic', 'amount' => 1000, 'payment_date' => '2026-11-02'],
            ['organization_id' => 8, 'fee_type' => 'transport', 'amount' => 500, 'payment_date' => '2026-11-03'],
            ['organization_id' => 8, 'fee_type' => 'admission', 'amount' => 300, 'payment_date' => '2026-11-03'],   // not academic / transport
            ['organization_id' => 8, 'fee_type' => 'academic', 'amount' => 900, 'payment_date' => '2026-11-20'],    // after today
            ['organization_id' => 9, 'fee_type' => 'academic', 'amount' => 7000, 'payment_date' => '2026-11-02'],   // another school
        ]);
        DB::table('transport_fee_payments')->insert(['organization_id' => 8, 'amount' => 200, 'payment_date' => '2026-10-15']);

        $m = SchoolMonthly::forSchool(8);

        $this->assertCount(12, $m['labels']);
        $this->assertSame('Dec 25', $m['labels'][0]);
        $this->assertSame('Nov 26', $m['labels'][11]);
        $this->assertSame(5, $m['students'][11]);
        $this->assertSame(2, $m['students'][10]);
        $this->assertSame(7, array_sum($m['students']));
        $this->assertSame(1, $m['teachers'][9]);
        $this->assertEquals(1500, $m['fee'][11]);
        $this->assertEquals(200, $m['fee'][10]);

        $parts = SchoolMonthly::studentsByClass(8);
        $this->assertSame(['Class 5', 'Class 6', 'Others'], array_column($parts, 'label'));
        $this->assertSame([4, 3, 1], array_column($parts, 'value'));
    }

    public function test_home_is_in_the_dashboard_look(): void
    {
        $this->signIn();
        $days = $this->days();
        HomeLookFixture::$data = [
            'organization' => 8,
            'totalStudents' => 1234, 'activeStudents' => 1200, 'totalTeachers' => 48, 'activeTeachers' => 46,
            'totalClasses' => 14, 'totalSubjects' => 22,
            'totalFee' => 5000000, 'overallFeeCollected' => 3215000, 'feeRemaining' => 1785000, 'feeCollectedToday' => 45000,
            'studentQueries' => 3, 'teacherQueries' => 1, 'websiteQueries' => 7,
            'latestRating' => (object) ['rating' => 4, 'created_at' => '2026-11-01 10:00:00', 'feedback' => 'Helpful for our office'],
            'averageRating' => 4.5, 'totalRatings' => 2,
            'last15DaysData' => $days, 'last7DaysData' => array_slice($days, -7),
            'feeSeries' => ['labels' => array_column($days, 'label'), 'data' => array_map(fn ($i) => $i * 1000, range(1, 30))],
            'feeRangeTotal' => 465000,
            'examTrend' => [
                ['exam' => 'Unit Test 1', 'label' => 'Unit Test 1', 'date' => '10 Aug 26', 'avg' => 61.5, 'pass_pct' => 70.0, 'passed' => 70, 'papers' => 500, 'students' => 100],
                ['exam' => 'Half-Yearly', 'label' => 'Half-Yearly', 'date' => '30 Sep 26', 'avg' => 66.2, 'pass_pct' => 78.0, 'passed' => 78, 'papers' => 600, 'students' => 100],
            ],
            'examTrendAvg' => 63.9, 'examTrendDelta' => 4.7,
            'upcomingEvents' => [[
                'title' => 'Annual Day', 'description' => 'Songs and plays', 'date' => '2026-11-20', 'formatted_date' => '20 Nov',
                'start_time' => '10:00 AM', 'end_time' => null, 'is_all_day' => false, 'color' => 'purple', 'event_type' => 'annual_day', 'location' => 'Main hall',
            ]],
            'recentSearches' => [['term' => 'Fee Management', 'time' => now()->subHour()]],
            'monthly' => $this->months([5, 8, 2, 0, 1, 40, 60, 12, 6, 3, 2, 4], [0, 1, 0, 0, 0, 3, 2, 0, 0, 1, 0, 1], array_fill(0, 12, 250000)),
            'studentsByClass' => [
                ['label' => 'Class 5', 'value' => 320, 'color' => '#7c3aed'],
                ['label' => 'Others', 'value' => 914, 'color' => '#e5e7eb'],
            ],
        ];

        $page = Livewire::test(HomeLookFixture::class);
        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        foreach (['Dashboard', 'Total Students', '1,234', '1,200 active · 14 classes', 'Total Teachers', '46 active · 22 subjects',
                  'Fee Collected', '₹32.15L', '64.3% of ₹50L · ₹45,000 today', 'Attendance Today', 'vs yesterday',
                  'Fee Collection', '₹4.65L in 30 days', '₹17.85L pending', 'Students by Class', 'Class 5', 'Others',
                  'Attendance Rate', 'Today', 'Half day', 'Teachers present', 'Attendance · Last 7 Days',
                  'Upcoming Events', 'Annual Day', 'Main hall', 'Exam Performance', 'Latest · Half-Yearly', '66.2%', '4.7 pts',
                  'Queries', 'LMS Rating', '4.5', 'Helpful for our office', 'Quick Links', 'Recent Searches', 'Fee Management'] as $seen) {
            $this->assertStringContainsString($seen, $text, "Home shows \"{$seen}\"");
        }
        foreach (['sa-card-dark', 'id="adminHomeFeeChart"', 'id="adminHomeAttChart"', 'id="adminHomeExamChart"', "saCharts.define('adminHome'", '/analytics"'] as $seen) {
            $this->assertStringContainsString($seen, $html);
        }

        // The attendance lines leave a gap on a day nobody was marked.
        $p = $this->payload($html, 'adminHome');
        $this->assertCount(30, $p['att']['students']);
        $sundays = array_keys(array_filter($days, fn ($d) => Carbon::parse($d['date'])->isSunday()));
        $this->assertNull($p['att']['students'][$sundays[0]]);
        $this->assertSame(end($days)['student_pct'], end($p['att']['students']));
        $this->assertSame(['Unit Test 1', 'Half-Yearly'], $p['exam']['labels']);
        $this->assertSame(29, $p['fee']['peak']);
    }

    public function test_home_for_a_school_with_nothing_yet(): void
    {
        $this->signIn();
        HomeLookFixture::$data = [
            'organization' => 8,
            'last15DaysData' => $this->days(false), 'last7DaysData' => array_slice($this->days(false), -7),
            'feeSeries' => ['labels' => [], 'data' => []], 'feeRangeTotal' => 0,
            'averageRating' => 0, 'totalRatings' => 0,
            'monthly' => $this->months(array_fill(0, 12, 0), array_fill(0, 12, 0), array_fill(0, 12, 0)),
        ];

        $html = Livewire::test(HomeLookFixture::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        foreach (['Not marked yet today', 'No students yet', 'No upcoming events', 'No exam marks recorded yet.', 'No ratings yet'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        $this->assertStringNotContainsString('id="adminHomeExamChart"', $html);
        $this->assertStringNotContainsString('Recent Searches', $text);
    }

    public function test_analytics_is_in_the_dashboard_look(): void
    {
        $this->signIn();
        $days  = $this->days();
        $pct   = fn (string $who) => array_map(fn ($d) => $d[$who . '_total'] > 0 ? round($d[$who . '_present'] / ($d[$who . '_present'] + $d[$who . '_absent']) * 100, 1) : null, $days);
        AnalyticsLookFixture::$data = [
            'statsData' => ['totalStudents' => 1234, 'activeStudents' => 1234, 'presentToday' => 900, 'absentToday' => 80, 'newAdmissions' => 12],
            'kpis' => [
                'student_rate' => 91.3, 'student_delta' => 1.2, 'student_marked' => true, 'teacher_rate' => 95.7,
                'collect_rate' => 64.3, 'avg_daily' => 15500, 'academic_fee' => 4200000, 'transport_fee' => 600000,
                'last_year_dues' => 200000, 'unpaid_students' => 37, 'new_admissions' => 12,
            ],
            'studentPieData' => ['present' => 900, 'absent' => 80, 'presentPct' => 91.8, 'absentPct' => 8.2],
            'attendanceDailyTrend' => [
                'labels' => array_column($days, 'label'), 'student' => $pct('student'), 'teacher' => $pct('teacher'),
                'studentPresent' => array_column($days, 'student_present'), 'studentAbsent' => array_column($days, 'student_absent'),
                'teacherPresent' => array_column($days, 'teacher_present'), 'teacherAbsent' => array_column($days, 'teacher_absent'),
            ],
            'monthlyAttendancePct' => ['from' => '1 Apr 2026', 'labels' => ['Apr 2026', 'May 2026'], 'student' => [90.1, null], 'teacher' => [96.0, 94.5], 'studentDays' => [20, 0], 'teacherDays' => [20, 18]],
            'classAttendanceRank' => [['name' => 'Class 5', 'pct' => 93.4, 'days' => 80, 'present' => 2000, 'total' => 2140], ['name' => 'Class 6', 'pct' => 64.0, 'days' => 80, 'present' => 1500, 'total' => 2340]],
            'standards' => collect(), 'sections' => [],
            'topRankers' => [['rank' => 1, 'photo' => null, 'name' => 'Aarav Sharma', 'class' => 'Class 5', 'section' => 'A', 'score' => 96.5, 'obtained' => 482.5, 'max' => 500]],
            'bottomRankers' => [['rank' => 120, 'photo' => null, 'name' => 'Kabir Rao', 'class' => 'Class 6', 'section' => 'B', 'score' => 31.0, 'obtained' => 155, 'max' => 500]],
            'admissionYears' => [2026, 2025], 'admissionYear' => '2026',
            'admissionsTrend' => ['labels' => ['01 Apr 2026', '15 Apr 2026'], 'data' => [40, 12], 'dates' => 2], 'admissionsTotal' => 52,
            'enquiryStats' => ['total' => 10, 'responded' => 6, 'pending' => 2, 'rate' => 75.0, 'student' => 5, 'teacher' => 3, 'website' => 2],
            'adminEnquiries' => [['tab' => 'student', 'name' => 'Riya', 'source' => 'Student', 'time' => '2 hours ago', 'status' => 'Pending']],
            'feeStats' => ['totalFee' => 5000000, 'collected' => 3215000, 'remaining' => 1785000, 'transportFee' => 600000, 'academicFee' => 4200000, 'lastYearDues' => 200000],
            'feeClassData' => ['labels' => ['Class 5', 'Class 6'], 'collected' => [800000, 600000], 'remaining' => [200000, 400000]],
            'feeClassRate' => [['name' => 'Class 5', 'pct' => 80.0], ['name' => 'Class 6', 'pct' => 60.0]],
            'transportFeeStats' => ['expected' => 600000, 'collected' => 420000, 'remaining' => 180000, 'rate' => 70.0, 'riders' => 140],
            'transportClassData' => ['labels' => ['Class 5'], 'collected' => [100000], 'remaining' => [20000]],
            'transportRouteData' => ['labels' => ['Route 1', 'Route 2'], 'collected' => [200000, 220000], 'remaining' => [80000, 100000]],
            'arrangements' => [['id' => 3, 'class' => 'Class 5', 'section' => 'A', 'absent_teacher' => 'Mr. Verma', 'time' => '09:00 – 09:40', 'status' => 'pending']],
            'availableTeachers' => [['id' => 7, 'name' => 'Ms. Iyer']],
            'monthly' => $this->months(array_fill(0, 12, 4), array_fill(0, 12, 0), array_merge(array_fill(0, 11, 250000), [300000])),
        ];

        $html = Livewire::test(AnalyticsLookFixture::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        foreach (['Key Metrics', 'Student Attendance', '91.3%', 'vs yesterday', '900 present · 80 absent today', 'Teacher Attendance', '95.7%',
                  'Fee Collection', '64.3%', '₹32.15L of ₹50L total fee collected', 'Total Students', '1,234', '12 new in the last 30 days',
                  'Academic Fee', '₹4,200,000', 'Last Year Dues', 'Unpaid Students', '37',
                  'Attendance Rate Trend', 'Student Split', '91.8%', 'Student Attendance Volume', 'Teacher Attendance Volume',
                  'Class-wise Attendance Ranking', 'Class 6', 'Top 10 Rankers', 'Aarav Sharma', 'Bottom 10 Rankers', 'Kabir Rao',
                  'Admissions Trend', 'Apr 2026 – Mar 2027', 'Enquiry Funnel', 'Riya', 'Monthly Collection', 'Where the Fee Stands',
                  'Fee Collection by Class', 'Recovery Rate by Class', 'Transport Fee by Class', 'Transport Fee by Route', '140 riders',
                  "Today's Absent Teachers", 'Mr. Verma', 'Ms. Iyer', 'Assign'] as $seen) {
            $this->assertStringContainsString($seen, $text, "Analytics shows \"{$seen}\"");
        }
        foreach (['adminAttTrendChart', 'adminStuVolumeChart', 'adminTchVolumeChart', 'adminAdmissionsChart', 'adminFeeMonthlyChart',
                  'adminFeeClassChart', 'adminTransportClassChart', 'adminTransportRouteChart'] as $canvas) {
            $this->assertStringContainsString('id="' . $canvas . '"', $html);
        }
        $this->assertStringContainsString('sa-card-dark', $html);

        $p = $this->payload($html, 'adminAnalytics');
        $this->assertSame([40, 12], $p['admissions']['data']);
        $this->assertSame(['Route 1', 'Route 2'], $p['transportRoute']['labels']);
        $this->assertCount(12, $p['feeMonthly']['data']);
    }

    public function test_analytics_for_a_school_with_nothing_yet(): void
    {
        $this->signIn();
        AnalyticsLookFixture::$data = [
            'standards' => collect(), 'admissionYears' => [2026], 'admissionYear' => '2026',
            'attendanceDailyTrend' => ['labels' => array_column($this->days(false), 'label'), 'student' => array_fill(0, 30, null), 'teacher' => array_fill(0, 30, null)],
            'monthly' => $this->months(array_fill(0, 12, 0), array_fill(0, 12, 0), array_fill(0, 12, 0)),
        ];

        $html = Livewire::test(AnalyticsLookFixture::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        foreach (['Not marked yet today', 'Student attendance is not marked yet today.', 'No attendance recorded yet.',
                  'No exam marks for the selected filters.', 'No enquiries yet.', 'No fee data available.',
                  'No transport riders assigned yet.', 'No transport routes yet.', 'no arrangements needed'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        $this->assertStringNotContainsString('vs yesterday', $text);
        $this->assertStringNotContainsString('id="adminTransportRouteChart"', $html);
    }
}

/** Home with its figures set directly instead of read from the database. */
class HomeLookFixture extends Home
{
    public static array $data = [];

    public function mount()
    {
        foreach (static::$data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}

/** Analytics with its figures set directly instead of read from the database. */
class AnalyticsLookFixture extends Analytics
{
    public static array $data = [];

    public function mount(): void
    {
        foreach (static::$data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
