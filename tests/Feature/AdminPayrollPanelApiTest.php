<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminPayrollController;
use App\Models\Admin\AdminEmployee;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Payroll reaches the panel's newer screens: Mark Attendance
 * for everyone at once (a teacher's row in the Attendance module's own
 * records), a date's statuses with their remarks, the Mark Salary tab's months
 * and Add Payment, and Payments narrowed to a type.
 */
class AdminPayrollPanelApiTest extends TestCase
{
    private int $org = 9;

    protected function setUp(): void
    {
        parent::setUp();

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
            $t->date('attendance_date'); $t->tinyInteger('status'); $t->string('remarks')->nullable(); $t->unsignedBigInteger('marked_by')->nullable(); $t->timestamps();
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

    private function teacher(string $name): AdminEmployee
    {
        $u  = DB::table('users')->insertGetId(['name' => $name, 'role' => 'teacher', 'organization_id' => $this->org]);
        $td = DB::table('teacher_details')->insertGetId(['user_id' => $u, 'organization_id' => $this->org]);

        // Opening Payroll gives the teacher their row.
        $this->api()->employees(new Request());

        return AdminEmployee::where('teacher_detail_id', $td)->firstOrFail();
    }

    public function test_the_sheet_marks_everyone_a_teacher_in_the_attendance_module(): void
    {
        $staff   = AdminEmployee::create(['organization_id' => $this->org, 'name' => 'Ravi', 'type' => 'employee', 'salary' => 1000]);
        $teacher = $this->teacher('Meera');
        $date    = now()->subDay()->toDateString();

        $sheet = $this->api()->attendanceSheet(new Request(['date' => $date]))->getData(true)['data'];
        // Teachers first in the panel.
        $this->assertSame(['Meera', 'Ravi'], array_column($sheet['rows'], 'name'));
        $this->assertFalse($sheet['existing']);

        $save = $this->api()->saveAttendanceSheet(new Request(['date' => $date, 'rows' => [
            $staff->id   => ['status' => 'half_day', 'remark' => 'Doctor'],
            $teacher->id => ['status' => 'present', 'remark' => ''],
        ]]));
        $this->assertSame(200, $save->status(), json_encode($save->getData(true)));
        $this->assertSame('half_day', DB::table('admin_attendances')->value('status'));
        $this->assertSame(1, (int) DB::table('teacher_attendances')->value('status'));

        $day = $this->api()->attendanceDay(new Request(['date' => $date]))->getData(true)['data'];
        $by  = collect($day['employees'])->keyBy('name');
        $this->assertSame('present', $by['Meera']['status']);
        $this->assertSame('Doctor', $by['Ravi']['remark']);
        $this->assertSame(1, $day['counts']['half_day']);

        // Everyone on Holiday needs the remark.
        $holiday = $this->api()->saveAttendanceSheet(new Request(['date' => $date, 'rows' => [
            $staff->id   => ['status' => 'holiday', 'remark' => ''],
            $teacher->id => ['status' => 'holiday', 'remark' => ''],
        ]]));
        $this->assertSame(422, $holiday->status());
        $this->assertStringContainsString('Remark needed', $holiday->getData(true)['message']);
    }

    public function test_the_salary_account_lists_months_and_takes_a_payment_for_one(): void
    {
        $e = AdminEmployee::create(['organization_id' => $this->org, 'name' => 'Ravi', 'type' => 'employee', 'salary' => 30000]);

        $acc = $this->api()->account($e->id)->getData(true)['data'];
        $this->assertNotEmpty($acc['months']);
        $this->assertSame(now()->format('Y-m'), $acc['months'][0]['ym']); // this month on top
        $this->assertTrue($acc['months'][0]['running']);

        $month = end($acc['months'])['ym'];
        $paid = $this->api()->addAccountPayment(new Request([
            'amount' => '5000', 'date' => now()->toDateString(), 'from' => 'Head', 'remark' => 'Advance', 'month' => $month,
        ]), $e->id);
        $this->assertSame(200, $paid->status(), json_encode($paid->getData(true)));
        $this->assertSame($month, DB::table('admin_salary_payments')->value('month'));

        $after = $this->api()->account($e->id)->getData(true)['data'];
        $this->assertSame(5000.0, (float) $after['paid']);

        $future = $this->api()->addAccountPayment(new Request([
            'amount' => '10', 'date' => now()->addDays(3)->toDateString(), 'from' => 'Head',
        ]), $e->id);
        $this->assertSame(422, $future->status());
        $this->assertArrayHasKey('date', $future->getData(true)['errors']);
    }

    public function test_payments_narrow_to_a_type_and_the_list_carries_this_months_attendance(): void
    {
        $a = AdminEmployee::create(['organization_id' => $this->org, 'name' => 'Ravi', 'type' => 'employee', 'salary' => 1000]);
        $b = AdminEmployee::create(['organization_id' => $this->org, 'name' => 'Sunil', 'type' => 'management', 'salary' => 1000]);
        foreach ([$a, $b] as $e) {
            DB::table('admin_salary_payments')->insert(['admin_employee_id' => $e->id, 'organization_id' => $this->org, 'month' => now()->format('Y-m'),
                'amount' => 100, 'status' => 'paid', 'payment_mode' => 'cash', 'payment_date' => now()->toDateString()]);
        }
        DB::table('admin_attendances')->insert(['admin_employee_id' => $a->id, 'organization_id' => $this->org, 'date' => now()->toDateString(), 'status' => 'present']);

        $d = $this->api()->payments(new Request(['type' => 'management']))->getData(true)['data'];
        $this->assertSame(['Sunil'], collect($d['payments'])->pluck('employee.name')->all());

        $list = $this->api()->employees(new Request())->getData(true)['data'];
        $ravi = collect($list['employees'])->firstWhere('name', 'Ravi');
        $this->assertEquals(1, $ravi['month_attendance']['present']);
        $this->assertSame(1, $ravi['month_attendance']['working']);
    }
}
