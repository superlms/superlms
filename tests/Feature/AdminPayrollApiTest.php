<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminPayrollController;
use App\Models\Admin\AdminEmployee;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Payroll runs the panel's own Payroll rules: salary cut by
 * the month's absences, payable only once the month is over, staff marked
 * for a date (never teachers), and every teacher given a payroll row.
 */
class AdminPayrollApiTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();

        // The panel reads months with MySQL's DATE_FORMAT; teach SQLite it.
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($d, $f) {
            return $d ? date(strtr($f, ['%Y' => 'Y', '%m' => 'm', '%d' => 'd']), strtotime($d)) : null;
        }, 2);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id');
            $t->string('phone')->nullable(); $t->date('date_of_joining')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->date('attendance_date'); $t->tinyInteger('status'); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('teacher_detail_id')->nullable();
            $t->string('name'); $t->string('email')->nullable(); $t->string('mobile')->nullable(); $t->string('designation')->nullable();
            $t->string('type'); $t->decimal('salary', 12, 2)->default(0); $t->string('address')->nullable();
            $t->string('bank_name')->nullable(); $t->string('bank_account_no')->nullable(); $t->string('bank_holder_name')->nullable();
            $t->string('bank_branch')->nullable(); $t->string('bank_ifsc')->nullable(); $t->string('photo')->nullable();
            $t->boolean('is_active')->default(true); $t->date('joining_date')->nullable(); $t->timestamps();
        });
        Schema::create('admin_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('admin_employee_id'); $t->unsignedBigInteger('organization_id');
            $t->date('date'); $t->string('status'); $t->string('note')->nullable(); $t->timestamps();
        });
        Schema::create('admin_salary_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('admin_employee_id'); $t->unsignedBigInteger('organization_id');
            $t->string('month'); $t->decimal('amount', 12, 2); $t->string('payment_mode')->nullable(); $t->string('paid_by')->nullable();
            $t->string('status')->nullable(); $t->date('payment_date')->nullable(); $t->string('transaction_id')->nullable();
            $t->string('remark')->nullable(); $t->timestamps();
        });

        Auth::setUser(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    private function api(): AdminPayrollController
    {
        return app(AdminPayrollController::class);
    }

    private function staff(string $name, string $type, float $salary): AdminEmployee
    {
        return AdminEmployee::create(['organization_id' => $this->org, 'name' => $name, 'type' => $type, 'salary' => $salary]);
    }

    public function test_every_teacher_gets_a_payroll_row(): void
    {
        $u = DB::table('users')->insertGetId(['name' => 'Meera', 'role' => 'teacher', 'organization_id' => $this->org]);
        DB::table('teacher_details')->insert(['user_id' => $u, 'organization_id' => $this->org]);
        $this->staff('Ravi', 'management', 50000);

        $d = $this->api()->employees(new Request())->getData(true)['data'];

        // Management first, the teacher last — the panel's order.
        $this->assertSame(['Ravi', 'Meera'], array_column($d['employees'], 'name'));
        $this->assertSame(1, $d['stats']['teacher']);
    }

    public function test_salary_is_cut_by_absences_and_paid_only_after_the_month(): void
    {
        $e = $this->staff('Ravi', 'employee', 31000); // ₹1,000 a day in a 31-day month
        $month = now()->subMonthNoOverflow()->format('Y-m');
        $days = Carbon::parse($month . '-01')->daysInMonth;
        $e->update(['salary' => $days * 1000]);

        foreach (['05' => 'absent', '06' => 'absent', '07' => 'half_day', '08' => 'present'] as $day => $st) {
            DB::table('admin_attendances')->insert(['admin_employee_id' => $e->id, 'organization_id' => $this->org, 'date' => "$month-$day", 'status' => $st]);
        }

        $d = $this->api()->salary(new Request(['month' => $month]))->getData(true)['data'];
        $row = $d['employees'][0];
        $this->assertTrue($d['can_pay']);
        $this->assertSame(2, $row['breakdown']['absent']);
        $this->assertSame(1, $row['breakdown']['half_day']);
        $this->assertSame((float) ($days * 1000 - 2500), (float) $row['breakdown']['payable']);

        $paid = $this->api()->pay(new Request([
            'month' => $month, 'amount' => $row['breakdown']['payable'], 'mode' => 'bank_transfer', 'paid_by' => 'Head', 'date' => now()->toDateString(),
        ]), $e->id);
        $this->assertSame(200, $paid->status());
        $this->assertSame(1, DB::table('admin_salary_payments')->count());

        // This month is not over yet.
        $early = $this->api()->pay(new Request([
            'month' => now()->format('Y-m'), 'amount' => 10, 'mode' => 'cash', 'paid_by' => 'Head', 'date' => now()->toDateString(),
        ]), $e->id);
        $this->assertSame(422, $early->status());
    }

    public function test_marking_a_date_skips_teachers_and_overwrites(): void
    {
        $e = $this->staff('Ravi', 'employee', 1000);
        $t = $this->staff('Meera', 'teacher', 1000);
        $date = now()->subDay()->toDateString();

        $res = $this->api()->markAttendance(new Request(['date' => $date, 'marks' => [
            ['id' => $e->id, 'status' => 'present'], ['id' => $t->id, 'status' => 'absent'],
        ]]));
        $this->assertSame(200, $res->status());
        $this->assertSame(1, DB::table('admin_attendances')->count());

        $this->api()->markAttendance(new Request(['date' => $date, 'marks' => [['id' => $e->id, 'status' => 'leave']]]));
        $this->assertSame('leave', DB::table('admin_attendances')->value('status'));

        $d = $this->api()->attendanceDate(new Request(['date' => $date]))->getData(true)['data'];
        $byName = collect($d['employees'])->keyBy('name');
        $this->assertSame('leave', $byName['Ravi']['status']);
        $this->assertFalse($byName['Meera']['markable']);

        // A day still to come cannot be marked.
        $future = $this->api()->markAttendance(new Request(['date' => now()->addDay()->toDateString(), 'marks' => [['id' => $e->id, 'status' => 'present']]]));
        $this->assertNotSame(200, $future->status());
    }
}
