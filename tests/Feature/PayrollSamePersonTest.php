<?php

namespace Tests\Feature;

use App\Livewire\Admin\Payroll as PayrollPage;
use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\DriverDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Payroll lists a person once: a Transport driver who is also a teacher, a
 * manager or an employee is one row carrying both types.
 */
class PayrollSamePersonTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->string('phone')->nullable();
            $t->date('date_of_joining')->nullable();
            $t->timestamps();
        });
        Schema::create('driver_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->string('phone')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('teacher_detail_id')->nullable();
            $t->unsignedBigInteger('driver_detail_id')->nullable();
            $t->string('email')->nullable();
            $t->string('mobile')->nullable();
            $t->string('designation')->nullable();
            $t->string('type');
            $t->decimal('salary', 10, 2)->default(0);
            $t->text('address')->nullable();
            $t->string('bank_name')->nullable();
            $t->string('bank_account_no')->nullable();
            $t->string('bank_holder_name')->nullable();
            $t->string('bank_branch')->nullable();
            $t->string('bank_ifsc')->nullable();
            $t->string('photo')->nullable();
            $t->boolean('is_active')->default(true);
            $t->date('joining_date')->nullable();
            $t->timestamps();
        });
        Schema::create('admin_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('admin_employee_id')->nullable();
            $t->date('date')->nullable();
            $t->string('status')->default('present');
            $t->string('note')->nullable();
            $t->timestamps();
            $t->unique(['admin_employee_id', 'date']);
        });
        Schema::create('admin_salary_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('admin_employee_id')->nullable();
            $t->string('month')->nullable();
            $t->decimal('amount', 10, 2)->default(0);
            $t->string('payment_mode')->default('cash');
            $t->string('paid_by')->nullable();
            $t->string('status')->default('pending');
            $t->date('payment_date')->nullable();
            $t->string('transaction_id')->nullable();
            $t->text('remark')->nullable();
            $t->string('receipt_number')->nullable();
            $t->timestamps();
        });
        Schema::create('employee_id_cards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('admin_employee_id')->default(0);
            $t->unsignedBigInteger('user_id')->default(0);
            $t->unsignedBigInteger('organization_id')->default(0);
            $t->string('card_number')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('teacher_detail_id');
            $t->date('attendance_date');
            $t->integer('status')->default(1);
            $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org]);

        // The page filters months with MySQL's DATE_FORMAT; give SQLite one.
        $pdo = DB::connection()->getPdo();
        $dateFormat = fn ($date, $format) => $date === null ? null
            : date(str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], $format), strtotime($date));
        method_exists($pdo, 'createFunction')
            ? $pdo->createFunction('DATE_FORMAT', $dateFormat, 2)
            : $pdo->sqliteCreateFunction('DATE_FORMAT', $dateFormat, 2);

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 999;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    private function teacher(string $name, string $phone): TeacherDetail
    {
        $user = User::create(['name' => $name, 'email' => uniqid() . '@t.test', 'mobile_number' => $phone, 'role' => 'teacher', 'organization_id' => $this->org]);

        return TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org, 'phone' => $phone]);
    }

    private function driver(string $name, string $phone): DriverDetail
    {
        $user = User::create(['name' => $name, 'email' => uniqid() . '@d.test', 'mobile_number' => $phone, 'role' => 'driver', 'organization_id' => $this->org]);

        return DriverDetail::create(['user_id' => $user->id, 'organization_id' => $this->org, 'phone' => $phone]);
    }

    private function openPage(): void
    {
        (new PayrollPage())->mount();
    }

    public function test_a_teacher_who_also_drives_is_one_row_with_both_types(): void
    {
        $teacher = $this->teacher('Ramesh Kumar', '9876543210');
        $driver  = $this->driver('Ramesh', '+91 98765 43210');

        $this->openPage();

        $rows = AdminEmployee::all();
        $this->assertCount(1, $rows);
        $this->assertSame($teacher->id, $rows[0]->teacher_detail_id);
        $this->assertSame($driver->id, $rows[0]->driver_detail_id);
        $this->assertSame(['teacher', 'driver'], $rows[0]->types());
        $this->assertSame('Ramesh Kumar', $rows[0]->name);
    }

    public function test_existing_teacher_and_driver_rows_are_joined_keeping_their_records(): void
    {
        $teacher = $this->teacher('Ramesh Kumar', '9876543210');
        $driver  = $this->driver('Ramesh Kumar', '9876543210');

        // Rows as the old payroll made them: one per record.
        $tRow = AdminEmployee::create(['organization_id' => $this->org, 'teacher_detail_id' => $teacher->id, 'name' => 'Ramesh Kumar', 'type' => 'teacher', 'salary' => 12000, 'mobile' => '9876543210']);
        $dRow = AdminEmployee::create(['organization_id' => $this->org, 'driver_detail_id' => $driver->id, 'name' => 'Ramesh Kumar', 'type' => 'driver', 'salary' => 3000, 'bank_name' => 'SBI']);
        AdminAttendance::create(['organization_id' => $this->org, 'admin_employee_id' => $dRow->id, 'date' => '2026-08-03', 'status' => 'absent']);
        AdminSalaryPayment::create(['organization_id' => $this->org, 'admin_employee_id' => $dRow->id, 'month' => '2026-08', 'amount' => 3000, 'status' => 'paid']);

        $this->openPage();

        $this->assertSame([$tRow->id], AdminEmployee::pluck('id')->all());
        $kept = AdminEmployee::find($tRow->id);
        $this->assertSame(['teacher', 'driver'], $kept->types());
        $this->assertEquals(15000, $kept->salary);
        $this->assertSame('SBI', $kept->bank_name);
        $this->assertSame(1, AdminAttendance::where('admin_employee_id', $tRow->id)->count());
        $this->assertSame(1, AdminSalaryPayment::where('admin_employee_id', $tRow->id)->count());
    }

    public function test_a_manager_who_also_drives_is_one_row(): void
    {
        $manager = AdminEmployee::create(['organization_id' => $this->org, 'name' => 'Suresh Yadav', 'type' => 'management', 'salary' => 20000, 'mobile' => '9123456789']);
        $driver  = $this->driver('Suresh', '9123456789');

        $this->openPage();

        $this->assertSame([$manager->id], AdminEmployee::pluck('id')->all());
        $this->assertSame(['management', 'driver'], $manager->fresh()->types());
        $this->assertSame($driver->id, $manager->fresh()->driver_detail_id);
    }

    public function test_different_people_sharing_a_number_stay_apart(): void
    {
        $this->teacher('Sunita Devi', '9000000001');
        $this->driver('Mohan Lal', '9000000001');

        $this->openPage();

        $this->assertSame(2, AdminEmployee::count());
    }

    public function test_adding_a_manager_who_is_a_transport_driver_joins_the_driver_row(): void
    {
        $driver = $this->driver('Suresh', '9123456789');
        $this->openPage();
        $this->assertSame(1, AdminEmployee::count());

        Livewire::test(PayrollPage::class)
            ->set('empName', 'Suresh Yadav')
            ->set('empMobile', '9123456789')
            ->set('empType', 'management')
            ->set('empSalary', 18000)
            ->call('saveEmployee')
            ->assertHasNoErrors();

        $rows = AdminEmployee::all();
        $this->assertCount(1, $rows);
        $this->assertSame('Suresh Yadav', $rows[0]->name);
        $this->assertSame(['management', 'driver'], $rows[0]->types());
        $this->assertSame($driver->id, $rows[0]->driver_detail_id);
        $this->assertEquals(18000, $rows[0]->salary);
    }

    public function test_adding_a_teacher_by_hand_as_a_driver_is_refused(): void
    {
        $this->teacher('Ramesh Kumar', '9876543210');
        $this->openPage();

        Livewire::test(PayrollPage::class)
            ->set('empName', 'Ramesh')
            ->set('empMobile', '9876543210')
            ->set('empType', 'driver')
            ->set('empSalary', 3000)
            ->call('saveEmployee')
            ->assertHasErrors('empMobile');

        $this->assertSame(1, AdminEmployee::count());
    }

    public function test_deleting_an_employee_asks_first_in_the_pages_own_modal(): void
    {
        $emp = AdminEmployee::create(['organization_id' => $this->org, 'name' => 'Suresh', 'type' => 'employee', 'salary' => 0]);
        $other = AdminEmployee::create(['organization_id' => $this->org + 1, 'name' => 'Elsewhere', 'type' => 'employee', 'salary' => 0]);

        $page = Livewire::test(PayrollPage::class)
            ->call('deleteEmployee', $emp->id)
            ->assertSet('pendingDeleteEmpId', $emp->id)
            ->assertSee('Delete Employee?')
            ->call('cancelDeleteEmployee')
            ->assertSet('pendingDeleteEmpId', null)
            ->assertDontSee('Delete Employee?');
        $this->assertNotNull($emp->fresh());

        $page->call('deleteEmployee', $other->id)->assertSet('pendingDeleteEmpId', null);

        $page->call('deleteEmployee', $emp->id)
            ->call('doDeleteEmployee')
            ->assertSet('pendingDeleteEmpId', null);
        $this->assertNull($emp->fresh());
        $this->assertNotNull($other->fresh());
    }

    public function test_the_driver_filter_lists_a_teacher_who_drives(): void
    {
        $this->teacher('Ramesh Kumar', '9876543210');
        $this->driver('Ramesh', '9876543210');
        $this->teacher('Anita', '9555555555');

        Livewire::test(PayrollPage::class)
            ->set('empTypeFilter', 'driver')
            ->assertViewHas('employeesList', fn ($list) => $list->pluck('name')->all() === ['Ramesh Kumar'])
            ->assertViewHas('empStats', fn ($s) => $s['total'] === 2 && $s['teacher'] === 2 && $s['driver'] === 1)
            // Both chips on the one row.
            ->assertSeeHtml('>teacher</span><span class="text-xs px-2 py-0.5 rounded-full font-medium border capitalize bg-amber-50');
    }
}
