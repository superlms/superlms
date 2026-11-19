<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Analytics;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin Analytics in the school Analytics' look: every tab's figures on
 * one page as sections (Credit left out at the user's ask), and the panel's
 * scrollbars in the school panel's style.
 */
class SuperAdminAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        Carbon::setTestNow('2026-10-11 10:00:00');

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 901;
        $user->role = 'super-admin';
        $user->permissions = [];
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_every_tab_is_a_section_on_one_page_without_credit(): void
    {
        $html = Livewire::test(AnalyticsWithFigures::class)->html();

        // Home's look with a chip a functionality (2026-11-18): each section is
        // its chip's, all on the page, the picked one shown (Overview first).
        preg_match_all('/data-an="([a-z]+)"/', $html, $m);
        $this->assertSame(['overview', 'schools', 'website', 'fee', 'payroll', 'calendar', 'enquiries', 'support'], $m[1]);
        preg_match_all('/x-on:click="go\(\'([a-z]+)\'\)"/', $html, $chips);
        $this->assertSame($m[1], $chips[1]);
        $this->assertStringContainsString('class="sa-skin sa-home min-h-screen"', $html);
        $this->assertStringContainsString('class="sk-head"', $html);

        // No tab strip, and nothing of Credit on the page.
        $this->assertStringNotContainsString('setTab(', $html);
        $this->assertDoesNotMatchRegularExpression('/\bCredit\b/i', strip_tags($html));
        $this->assertStringNotContainsString('creditMonthlyChart', $html);

        // Every section's charts are there at once, each canvas kept out of
        // Livewire's re-renders (wire:ignore) so the panel's auto-refresh
        // never blanks it.
        foreach (['overviewMonthlyChart', 'schoolsMonthlyChart', 'websiteVisitsChart', 'feeMonthlyChart',
                  'payrollMonthlyChart', 'calendarMonthlyChart', 'enquiryMonthlyChart', 'supportMonthlyChart'] as $id) {
            $this->assertMatchesRegularExpression('/<div[^>]*wire:ignore[^>]*>\s*<canvas id="' . $id . '"/', $html);
        }
        $this->assertStringContainsString('data-sa-charts="analytics"', $html);

        // The gender and payment-mode donuts are drawn on the server, with
        // their figures in the legend.
        foreach (['genderSplitChart', 'feeModeChart', 'payrollModeChart'] as $id) {
            $this->assertStringNotContainsString('id="' . $id . '"', $html);
        }

        // Figures from each section.
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Female 25 · 50%', $text);
        $this->assertStringContainsString('Cash ₹6,000 · 100%', $text);
        $this->assertStringContainsString('Bank Transfer ₹30,000 · 100%', $text);
        // A payroll type and a role read as words, not as stored.
        $this->assertStringContainsString('Team 3', $text);
        $this->assertStringContainsString('Avg / School 12.5', $text);
        $this->assertStringContainsString('Most Visited About', $text);
        $this->assertStringContainsString('Collected ₹6,000', $text);
        $this->assertStringContainsString('Monthly Bill ₹45,000', $text);
        $this->assertStringContainsString('Total Pending 5', $text);
        $this->assertStringContainsString('Reply Rate 75%', $text);
        // The Calendar's figures and its tasks donut.
        $this->assertStringContainsString('Meetings · Oct 2026 4 1 last month', $text);
        $this->assertStringContainsString('Tasks · Oct 2026 5 3 done · 60%', $text);
        $this->assertStringContainsString('Tasks Still Open 2 from earlier days, not done', $text);
        $this->assertStringContainsString('Today 3 1 meeting · 2 tasks · 0 events', $text);
        $this->assertStringContainsString('Done 6 · 60%', $text);
        $this->assertStringContainsString('Overdue 2 · 20%', $text);
        $this->assertStringContainsString('"calendar":{"meetings":[1,4]', html_entity_decode($html));
        // A fractional day count from Carbon reads as whole days.
        $this->assertStringContainsString('· 30 days', $text);

        // The header is the title, the date and the months picker — no
        // Schools / Students / Rating figures (2026-10-13).
        $header = substr($html, 0, strpos($html, '<nav class="an-chips"'));
        $this->assertStringContainsString('Analytics', $header);
        $this->assertStringContainsString('wire:model.live="months"', $header);
        foreach (['>Schools<', '>Students<', '>Rating<'] as $figure) {
            $this->assertStringNotContainsString($figure, $header);
        }
    }

    public function test_students_by_gender_reads_the_students_own_details(): void
    {
        \Illuminate\Support\Facades\Schema::create('student_details', function (\Illuminate\Database\Schema\Blueprint $t) {
            $t->id();
            $t->string('gender')->nullable();
            $t->timestamps();
        });
        foreach (['male', 'Male', ' female', 'other', 'Others', null, ''] as $g) {
            \Illuminate\Support\Facades\DB::table('student_details')->insert(['gender' => $g]);
        }

        $split = (fn () => $this->studentGenderSplit())->call(new Analytics());

        $this->assertSame(['male' => 2, 'female' => 1, 'other' => 2, 'unknown' => 2], $split);
    }

    /**
     * The Calendar section counts the signed-in person's own diary only —
     * nobody else's — and is off the page for a sub-super-admin who was not
     * given Calendar.
     */
    public function test_the_calendar_section_counts_only_the_signed_in_users_own_calendar(): void
    {
        (require base_path('database/migrations/2026_11_12_120000_create_super_admin_calendar_events_table.php'))->up();

        $add = fn (int $user, string $type, string $date, bool $done = false) => \App\Models\SuperAdmin\SuperAdminCalendarEvent::create([
            'user_id' => $user, 'type' => $type, 'title' => ucfirst($type), 'date' => $date, 'is_done' => $done,
        ]);
        // Today is 11 Oct 2026; the window is the last 12 months (Nov 2025 – Oct 2026).
        $add(901, 'meeting', '2026-10-11');
        $add(901, 'task', '2026-10-11', true);
        $add(901, 'task', '2026-10-05');            // overdue
        $add(901, 'event', '2026-10-14');           // in the next 7 days
        $add(901, 'meeting', '2026-10-25');
        $add(901, 'meeting', '2026-09-15');         // last month
        $add(901, 'task', '2026-09-20', true);
        $add(901, 'task', '2025-01-01');            // overdue, before the window
        // Someone else's: never counted.
        $add(902, 'meeting', '2026-10-11');
        $add(902, 'task', '2026-10-01');
        $add(902, 'event', '2026-10-12');

        $load = function () {
            $component = new Analytics();
            (fn () => $this->loadCalendar())->call($component);

            return $component;
        };

        $c = $load();
        $this->assertSame([
            'month' => 'Oct 2026', 'meetings' => 2, 'meetingsLast' => 1, 'tasks' => 2, 'tasksDone' => 1, 'tasksPct' => 50.0,
            'events' => 1, 'eventsLast' => 0, 'today' => 2, 'todayMeetings' => 1, 'todayTasks' => 1, 'todayEvents' => 0,
            'overdue' => 2, 'next7' => 1, 'windowTotal' => 7, 'windowTasks' => 3, 'windowDone' => 2,
            'windowOverdue' => 1, 'windowToDo' => 0, 'windowPct' => 66.7,
        ], $c->calendarStats);
        $this->assertCount(12, $c->calendarMonthly['meetings']);
        $this->assertSame([1, 2], array_slice($c->calendarMonthly['meetings'], -2));
        $this->assertSame([1, 2], array_slice($c->calendarMonthly['tasks'], -2));
        $this->assertSame([0, 1], array_slice($c->calendarMonthly['events'], -2));

        // Coming up: today and the next seven days, a task done left out, in date order.
        $this->assertSame(['Meeting', 'Event'], array_column($c->calendarUpcoming, 'title'));
        $this->assertSame('Today', $c->calendarUpcoming[0]['when']);
        $this->assertSame('Wed, 14 Oct', $c->calendarUpcoming[1]['when']);

        // A sub-super-admin given Calendar: their own diary alone.
        $sub = new User(['name' => 'Sub', 'email' => 'sub@example.com']);
        $sub->id = 902;
        $sub->role = 'sub-super-admin';
        $sub->permissions = ['super-admin.analytics', 'super-admin.calendar'];
        $this->actingAs($sub);
        $c = $load();
        $this->assertSame(1, $c->calendarStats['meetings']);
        $this->assertSame(1, $c->calendarStats['overdue']);
        $this->assertSame(1, $c->calendarStats['next7']);
        $this->assertSame(3, $c->calendarStats['windowTotal']);

        // Not given Calendar: no section.
        $sub->permissions = ['super-admin.analytics'];
        $this->assertSame([], $load()->calendarStats);
        $this->assertSame([], $load()->calendarMonthly);
        $this->assertSame([], $load()->calendarUpcoming);
    }

    /**
     * Every section's extra detail (user's ask of 2026-11-19): this month
     * against last, the platform's health and what waits on the Super Admin;
     * the schools by state / board / medium and the newest; the website's
     * weekdays and the range before; the fee by payment status, the biggest
     * dues and the latest payments; the payroll's progress; what is coming up;
     * the demo statuses and the latest enquiries; the schools and topics of
     * the support tickets.
     */
    public function test_every_section_has_more_detail(): void
    {
        $html = Livewire::test(AnalyticsWithMoreDetail::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        foreach ([
            // Overview
            'This Month vs Last Month', 'New schools 1 2 -50%', 'New students 6 3 +100%', 'Fee collected ₹1,000 ₹2,000 -50%',
            'Platform Health', 'Schools active 75% · 3 of 4', 'Needs Attention', 'Demo requests to answer 2', 'Unpaid for Sep 2026 1',
            // Schools
            'Active Schools 3', 'Students per Teacher 6.3', 'Schools without Students 1', 'Schools by State', 'Uttar Pradesh 3 · 75%',
            'Schools by Board', 'CBSE', 'Newest Schools', 'Beta School', 'Joined 02 Oct 2026 · 20 students · 2 teachers', 'Inactive',
            // Website
            'Visits Today 4', 'Return Visits 28', 'vs Range Before +33.3%', 'Visits by Weekday',
            // Fee
            'Schools by Payment', 'Paid in full 1', 'Highest Dues', '₹4,000', '60% paid', 'Latest Payments', 'Upi · #R-7', 'Average Payment ₹1,000',
            // Payroll
            'Sep 2026 Payroll Progress', '66.7% paid', 'Left to pay ₹15,000', 'People paid 2 / 3',
            // Calendar
            'Coming Up', 'Call with Alpha', 'Oct 2026 by Kind',
            // Enquiries
            'Demo Requests by Status', '1 demo meeting booked ahead', 'Top Cities', 'Lucknow', 'Latest Enquiries', 'Asha Demo · Alpha',
            // Support
            'Last Month 3', 'Oldest Waiting 5 d', 'Schools Writing Most', '2 waiting', 'Topics', 'Latest Tickets', 'Fee · Receipt is wrong',
        ] as $seen) {
            $this->assertStringContainsString($seen, $text, "Analytics shows \"{$seen}\"");
        }
        $this->assertMatchesRegularExpression('/<div[^>]*wire:ignore[^>]*>\s*<canvas id="feeCountChart"/', $html);
        $this->assertDoesNotMatchRegularExpression('/\bCredit\b/i', $text);
    }

    public function test_schools_are_grouped_by_their_own_fields(): void
    {
        \Illuminate\Support\Facades\Schema::create('organizations', function (\Illuminate\Database\Schema\Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('state')->nullable();
            $t->string('education_board')->nullable();
            $t->string('medium')->nullable();
            $t->timestamps();
        });
        foreach ([['Bihar', 'cbse'], ['bihar', 'CBSE'], ['Uttar pradesh', 'State Board'], [null, ''], ['  ', 'icse']] as [$state, $board]) {
            \Illuminate\Support\Facades\DB::table('organizations')->insert(['name' => 'S', 'state' => $state, 'education_board' => $board]);
        }

        $group = fn (string $column) => (fn () => $this->groupSchools($column))->call(new Analytics());

        $this->assertSame([['label' => 'Bihar', 'count' => 2], ['label' => 'Uttar Pradesh', 'count' => 1], ['label' => 'Not set', 'count' => 2]], $group('state'));
        $this->assertSame([['label' => 'CBSE', 'count' => 2], ['label' => 'State Board', 'count' => 1], ['label' => 'ICSE', 'count' => 1], ['label' => 'Not set', 'count' => 1]], $group('education_board'));
    }

    public function test_the_super_admin_sidebar_scrollbar_is_a_hairline_and_page_boxes_get_the_slim_bar(): void
    {
        $html = view('admin-components.super-admin-sidebar')->render();

        $this->assertStringContainsString('#sidebar-nav::-webkit-scrollbar { width: 3px; }', $html);
        $this->assertStringContainsString('#main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar { width: 8px; height: 8px; }', $html);
    }
}

/** The page with fixed figures instead of the database's. */
class AnalyticsWithFigures extends Analytics
{
    public function mount(): void
    {
        $this->visitFrom = '2026-09-12';
        $this->visitTo   = '2026-10-11';

        $this->overviewStats = [
            'totalSchools' => 4, 'activeSchools' => 3, 'inactiveSchools' => 1, 'newSchoolsThisMonth' => 1,
            'totalStudents' => 50, 'activeStudents' => 45, 'totalTeachers' => 8, 'activeTeachers' => 7,
            'avgStudentsPerSchool' => 12.5, 'studentsThisMonth' => 6, 'studentsLastMonth' => 3, 'studentGrowthPct' => 100,
        ];
        $this->topByStudents = [['rank' => 1, 'id' => 1, 'name' => 'Alpha', 'logo' => null, 'students' => 30, 'teachers' => 4]];
        $this->topByTeachers = [['rank' => 1, 'id' => 1, 'name' => 'Alpha', 'logo' => null, 'students' => 30, 'teachers' => 4]];
        $this->schoolBuckets = ['<500' => 4, '500+' => 0, '1000+' => 0, '1500+' => 0, '2000+' => 0];
        $this->monthlyRegistrations = ['labels' => [], 'students' => [], 'teachers' => [], 'schools' => []];
        $this->genderSplit = ['male' => 20, 'female' => 25, 'other' => 0, 'unknown' => 5];
        $this->ratingStats = ['total' => 2, 'avg' => 4.5, 'fiveStar' => 1, 'distribution' => [
            5 => ['count' => 1, 'pct' => 50], 4 => ['count' => 1, 'pct' => 50], 3 => ['count' => 0, 'pct' => 0],
            2 => ['count' => 0, 'pct' => 0], 1 => ['count' => 0, 'pct' => 0],
        ]];
        $this->creditStats = ['total' => 9];

        $this->websiteStats = [
            'totalVisits' => 40, 'uniqueVisitors' => 12, 'pagesTracked' => 2, 'avgPerDay' => 1,
            'topPage' => 'About', 'from' => '12 Sep 2026', 'to' => '11 Oct 2026', 'days' => 30.999999999988,
        ];
        $this->websitePages = [['page' => 'About', 'visits' => 30, 'uniques' => 10, 'pct' => 75.0]];
        $this->websiteDaily = ['labels' => [], 'visits' => [], 'uniques' => []];

        $this->feeStats = [
            'totalSchools' => 2, 'totalStudents' => 10, 'totalFeeToCollect' => 10000, 'totalCollected' => 6000,
            'totalRemaining' => 4000, 'avgFeePerStudent' => 1000, 'collectionRate' => 60.0,
            'collectedThisMonth' => 1000, 'collectedLastMonth' => 2000, 'paidRecords' => 6, 'unpaidRecords' => 4,
        ];
        $this->feeMonthly = ['labels' => [], 'collected' => [], 'payments' => []];
        $this->feeModes = ['labels' => ['Cash'], 'data' => [6000.0]];
        $this->schoolFeeList = [['name' => 'Alpha', 'toCollect' => 10000, 'collected' => 6000, 'remaining' => 4000, 'pct' => 60.0]];

        $this->payrollStats = [
            'totalEmployees' => 3, 'totalMonthlySalaryBill' => 45000, 'payableMonth' => 'Sep 2026',
            'paidThisMonth' => 2, 'pendingThisMonth' => 1, 'totalPaidAmount' => 30000,
            'byType' => ['team' => ['count' => 3, 'salary' => 45000]],
            'avgSalary' => 15000, 'highestSalary' => 20000, 'lowestSalary' => 10000, 'paidThisYear' => 90000,
        ];
        $this->payrollMonthly = ['labels' => [], 'paid' => []];
        $this->payrollModes = ['labels' => ['Bank Transfer'], 'data' => [30000.0]];
        $this->payrollPending = [['name' => 'Ravi', 'type' => 'team', 'designation' => 'Sales', 'salary' => 15000]];
        $this->payrollRecent = [];

        $this->enquiryStats = [
            'demo'     => ['total' => 6, 'pending' => 2, 'replied' => 4, 'thisMonth' => 1, 'thisWeek' => 0, 'replyRate' => 66.7],
            'contact'  => ['total' => 5, 'pending' => 3, 'replied' => 2, 'thisMonth' => 2, 'thisWeek' => 1, 'replyRate' => 40.0],
            'combined' => ['total' => 11, 'pending' => 5, 'replied' => 6],
        ];
        $this->enquiryMonthly = ['labels' => [], 'demo' => [], 'contact' => []];

        $this->supportStats = ['total' => 8, 'pending' => 2, 'replied' => 6, 'thisMonth' => 1, 'thisWeek' => 0, 'avgPerMonth' => 1.5];
        $this->supportMonthly = ['labels' => [], 'total' => [], 'replied' => []];

        $this->calendarStats = [
            'month' => 'Oct 2026', 'meetings' => 4, 'meetingsLast' => 1, 'tasks' => 5, 'tasksDone' => 3, 'tasksPct' => 60.0,
            'events' => 1, 'eventsLast' => 0, 'today' => 3, 'todayMeetings' => 1, 'todayTasks' => 2, 'todayEvents' => 0,
            'overdue' => 2, 'next7' => 4, 'windowTotal' => 16, 'windowTasks' => 10, 'windowDone' => 6,
            'windowOverdue' => 2, 'windowToDo' => 2, 'windowPct' => 60.0,
        ];
        $this->calendarMonthly = ['meetings' => [1, 4], 'tasks' => [5, 5], 'events' => [0, 1]];
    }

    public function getVisitMinDateProperty(): string
    {
        return '2026-07-14';
    }
}

/** The figures above with every section's extra detail too (2026-11-19). */
class AnalyticsWithMoreDetail extends AnalyticsWithFigures
{
    public function mount(): void
    {
        parent::mount();

        $this->schoolExtra = ['newLastMonth' => 2, 'avgTeachers' => 2.0, 'studentsPerTeacher' => 6.3, 'emptySchools' => 1, 'largest' => 30, 'teachersThisMonth' => 1, 'teachersLastMonth' => 0];
        $this->schoolGroups = [
            'state'  => [['label' => 'Uttar Pradesh', 'count' => 3], ['label' => 'Not set', 'count' => 1]],
            'board'  => [['label' => 'CBSE', 'count' => 4]],
            'medium' => [],
        ];
        $this->newestSchools = [['name' => 'Beta School', 'logo' => null, 'active' => false, 'students' => 20, 'teachers' => 2, 'joined' => '02 Oct 2026']];
        $this->websiteExtra = ['today' => 4, 'prevVisits' => 30, 'changePct' => 33.3, 'returnVisits' => 28, 'perVisitor' => 3.3,
            'busiest' => ['label' => 'Mon, 05 Oct', 'visits' => 9], 'weekdays' => ['Mon' => 9, 'Tue' => 5, 'Wed' => 5, 'Thu' => 6, 'Fri' => 5, 'Sat' => 5, 'Sun' => 5]];
        $this->feeStats['schoolsDue'] = 1;
        $this->feeExtra = ['status' => ['full' => 1, 'part' => 1, 'none' => 0], 'topDues' => [['name' => 'Alpha', 'remaining' => 4000.0, 'expected' => 10000.0, 'pct' => 60.0]],
            'receipts' => 6, 'avgPayment' => 1000, 'paymentsThisMonth' => 1, 'largestPayment' => 2000.0];
        $this->feeRecent = [['name' => 'Alpha', 'amount' => 1000.0, 'mode' => 'Upi', 'date' => '05 Oct 2026', 'number' => 'R-7']];
        $this->payrollExtra = ['active' => 3, 'inactive' => 0, 'paymentsThisYear' => 9, 'paidPct' => 66.7, 'leftToPay' => 15000, 'avgMonth' => 30000];
        $this->calendarUpcoming = [['type' => 'meeting', 'title' => 'Call with Alpha', 'when' => 'Today · 4:00 PM', 'where' => 'Ravi']];
        $this->enquiryExtra = ['status' => ['pending' => 2, 'booked' => 1, 'completed' => 3, 'cancelled' => 0], 'upcomingMeetings' => 1,
            'demoLastMonth' => 2, 'contactLastMonth' => 1, 'cities' => [['label' => 'Lucknow', 'count' => 4]]];
        $this->enquiryRecent = [['kind' => 'Demo', 'name' => 'Asha', 'school' => 'Alpha', 'status' => 'Booked', 'replied' => false, 'ago' => '1 hour ago']];
        $this->supportExtra = ['lastMonth' => 3, 'oldestPendingDays' => 5, 'schools' => [['name' => 'Alpha', 'count' => 5, 'pending' => 2]], 'topics' => [['label' => 'Fee', 'count' => 5]]];
        $this->supportRecent = [['school' => 'Alpha', 'topic' => 'Fee', 'text' => 'Receipt is wrong', 'replied' => false, 'ago' => '2 days ago']];
    }
}
