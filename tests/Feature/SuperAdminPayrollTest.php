<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Listing;
use App\Livewire\SuperAdmin\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin Payroll in the school Payroll's look and logic (user's ask of
 * 2026-10-12): the list with this month's attendance, one Mark Attendance
 * panel for everyone (Present / Absent / Half / Holiday, a blank row unmarked),
 * Mark Salary as one person's account (first absent a paid leave, a payment
 * row each time) and Payments filtered by type → person → month. Also School
 * Listing's view in the Enquiry view's look and its status as plain text.
 */
class SuperAdminPayrollTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('s', 32))]);
        Carbon::setTestNow('2026-10-12 10:00:00');

        // Saves tell the super admins (ActivityNotifier reads users) — never a real push.
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'role'] as $c) {
                $t->string($c)->nullable();
            }
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('permissions')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->string('notifiable_type');
            $t->unsignedBigInteger('notifiable_id');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public function sendToUser(...$a) {}
            public function notifyUserIds(...$a) { return false; }
        });

        Schema::create('super_admin_employees', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            foreach (['email', 'mobile', 'designation', 'address', 'bank_name', 'bank_account_no', 'bank_holder_name', 'bank_branch', 'bank_ifsc', 'photo'] as $c) {
                $t->string($c)->nullable();
            }
            $t->string('type')->default('user');
            $t->decimal('salary', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->date('joining_date')->nullable();
            $t->timestamps();
        });
        Schema::create('super_admin_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('super_admin_employee_id');
            $t->date('date');
            $t->string('status')->default('present');
            $t->string('note')->nullable();
            $t->timestamps();
            $t->unique(['super_admin_employee_id', 'date']);
        });
        Schema::create('super_admin_salary_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('super_admin_employee_id');
            $t->decimal('amount', 10, 2);
            $t->string('month');
            $t->string('payment_mode')->default('cash');
            $t->string('paid_by')->nullable();
            $t->string('status')->default('pending');
            $t->date('payment_date')->nullable();
            $t->string('transaction_id')->nullable();
            $t->text('remark')->nullable();
            $t->string('receipt_number')->nullable();
            $t->timestamps();
        });

        // The page filters months with MySQL's DATE_FORMAT; give SQLite one.
        $pdo = DB::connection()->getPdo();
        $dateFormat = fn ($date, $format) => $date === null ? null
            : date(str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], $format), strtotime($date));
        method_exists($pdo, 'createFunction')
            ? $pdo->createFunction('DATE_FORMAT', $dateFormat, 2)
            : $pdo->sqliteCreateFunction('DATE_FORMAT', $dateFormat, 2);

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 1;
        $user->role = 'super-admin';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function text(string $html): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($html));
    }

    private function employee(string $name, string $type, float $salary, array $more = []): int
    {
        return DB::table('super_admin_employees')->insertGetId($more + [
            'name' => $name, 'type' => $type, 'salary' => $salary, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function mark(int $emp, string $date, string $status): void
    {
        DB::table('super_admin_attendances')->insert([
            'super_admin_employee_id' => $emp, 'date' => $date, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_list_shows_type_and_this_months_attendance(): void
    {
        $asha = $this->employee('Asha Verma', 'counsellor', 31000, ['designation' => 'Senior Counsellor', 'mobile' => '9876500001']);
        $this->mark($asha, '2026-10-01', 'present');
        $this->mark($asha, '2026-10-02', 'half_day');
        $this->mark($asha, '2026-10-03', 'absent');
        $this->mark($asha, '2026-10-04', 'holiday');

        $html = Livewire::test(Payroll::class)->html();
        $text = $this->text($html);

        $this->assertStringContainsString('Employee Mobile Attendance · Oct Salary Actions', $text);
        $this->assertStringContainsString('Asha Verma Counsellor · Senior Counsellor 9876500001 1.5 / 3 ₹31,000', $text);
        $this->assertStringNotContainsString('rounded-full font-medium border capitalize', $html);
    }

    public function test_mark_attendance_is_one_panel_for_everyone(): void
    {
        $asha = $this->employee('Asha Verma', 'counsellor', 31000);
        $ravi = $this->employee('Ravi Kumar', 'user', 20000);
        $neha = $this->employee('Neha Singh', 'team', 25000);
        $this->mark($neha, '2026-10-12', 'present');

        $page = Livewire::test(Payroll::class)->set('activeTab', 'attendance')->call('openMarkPanel')
            ->assertSet('showMarkPanel', true)
            ->assertSet('panelDate', '2026-10-12')
            ->assertSet('panelExisting', true);
        $panel = $this->text($page->html());
        $this->assertStringContainsString('Mark Attendance Monday, 12 Oct 2026', $panel);
        $this->assertStringContainsString('All present All absent All holiday Clear', $panel);

        // Asha present with a remark, Ravi half day, Neha's mark taken off.
        $page->set("panelRows.$asha.status", 'present')->set("panelRows.$asha.remark", 'On a school visit')
            ->set("panelRows.$ravi.status", 'half_day')
            ->set("panelRows.$neha.status", '')
            ->call('saveMarkPanel')
            ->assertSet('showMarkPanel', false)
            ->assertSet('attendanceDate', '2026-10-12');

        $rows = DB::table('super_admin_attendances')->orderBy('super_admin_employee_id')->get();
        $this->assertSame([[$asha, 'present', 'On a school visit'], [$ravi, 'half_day', null]],
            $rows->map(fn ($r) => [(int) $r->super_admin_employee_id, $r->status, $r->note])->all());

        // The date view lists everyone that day, the unmarked one too.
        $day = $this->text($page->html());
        $this->assertStringContainsString('Monday, 12 Oct 2026', $day);
        $this->assertStringContainsString('Asha Verma Counsellor On a school visit Present', $day);
        $this->assertStringContainsString('Neha Singh Team Not marked', $day);

        // Everyone on Holiday needs the remark.
        $page->call('openMarkPanel');
        foreach ([$asha, $ravi, $neha] as $id) {
            $page->set("panelRows.$id.status", 'holiday')->set("panelRows.$id.remark", '');
        }
        $page->call('saveMarkPanel')->assertSet('showMarkPanel', true);
        foreach ([$asha, $ravi, $neha] as $id) {
            $page->set("panelRows.$id.remark", 'Diwali');
        }
        $page->call('saveMarkPanel')->assertSet('showMarkPanel', false);
        $this->assertSame(3, DB::table('super_admin_attendances')->where('status', 'holiday')->where('note', 'Diwali')->count());
    }

    public function test_an_employees_attendance_is_month_calendars(): void
    {
        $asha = $this->employee('Asha Verma', 'counsellor', 31000);
        $this->mark($asha, '2026-10-01', 'present');
        $this->mark($asha, '2026-10-05', 'absent');

        $text = $this->text(Livewire::test(Payroll::class)->set('activeTab', 'attendance')
            ->set('filterAttendanceType', 'counsellor')->set('attEmpId', (string) $asha)->html());

        $this->assertStringContainsString('Asha Verma Counsellor · Apr 2026 – Mar 2027', $text);
        $this->assertStringContainsString('Working days 2 · Present 1 · Absent 1', $text);
        $this->assertStringContainsString('October 2026 W 2 · P 1 · A 1', $text);
    }

    public function test_mark_salary_is_one_persons_account_with_a_paid_leave(): void
    {
        // 31-day month, ₹31,000: a day is ₹1,000. Two absents: the first is paid.
        $asha = $this->employee('Asha Verma', 'counsellor', 31000, ['joining_date' => '2026-08-10']);
        $this->mark($asha, '2026-08-03', 'absent');
        $this->mark($asha, '2026-08-04', 'absent');
        $this->mark($asha, '2026-08-05', 'half_day');

        $page = Livewire::test(Payroll::class)->set('activeTab', 'salary')
            ->set('salaryType', 'counsellor')->set('salaryEmpId', (string) $asha);
        $text = $this->text($page->html());

        // Months from the joining month (August) to this one.
        $this->assertStringContainsString('Salary by month', $text);
        $this->assertStringContainsString('August 2026 Present 0 · Absent 2 · Half 1 ₹31,000 ₹29,500', $text);
        $this->assertStringNotContainsString('July 2026', $text);

        // A month's screen, and two payments in it.
        $page->call('openSalaryMonth', '2026-08');
        $this->assertStringContainsString('1 absent counted as a paid leave', $this->text($page->html()));
        $page->call('openSalaryPayment', '2026-08')->set('spAmount', 20000)->set('spDate', '2026-09-02')->set('spFrom', 'Super')
            ->call('saveSalaryPayment')->assertSet('showSalaryPayPanel', false);
        $page->call('openSalaryPayment', '2026-08')->set('spAmount', 9500)->set('spDate', '2026-09-20')->set('spFrom', 'Super')
            ->call('saveSalaryPayment');

        $this->assertSame(2, DB::table('super_admin_salary_payments')->where('month', '2026-08')->where('status', 'paid')->count());
        $this->assertStringContainsString('Total · 2 payments ₹29,500', $this->text($page->html()));
    }

    public function test_payments_filter_by_type_then_person(): void
    {
        $asha = $this->employee('Asha Verma', 'counsellor', 31000);
        $ravi = $this->employee('Ravi Kumar', 'user', 20000);
        foreach ([[$asha, 5000], [$ravi, 7000]] as [$id, $amount]) {
            DB::table('super_admin_salary_payments')->insert([
                'super_admin_employee_id' => $id, 'amount' => $amount, 'month' => '2026-09', 'payment_mode' => 'cash',
                'status' => 'paid', 'payment_date' => '2026-10-01', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $page = Livewire::test(Payroll::class)->set('activeTab', 'payments');
        $all = $this->text($page->html());
        $this->assertStringContainsString('Asha Verma', $all);
        $this->assertStringContainsString('Ravi Kumar', $all);

        $user = $this->text($page->set('filterPaymentType', 'user')->html());
        $this->assertStringContainsString('Ravi Kumar User Sep 2026 ₹7,000', $user);
        $this->assertStringNotContainsString('₹5,000', $user);
    }

    public function test_the_old_pay_salary_flow_still_records_a_month(): void
    {
        $asha = $this->employee('Asha Verma', 'counsellor', 30000);

        Livewire::test(Payroll::class)->set('salaryMonth', '2026-09')
            ->call('openPayModal', $asha)->assertSet('payAmount', 30000.0)
            ->set('payPaidBy', 'Super')->call('savePayment')->assertSet('showPayModal', false);

        $this->assertSame(1, DB::table('super_admin_salary_payments')->where('month', '2026-09')->count());
    }

    public function test_school_listing_view_is_the_enquiry_view_and_status_is_plain_text(): void
    {
        Schema::create('school_listings', function (Blueprint $t) {
            $t->id();
            foreach (['location', 'logo', 'name', 'email', 'mobile', 'address', 'classes', 'status', 'remark', 'payment_type'] as $c) {
                $t->string($c)->nullable();
            }
            foreach (['no_of_students', 'avg_fee', 'payment_amount'] as $c) {
                $t->decimal($c, 10, 2)->nullable();
            }
            $t->timestamps();
        });
        $id = DB::table('school_listings')->insertGetId([
            'location' => 'Jaipur', 'name' => 'Green Valley School', 'email' => 'office@gv.edu', 'mobile' => '9876500002',
            'status' => 'approved', 'payment_type' => 'monthly', 'payment_amount' => 5000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('school_listings')->insert(['location' => 'Agra', 'name' => 'Sunrise School', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        $page = Livewire::test(Listing::class);
        $html = $page->html();
        $this->assertStringContainsString('<div class="text-xs text-emerald-600 font-medium">Approved</div>', $html);
        $this->assertStringContainsString('<div class="text-xs text-amber-600 font-medium">Pending</div>', $html);
        $this->assertStringNotContainsString('rounded-full text-[11px] font-semibold', $html);

        $view = $page->call('openView', $id)->html();
        $text = $this->text($view);
        $this->assertStringContainsString('Green Valley School School listing · Approved', $text);
        $this->assertStringContainsString('Location Jaipur Email office@gv.edu Mobile 9876500002', $text);
        $this->assertStringContainsString('Status Approved Payment Monthly · ₹5,000', $text);
        $this->assertSame(3, substr_count($view, 'name="listingStatus"'));

        // Same status save.
        $page->set('statusChoice', 'rejected')->set('remarkText', 'Not interested this year')->call('saveStatus');
        $this->assertSame('rejected', DB::table('school_listings')->where('id', $id)->value('status'));
    }
}
