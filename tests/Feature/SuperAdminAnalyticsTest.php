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

        preg_match_all('/<h2[^>]*>([^<]+)<\/h2>/', $html, $m);
        $this->assertSame(
            ['Key Metrics', 'Schools', 'Website', 'Fee', 'Payroll', 'Enquiries', 'Support'],
            array_map('trim', $m[1])
        );

        // No tab strip, and nothing of Credit on the page.
        $this->assertStringNotContainsString('setTab(', $html);
        $this->assertDoesNotMatchRegularExpression('/\bCredit\b/i', strip_tags($html));
        $this->assertStringNotContainsString('creditMonthlyChart', $html);

        // Every section's charts are there at once.
        foreach (['overviewMonthlyChart', 'schoolsMonthlyChart', 'genderSplitChart', 'websiteVisitsChart',
                  'feeMonthlyChart', 'feeModeChart', 'payrollMonthlyChart', 'payrollModeChart',
                  'enquiryMonthlyChart', 'supportMonthlyChart'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html);
        }

        // Figures from each section.
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Avg / School 12.5', $text);
        $this->assertStringContainsString('Most Visited About', $text);
        $this->assertStringContainsString('Collected ₹6,000', $text);
        $this->assertStringContainsString('Monthly Bill ₹45,000', $text);
        $this->assertStringContainsString('Total Pending 5', $text);
        $this->assertStringContainsString('Reply Rate 75%', $text);
        // A fractional day count from Carbon reads as whole days.
        $this->assertStringContainsString('· 30 days', $text);
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
    }

    public function getVisitMinDateProperty(): string
    {
        return '2026-07-14';
    }
}
