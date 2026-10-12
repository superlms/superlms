<?php

namespace Tests\Feature;

use App\Livewire\Components\NavBar;
use App\Livewire\SuperAdmin\Reports;
use App\Livewire\SuperAdmin\Schools;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin Reports: revenue is the money received each day (receipts — an
 * Amount Received for many students is one row, part payments included),
 * Credit is gone from Reports and the page search, Today's figures are not
 * greyed out, and the month list runs April 2026 → this month.
 */
class SuperAdminReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('r', 32))]);
        Carbon::setTestNow('2026-10-12 10:45:00');

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('super_admin_fee_structures', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('fee_type')->default('per_student');
            $t->string('academic_year')->default('2026-2027');
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();

        DB::table('organizations')->insert(['id' => 9, 'name' => 'ADARSH SATYADEV INTER COLLEGE']);
        DB::table('super_admin_fee_structures')->insert(['id' => 3, 'organization_id' => 9]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(): void
    {
        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 1;
        $user->role = 'super-admin';
        $this->actingAs($user);
    }

    private function receipt(float $amount, string $date, array $more = []): void
    {
        DB::table('super_admin_fee_receipts')->insert($more + [
            'organization_id' => 9, 'super_admin_fee_structure_id' => 3, 'amount' => $amount,
            'academic_year' => '2026-2027', 'payment_mode' => 'cash', 'payment_date' => $date,
            'label' => '215 students', 'items' => 215, 'receipt_number' => 'SARC-9-2026-00180 – SARC-9-2026-00394',
            'created_at' => $date . ' 10:39:00',
        ]);
    }

    public function test_revenue_is_the_money_received_and_one_amount_is_one_row(): void
    {
        $this->receipt(15000, '2026-10-12');                          // 214 paid + 1 part paid: all of it
        $this->receipt(500, '2026-10-03', ['label' => 'Apr 2026', 'items' => 1]);
        $this->receipt(-20, '2026-10-12', ['label' => 'Zoya', 'items' => 1, 'created_at' => '2026-10-12 11:00:00']);

        $page = Livewire::test(Reports::class);
        $this->assertEquals(14980, $page->get('today')['revenue']);
        $this->assertEquals(15480, $page->get('totals')['revenue']);
        $this->assertEquals(15480, $page->get('snapshot')['revenue']);

        $page->call('openDetail', '2026-10-12', 'revenue');
        $rows = $page->get('detailRows');
        $this->assertCount(2, $rows);
        $this->assertSame('ADARSH SATYADEV INTER COLLEGE', $rows[1]['title']);
        $this->assertSame('Cash · 215 students', $rows[1]['subtitle']);
        $this->assertSame('SARC-9-2026-00180 – SARC-9-2026-00394', $rows[1]['meta']);
        $this->assertEquals(15000, $rows[1]['amount']);
        $this->assertSame('10:39 AM', $rows[1]['time']);
    }

    public function test_one_amount_received_shows_its_full_sum(): void
    {
        $this->receipt(15000, '2026-10-12');

        $page = Livewire::test(Reports::class);
        $this->assertEquals(15000, $page->get('today')['revenue']);
        $this->assertStringContainsString('₹15,000', $page->html());
    }

    public function test_credit_is_gone_from_reports(): void
    {
        $page = Livewire::test(Reports::class);

        $this->assertArrayNotHasKey('credit', $page->get('today'));
        $this->assertArrayNotHasKey('credit', $page->get('snapshot'));
        $this->assertArrayNotHasKey('credit', $page->get('totals'));
        $this->assertStringNotContainsString('Credit', strip_tags($page->html()));

        // Its drill-down no longer opens.
        $page->call('openDetail', '2026-10-12', 'credit')->assertSet('detailOpen', false);
    }

    public function test_todays_figures_are_not_greyed_out(): void
    {
        $html = Livewire::test(Reports::class)->html();

        $today = substr($html, strpos($html, '>Today</h2>'), strpos($html, 'Click a figure') - strpos($html, '>Today</h2>'));
        $this->assertStringContainsString('New Students', $today);
        $this->assertStringNotContainsString('text-gray-300', $today);
    }

    public function test_the_day_by_day_month_filter_runs_from_april_2026_to_this_month(): void
    {
        $page = Livewire::test(Reports::class);

        $this->assertSame(
            ['2026-04', '2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'],
            array_keys($page->get('monthOptions'))
        );
        $this->assertSame('2026-10', $page->get('selectedMonth'));
        $this->assertStringContainsString('aria-label="Day by day month"', $page->html());

        $this->receipt(700, '2026-08-14');
        $page->call('setMonth', '2026-08');
        $this->assertSame('2026-08', $page->get('selectedMonth'));
        $this->assertCount(31, $page->get('rows'));
        $this->assertEquals(700, $page->get('totals')['revenue']);

        // A month outside the list is ignored.
        $page->call('setMonth', '2026-11')->assertSet('selectedMonth', '2026-08');

        // Later on the list carries on past March.
        Carbon::setTestNow('2027-05-02 09:00:00');
        $this->assertSame('2027-05', array_key_last(Livewire::test(Reports::class)->get('monthOptions')));
        $this->assertSame('2026-04', array_key_first(Livewire::test(Reports::class)->get('monthOptions')));
    }

    public function test_the_page_search_does_not_find_credit(): void
    {
        $this->signIn();
        $nav = new NavBar();

        $nav->search = 'cred';
        $nav->updatedSearch();
        $this->assertSame([], $nav->searchResults);

        $nav->search = 'Fee';
        $nav->updatedSearch();
        $this->assertContains('Fees', array_column($nav->searchResults, 'title'));
    }

    public function test_the_add_school_form_is_in_the_add_student_look(): void
    {
        $this->signIn();
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::table('organizations', function (Blueprint $t) {
            foreach (['email', 'mobile_number', 'education_board', 'medium', 'logo', 'school_code', 'status'] as $c) {
                $t->string($c)->nullable();
            }
        });

        $page = Livewire::test(Schools::class)->call('openModal');
        $text = preg_replace('/\s+/', ' ', strip_tags($page->html()));

        $this->assertStringContainsString('New School Step 1 of 2 · a login password is generated and emailed to the school on save', $text);
        foreach (['School Logo', 'School Name', 'School Code', 'Email', 'Mobile', 'Education Board', 'Medium', 'State',
                  'Serial Number', 'Affiliation No', 'UDISE Number', 'Address', 'Cancel', 'Next: Select Modules →'] as $field) {
            $this->assertStringContainsString($field, $text);
        }
        // The Add Student form's inputs and dark save button.
        $this->assertStringContainsString('px-3.5 py-2.5 border border-gray-300 rounded-md', $page->html());
        $this->assertStringContainsString('bg-gray-900 hover:bg-gray-800', $page->html());
    }
}
