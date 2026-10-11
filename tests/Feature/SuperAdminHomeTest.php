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

    public function test_the_super_admin_fee_is_counted_by_payment_date_over_twelve_months(): void
    {
        $this->signIn();
        $this->table('super_admin_fee_payments', function (Blueprint $t) {
            $t->unsignedBigInteger('organization_id');
            $t->decimal('amount', 12, 2);
            $t->date('payment_date')->nullable();
            $t->boolean('is_paid')->default(false);
        });

        $pay = fn ($org, $amount, $date, $paid) => DB::table('super_admin_fee_payments')->insert(['organization_id' => $org, 'amount' => $amount, 'payment_date' => $date, 'is_paid' => $paid]);
        $pay(1, 1000, '2026-10-05', true);
        $pay(1, 500, '2026-09-20', true);
        $pay(2, 300, '2025-09-01', true);   // before the 12 months
        $pay(2, 2000, null, false);

        $fee = $this->helper('superAdminFee');

        $this->assertEquals(3800, $fee['total']);
        $this->assertEquals(1800, $fee['collected']);
        $this->assertEquals(2000, $fee['pending']);
        $this->assertSame(47.4, $fee['pct']);
        $this->assertEquals(1000, $fee['this_month']);
        $this->assertEquals(500, $fee['last_month']);
        $this->assertEquals(1500, $fee['year_total']);
        $this->assertCount(12, $fee['labels']);
        $this->assertSame('Nov 25', $fee['labels'][0]);
        $this->assertEquals(1000, end($fee['data']));
        $this->assertSame(2, $fee['schools']);
        $this->assertSame(1, $fee['unpaid_records']);
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
}
