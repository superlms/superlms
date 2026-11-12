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
 * Super Admin home: the Super Admin's own figures in the school panel's
 * look, and Credit off the sidebar and Quick Links (its page, route and
 * permission stay).
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

    public function test_a_sub_super_admin_granted_credit_still_gets_no_credit_link(): void
    {
        $this->signIn('sub-super-admin', ['super-admin.dashboard', 'super-admin.schools', 'super-admin.users', 'super-admin.credit']);

        $this->assertSame(['Home', 'Schools'], $this->sidebarTitles());
        $this->assertNotContains('Credit', array_column(Livewire::test(QuickLinks::class)->viewData('orderedLinks'), 'title'));
    }

    private function table(string $name, callable $columns): void
    {
        Schema::create($name, function (Blueprint $t) use ($columns) {
            $t->id();
            $columns($t);
            $t->timestamps();
        });
    }

    public function test_the_super_admin_fee_reads_the_fee_structures_and_counts_months_by_payment_date(): void
    {
        $this->signIn();
        $this->table('organizations', fn (Blueprint $t) => $t->string('name'));
        $this->table('student_details', function (Blueprint $t) {
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
        });
        $this->table('super_admin_fee_structures', function (Blueprint $t) {
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->string('fee_type');
            $t->decimal('amount', 10, 2)->default(0);
            $t->decimal('total_amount', 10, 2)->nullable();
            $t->string('installment_frequency')->nullable();
            $t->text('period_amounts')->nullable();
            $t->string('academic_year');
            $t->boolean('is_active')->default(true);
        });
        $this->table('super_admin_fee_payments', function (Blueprint $t) {
            $t->unsignedBigInteger('organization_id');
            $t->decimal('amount', 12, 2);
            $t->string('academic_year')->nullable();
            $t->date('payment_date')->nullable();
            $t->boolean('is_paid')->default(false);
        });

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta']]);
        DB::table('student_details')->insert([['organization_id' => 2], ['organization_id' => 2], ['organization_id' => 2]]);
        // Alpha: Monthly, 500 a month = 6,000; Beta: Per Student, 1,000 × 3 = 3,000.
        DB::table('super_admin_fee_structures')->insert([
            ['organization_id' => 1, 'fee_type' => 'one_time', 'installment_frequency' => 'monthly', 'total_amount' => 6000,
             'period_amounts' => json_encode(array_fill_keys(['2026-04', '2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02', '2027-03'], 500)),
             'academic_year' => '2026-2027', 'amount' => 500],
            ['organization_id' => 2, 'fee_type' => 'per_student', 'installment_frequency' => null, 'total_amount' => null,
             'period_amounts' => null, 'academic_year' => '2026-2027', 'amount' => 1000],
        ]);

        // Money received day by day: the receipts (the real migration makes the table).
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();

        $pay = fn ($org, $amount, $date, $paid, $year = '2026-2027') => DB::table('super_admin_fee_payments')->insert(['organization_id' => $org, 'amount' => $amount, 'academic_year' => $year, 'payment_date' => $date, 'is_paid' => $paid]);
        $pay(1, 1000, '2026-10-05', true);
        $pay(1, 500, '2026-09-20', false);              // part paid — money received all the same
        $pay(2, 300, '2025-09-01', true, '2025-2026');  // last year, before the 12 months
        $pay(2, 2000, null, false);
        foreach ([[1, 1000, '2026-10-05'], [1, 500, '2026-09-20'], [2, 300, '2025-09-01'], [2, 2000, null]] as [$org, $amount, $date]) {
            DB::table('super_admin_fee_receipts')->insert(['organization_id' => $org, 'amount' => $amount, 'payment_date' => $date, 'academic_year' => '2026-2027']);
        }

        $fee = $this->helper('superAdminFee');

        $this->assertEquals(9000, $fee['total']);
        $this->assertEquals(3500, $fee['collected']);
        $this->assertEquals(5500, $fee['pending']);
        $this->assertSame(38.9, $fee['pct']);
        $this->assertEquals(1000, $fee['this_month']);
        $this->assertEquals(500, $fee['last_month']);
        $this->assertEquals(1500, $fee['year_total']);
        $this->assertCount(12, $fee['labels']);
        $this->assertSame('Nov 25', $fee['labels'][0]);
        $this->assertEquals(1000, end($fee['data']));
        $this->assertSame(2, $fee['schools']);
        $this->assertSame(2, $fee['schools_due']);
        $this->assertSame('2026-2027', $fee['year']);
    }

    public function test_staff_salary_and_attendance_count_active_staff_only(): void
    {
        $this->signIn();
        $this->table('super_admin_employees', function (Blueprint $t) {
            $t->string('name');
            $t->string('designation')->nullable();
            $t->string('type')->nullable();
            $t->decimal('salary', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
        });
        $this->table('super_admin_salary_payments', function (Blueprint $t) {
            $t->unsignedBigInteger('super_admin_employee_id');
            $t->string('month');
            $t->string('status');
            $t->decimal('amount', 12, 2);
        });
        $this->table('super_admin_attendances', function (Blueprint $t) {
            $t->unsignedBigInteger('super_admin_employee_id');
            $t->date('date');
            $t->string('status');
        });

        DB::table('super_admin_employees')->insert([
            ['id' => 1, 'name' => 'Asha', 'designation' => 'Sales', 'type' => 'team', 'salary' => 10000, 'is_active' => 1],
            ['id' => 2, 'name' => 'Bina', 'designation' => null, 'type' => 'team', 'salary' => 20000, 'is_active' => 1],
            ['id' => 3, 'name' => 'Chetan', 'designation' => null, 'type' => 'team', 'salary' => 5000, 'is_active' => 0],
        ]);
        DB::table('super_admin_salary_payments')->insert([
            ['super_admin_employee_id' => 1, 'month' => '2026-09', 'status' => 'paid', 'amount' => 10000],
            ['super_admin_employee_id' => 2, 'month' => '2026-09', 'status' => 'pending', 'amount' => 20000],
        ]);
        DB::table('super_admin_attendances')->insert([
            ['super_admin_employee_id' => 1, 'date' => '2026-10-11', 'status' => 'present'],
            ['super_admin_employee_id' => 2, 'date' => '2026-10-11', 'status' => 'half_day'],
            ['super_admin_employee_id' => 3, 'date' => '2026-10-11', 'status' => 'present'],  // inactive
            ['super_admin_employee_id' => 1, 'date' => '2026-10-10', 'status' => 'absent'],   // yesterday
        ]);

        $staff = $this->helper('staffSummary');

        $this->assertSame(3, $staff['employees']);
        $this->assertSame(2, $staff['active']);
        $this->assertEquals(30000, $staff['bill']);
        $this->assertSame('Sep 2026', $staff['month']);
        $this->assertSame(1, $staff['paid_count']);
        $this->assertEquals(10000, $staff['paid_amount']);
        $this->assertSame(1, $staff['unpaid_count']);
        $this->assertSame([['name' => 'Bina', 'role' => 'Team', 'salary' => 20000.0]], $staff['unpaid']);
        $this->assertSame(1, $staff['today']['present']);
        $this->assertSame(1, $staff['today']['half_day']);
        $this->assertSame(0, $staff['today']['absent']);
        $this->assertSame(2, $staff['today']['marked']);
        $this->assertSame(75.0, $staff['today']['pct']);
    }

    private function makeRequestTables(): void
    {
        $this->table('contact_super_admins', function (Blueprint $t) {
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('topic')->nullable();
            $t->text('admin_query')->nullable();
            $t->boolean('super_admin_reply')->default(false);
        });
        foreach (['website_demos', 'website_contacts'] as $name) {
            $this->table($name, fn (Blueprint $t) => $t->string('remark')->nullable());
        }
        foreach (['school_listings', 'career_applications', 'executive_applications'] as $name) {
            $this->table($name, fn (Blueprint $t) => $t->string('status')->nullable());
        }

        DB::table('contact_super_admins')->insert([['super_admin_reply' => 0], ['super_admin_reply' => 1]]);
        DB::table('website_demos')->insert([['remark' => null], ['remark' => ''], ['remark' => 'Called back']]);
        DB::table('website_contacts')->insert([['remark' => null], ['remark' => 'Mailed']]);
        DB::table('school_listings')->insert([['status' => 'pending'], ['status' => null], ['status' => 'approved']]);
        DB::table('career_applications')->insert([['status' => 'new'], ['status' => 'reviewed']]);
        DB::table('executive_applications')->insert([['status' => 'new'], ['status' => null], ['status' => 'contacted']]);
    }

    public function test_needs_attention_counts_what_is_waiting_on_the_super_admin(): void
    {
        $this->signIn();
        $this->makeRequestTables();

        $counts = array_column($this->helper('waitingOnYou'), 'count', 'label');

        $this->assertSame([
            'Support Tickets'        => 1,
            'Demo Requests'          => 2,
            'Contact Messages'       => 1,
            'School Listings'        => 2,
            'Career Applications'    => 1,
            'Become Executive'       => 2,
        ], $counts);
    }

    public function test_a_sub_super_admin_sees_only_the_waiting_work_of_screens_granted(): void
    {
        $this->signIn('sub-super-admin', ['super-admin.dashboard', 'super-admin.support', 'super-admin.listing']);
        $this->makeRequestTables();

        $this->assertSame(['Support Tickets', 'School Listings'], array_column($this->helper('waitingOnYou'), 'label'));
    }

    /**
     * The home's Calendar card: the signed-in person's own today, next seven
     * days and tasks left open — nobody else's — and no card for a
     * sub-super-admin who was not given Calendar.
     */
    public function test_the_calendar_card_shows_only_the_signed_in_users_own_calendar(): void
    {
        $this->signIn();
        (require base_path('database/migrations/2026_11_12_120000_create_super_admin_calendar_events_table.php'))->up();

        $add = fn (int $user, string $type, string $title, string $date, array $more = []) => \App\Models\SuperAdmin\SuperAdminCalendarEvent::create(
            ['user_id' => $user, 'type' => $type, 'title' => $title, 'date' => $date] + $more
        );
        // Today is 11 Oct 2026.
        $add(900, 'task', 'Send reminders', '2026-10-11', ['is_done' => true]);
        $add(900, 'event', 'Annual day', '2026-10-11');
        $add(900, 'meeting', 'Fee review', '2026-10-11', ['start_time' => '15:00:00', 'end_time' => '15:30:00', 'with_whom' => 'Doon School']);
        $add(900, 'meeting', 'Call Ravi', '2026-10-11', ['start_time' => '09:30:00']);
        $add(900, 'meeting', 'Demo', '2026-10-15');
        $add(900, 'event', 'Too far', '2026-10-19');
        $add(900, 'task', 'Old task', '2026-10-02');
        $add(900, 'task', 'Done long ago', '2026-10-01', ['is_done' => true]);
        $add(901, 'meeting', 'Not mine', '2026-10-11');
        $add(901, 'task', 'Not mine either', '2026-10-03');

        $cal = $this->helper('calendarSummary');

        $this->assertSame(['meetings' => 2, 'tasks' => 1, 'tasks_done' => 1, 'events' => 1],
            array_intersect_key($cal, array_flip(['meetings', 'tasks', 'tasks_done', 'events'])));
        // Timed ones by their time, then the rest; a done task last.
        $this->assertSame(['Call Ravi', 'Fee review', 'Annual day', 'Send reminders'], array_column($cal['today'], 'title'));
        $this->assertSame('3:00 PM – 3:30 PM', $cal['today'][1]['time']);
        $this->assertSame('Doon School', $cal['today'][1]['with']);
        $this->assertTrue($cal['today'][3]['done']);
        $this->assertSame(['Demo'], array_column($cal['upcoming'], 'title'));
        $this->assertSame(1, $cal['upcoming_count']);
        $this->assertSame(['Old task'], array_column($cal['overdue'], 'title'));
        $this->assertSame('Fri, 02 Oct', $cal['overdue'][0]['day']);
        $this->assertSame(1, $cal['overdue_count']);

        // A sub-super-admin with Calendar sees theirs; without it, no card.
        $this->signIn('sub-super-admin', ['super-admin.dashboard', 'super-admin.calendar'])->id = 901;
        $this->assertSame(['Not mine'], array_column($this->helper('calendarSummary')['today'], 'title'));
        $this->assertSame(['Not mine either'], array_column($this->helper('calendarSummary')['overdue'], 'title'));
        $this->signIn('sub-super-admin', ['super-admin.dashboard']);
        $this->assertNull($this->helper('calendarSummary'));
    }

    /**
     * The charts survive the panel's auto-refresh: their canvases are kept out
     * of Livewire's re-renders and their figures sit in an element that is
     * re-rendered; and a narrow card's figures are whole (the staff legend
     * keeps its labels, a KPI with no change reads 0%).
     */
    public function test_home_charts_are_kept_through_re_renders_and_figures_are_whole(): void
    {
        $this->signIn();
        $months = ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
        $zeros  = array_fill(0, 12, 0);

        $html = view('livewire.super-admin.dashboard', [
            'activeSchools' => 1, 'activeStudents' => 10, 'activeTeachers' => 2, 'newSchoolsThisMonth' => 0,
            'superAdminFee' => [
                'total' => 1000, 'collected' => 400, 'pending' => 600, 'pct' => 40, 'this_month' => 0, 'last_month' => 0,
                'schools' => 1, 'schools_due' => 1, 'year' => '2026-27', 'unpaid_records' => 1,
                'labels' => array_map(fn ($m) => $m . ' 26', $months), 'data' => array_replace($zeros, [11 => 400]), 'year_total' => 400,
            ],
            'staff' => [
                'employees' => 2, 'active' => 2, 'bill' => 30000, 'month' => 'Sep 2026', 'paid_count' => 1, 'paid_amount' => 15000,
                'paid_pct' => 50, 'unpaid_count' => 1, 'unpaid' => [['name' => 'Ravi Kumar', 'role' => 'team', 'salary' => 15000]],
                'today' => ['present' => 1, 'absent' => 0, 'half_day' => 0, 'leave' => 1, 'holiday' => 0, 'marked' => 2, 'pct' => 50],
            ],
            'waiting' => [], 'latestTickets' => [], 'latestEnquiries' => [],
            'websiteWeek' => ['visits' => 0, 'unique' => 0, 'today' => 0, 'top' => '—'],
            'pushSummary' => ['this_month' => 0, 'total' => 0, 'latest' => null],
            'latestReview' => null, 'activityFeed' => [],
            'studentsBySchool' => [['label' => 'Alpha Public School', 'value' => 12, 'color' => '#7c3aed']],
            'totalSchools' => 1, 'totalStudents' => 12, 'totalTeachers' => 2, 'avgStudentsPerSchool' => 12,
            'topSchoolsByStudents' => [], 'topSchoolsByTeachers' => [], 'recentSchools' => [], 'recentActivities' => [],
            'monthlyRegistrations' => ['labels' => array_map(fn ($m) => $m . ' 2026', $months), 'students' => $zeros, 'teachers' => $zeros, 'schools' => $zeros],
            'ratingStats' => ['total_schools' => 1, 'schools_rated' => 0, 'remaining' => 1, 'avg_rating' => 0],
            'supportStats' => ['total' => 0, 'pending' => 0, 'replied' => 0, 'this_month' => 0],
            'enquiryStats' => ['demo_total' => 0, 'contact_total' => 0],
            'creditStats' => [],
            'calendar' => [
                'meetings' => 1, 'tasks' => 2, 'tasks_done' => 1, 'events' => 0,
                'today' => [
                    ['type' => 'meeting', 'title' => 'Fee review', 'time' => '3:00 PM – 3:30 PM', 'with' => 'Doon School', 'done' => false, 'date' => '2026-10-11', 'day' => 'Sun, 11 Oct'],
                    ['type' => 'task', 'title' => 'Send reminders', 'time' => 'Any time', 'with' => null, 'done' => true, 'date' => '2026-10-11', 'day' => 'Sun, 11 Oct'],
                ],
                'today_more' => 1,
                'upcoming' => [], 'upcoming_count' => 0,
                'overdue' => [['type' => 'task', 'title' => 'Old task', 'time' => 'Any time', 'with' => null, 'done' => false, 'date' => '2026-10-02', 'day' => 'Fri, 02 Oct']],
                'overdue_count' => 1,
            ],
        ])->render();

        foreach (['homeFeeChart', 'homeRegChart'] as $id) {
            $this->assertMatchesRegularExpression('/<div[^>]*wire:ignore[^>]*>\s*<canvas id="' . $id . '"/', $html);
        }
        // No chart is drawn from an Alpine init on its canvas any more.
        $this->assertStringNotContainsString('<canvas x-data', $html);
        $this->assertStringContainsString('data-sa-charts="home"', $html);
        $this->assertStringContainsString("saCharts.define('home'", $html);

        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Leave / holiday 1 · 50%', $text);
        $this->assertStringContainsString('Ravi Kumar Team ₹15,000', $text);
        $this->assertStringContainsString('0% vs last month', $text);

        // The Calendar card: today's counts, its three lists, each row opening its day.
        $this->assertStringContainsString('1 meeting today 1/2 tasks done 0 events Open →', $text);
        $this->assertStringContainsString('Today · 11 Oct 3 Fee review 3:00 PM – 3:30 PM · With Doon School Send reminders Any time +1 more', $text);
        $this->assertStringContainsString('Next 7 days 0 Nothing in the next 7 days', $text);
        $this->assertStringContainsString('Tasks still open 1 Old task Fri, 02 Oct · Any time', $text);
        $this->assertStringContainsString('href="' . e(route('super-admin.calendar', ['month' => '2026-10', 'day' => '2026-10-02'])) . '"', $html);
        $this->assertStringContainsString('line-through', $html);
    }
}
