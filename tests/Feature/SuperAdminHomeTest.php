<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Dashboard;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin home in the school dashboard's look, and Credit off the
 * sidebar and Quick Links (its page, route and permission stay).
 */
class SuperAdminHomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('h', 32))]);
        Carbon::setTestNow('2026-10-11 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(string $role = 'super-admin', array $permissions = []): User
    {
        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 900;
        $user->role = $role;
        $user->permissions = $permissions;
        $this->actingAs($user);

        return $user;
    }

    /** Titles the sidebar links carry (desktop list). */
    private function sidebarTitles(): array
    {
        $html = view('admin-components.super-admin-sidebar')->render();
        preg_match_all('/<a href="[^"]*"\s+title="([^"]+)"/', $html, $m);

        return array_map('html_entity_decode', $m[1]);
    }

    /** Runs one of the component's protected helpers. */
    private function helper(string $method, ...$args)
    {
        $component = new Dashboard();

        return (fn () => $this->{$method}(...$args))->call($component);
    }

    public function test_credit_is_off_the_sidebar_but_stays_in_the_menu_list(): void
    {
        $this->signIn();
        $titles = $this->sidebarTitles();

        $this->assertNotContains('Credit', $titles);
        $this->assertContains('Payroll', $titles);
        $this->assertContains('Support', $titles);
        $this->assertContains('super-admin.credit', array_column(config('menu.super-admin'), 'link'));
    }

    public function test_quick_links_have_no_credit_tile(): void
    {
        $this->signIn();
        $titles = array_column(Livewire::test(QuickLinks::class)->viewData('orderedLinks'), 'title');

        $this->assertNotContains('Credit', $titles);
        $this->assertContains('Payroll', $titles);
    }

    public function test_a_sub_super_admin_granted_credit_still_gets_no_credit_link_and_only_granted_tiles(): void
    {
        $this->signIn('sub-super-admin', ['super-admin.dashboard', 'super-admin.schools', 'super-admin.users', 'super-admin.credit']);

        $this->assertSame(['Home', 'Schools'], $this->sidebarTitles());
        $this->assertSame(['Schools'], array_column($this->helper('quickTiles'), 'label'));
        $this->assertNotContains('Credit', array_column(Livewire::test(QuickLinks::class)->viewData('orderedLinks'), 'title'));
    }

    public function test_home_tiles_for_the_super_admin_have_no_credit(): void
    {
        $this->signIn();
        $labels = array_column($this->helper('quickTiles'), 'label');

        $this->assertContains('Schools', $labels);
        $this->assertContains('Users', $labels);
        $this->assertNotContains('Credit', $labels);
    }

    private function makeTables(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('logo')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('role');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        foreach (['student_attendances' => 'student_detail_id', 'teacher_attendances' => 'teacher_detail_id'] as $table => $who) {
            Schema::create($table, function (Blueprint $t) use ($who) {
                $t->id();
                $t->unsignedBigInteger($who)->nullable();
                $t->unsignedBigInteger('organization_id');
                $t->date('attendance_date');
                $t->unsignedTinyInteger('status');
                $t->timestamps();
            });
        }
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

    public function test_attendance_and_fee_are_counted_across_schools_as_the_school_dashboard_counts_them(): void
    {
        $this->signIn();
        $this->makeTables();

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Zeta School', 'status' => 1],
            ['id' => 2, 'name' => 'alpha school', 'status' => 1],
            ['id' => 3, 'name' => 'Idle School', 'status' => 0],
        ]);
        foreach ([[1, 'user', 4], [1, 'teacher', 2], [2, 'user', 2], [2, 'teacher', 1]] as [$org, $role, $n]) {
            for ($i = 0; $i < $n; $i++) {
                DB::table('users')->insert(['name' => "$role $org $i", 'role' => $role, 'organization_id' => $org]);
            }
        }

        $today = '2026-10-11';
        $mark = fn ($table, $org, $date, $status) => DB::table($table)->insert(['organization_id' => $org, 'attendance_date' => $date, 'status' => $status]);
        // School 1 today: 2 present, 1 absent, 1 half. School 2 today: 1 present.
        foreach ([1, 1, 0, 2] as $s) { $mark('student_attendances', 1, $today, $s); }
        $mark('student_attendances', 2, $today, 1);
        $mark('student_attendances', 2, '2026-10-10', 0);
        $mark('student_attendances', 1, $today, 3);       // holiday: left out
        $mark('student_attendances', 99, $today, 1);      // a deleted school: left out
        $mark('student_attendances', 1, '2026-09-01', 1); // older than 30 days
        $mark('teacher_attendances', 1, $today, 1);

        $fee = fn ($org, $type, $amount, $date) => DB::table('fee_payments')->insert(['organization_id' => $org, 'fee_type' => $type, 'amount' => $amount, 'payment_date' => $date]);
        $fee(1, 'academic', 500, $today);
        $fee(2, 'transport', 300, '2026-10-10');
        $fee(1, 'academic', 1000, '2026-05-02');  // this session, outside 30 days
        $fee(1, 'academic', 4000, '2026-03-20');  // last session
        $fee(1, 'penalty', 50, $today);           // not academic/transport
        DB::table('transport_fee_payments')->insert(['organization_id' => 2, 'amount' => 200, 'payment_date' => $today]);

        $attendance = $this->helper('attendanceBySchool');
        $trend      = $this->helper('attendanceTrend', $attendance);
        $todayRow   = end($trend);

        $this->assertCount(30, $trend);
        $this->assertSame('12 Sep', $trend[0]['label']);
        $this->assertSame(3, $todayRow['student_present']);
        $this->assertSame(1, $todayRow['student_absent']);
        $this->assertSame(1, $todayRow['student_half']);
        $this->assertSame(70.0, $todayRow['student_pct']); // (3 + 0.5) / 5
        $this->assertSame(1, $todayRow['teacher_present']);
        $this->assertSame(1, $trend[28]['student_absent']);

        $fees    = $this->helper('schoolFees');
        $summary = $this->helper('feeSummary', $fees);

        $this->assertEquals(700, $summary['today']);
        $this->assertEquals(1000, $summary['month']);
        $this->assertEquals(2000, $summary['session']);
        $this->assertEquals(1000, $summary['range_total']);
        $this->assertSame(2, $summary['schools_today']);
        $this->assertSame('01 Apr 2026', $summary['session_from']);

        $schools = $this->helper('schoolsToday', $attendance, $fees);

        $this->assertSame(['alpha school', 'Idle School', 'Zeta School'], array_column($schools, 'name'));
        [$alpha, $idle, $zeta] = $schools;
        $this->assertSame(4, $zeta['students']);
        $this->assertTrue($zeta['student_marked']);
        $this->assertEquals(50, $zeta['student_pct']);   // 2 present of 4 students
        $this->assertEquals(500, $zeta['fee_today']);
        $this->assertEquals(1500, $zeta['fee_session']);
        $this->assertEquals(200, $alpha['fee_today']);
        $this->assertEquals(500, $alpha['fee_session']);
        $this->assertFalse($idle['student_marked']);
        $this->assertFalse($idle['status']);
    }
}
