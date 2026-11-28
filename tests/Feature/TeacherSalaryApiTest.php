<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\TeacherSalaryController;
use App\Models\Admin\AdminEmployee;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The teacher app's Salaries: a teacher sees their own Payroll account — the
 * months, what each came to and what was paid — and nobody else's; a teacher
 * the school has not set up yet gets an empty account; only a teacher may ask.
 */
class TeacherSalaryApiTest extends TestCase
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
    }

    /** A teacher at the school, signed in; with a Payroll row when $salary is given. */
    private function signInTeacher(string $name, ?float $salary): array
    {
        $user = User::forceCreate(['name' => $name, 'role' => 'teacher', 'organization_id' => $this->org]);
        $td = DB::table('teacher_details')->insertGetId(['user_id' => $user->id, 'organization_id' => $this->org]);
        $emp = $salary === null ? null : AdminEmployee::create([
            'organization_id' => $this->org, 'teacher_detail_id' => $td, 'name' => $name,
            'type' => 'teacher', 'salary' => $salary, 'designation' => 'PGT',
        ]);
        Auth::setUser($user);

        return [$user, $emp];
    }

    private function salaries(): array
    {
        $res = app(TeacherSalaryController::class)->index();
        $this->assertSame(200, $res->status(), json_encode($res->getData(true)));

        return $res->getData(true)['data'];
    }

    public function test_a_teacher_sees_their_own_months_and_what_was_paid(): void
    {
        [, $other] = $this->signInTeacher('Ravi', 18000);
        [, $emp] = $this->signInTeacher('Meera', 30000);

        DB::table('admin_salary_payments')->insert([
            ['admin_employee_id' => $emp->id, 'organization_id' => $this->org, 'month' => now()->format('Y-m'),
             'amount' => 5000, 'status' => 'paid', 'payment_date' => now()->toDateString(), 'paid_by' => 'Head', 'payment_mode' => 'cash'],
            // Someone else's pay never shows.
            ['admin_employee_id' => $other->id, 'organization_id' => $this->org, 'month' => now()->format('Y-m'),
             'amount' => 999, 'status' => 'paid', 'payment_date' => now()->toDateString(), 'paid_by' => 'Head', 'payment_mode' => 'cash'],
        ]);

        $data = $this->salaries();

        $this->assertSame('Meera', $data['employee']['name']);
        $this->assertSame(30000.0, (float) $data['employee']['salary']);
        $this->assertNotEmpty($data['months']);
        $this->assertSame(now()->format('Y-m'), $data['months'][0]['ym']); // this month on top
        $this->assertSame(5000.0, (float) $data['months'][0]['paid']);
        $this->assertSame(5000.0, (float) $data['paid']);
        $this->assertSame('Head', $data['months'][0]['payments'][0]['from']);
    }

    public function test_a_teacher_without_a_payroll_row_gets_an_empty_account(): void
    {
        $this->signInTeacher('Asha', null);

        $data = $this->salaries();

        $this->assertNull($data['employee']);
        $this->assertSame([], $data['months']);
        $this->assertSame(0.0, (float) $data['paid']);
    }

    public function test_only_a_teacher_may_ask(): void
    {
        Auth::setUser(User::forceCreate(['name' => 'Kid', 'role' => 'student', 'organization_id' => $this->org]));

        $this->assertSame(403, app(TeacherSalaryController::class)->index()->status());
    }
}
