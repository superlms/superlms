<?php

namespace Tests\Feature;

use App\Livewire\Admin\Payroll as PayrollPage;
use App\Livewire\Admin\Teacher as TeacherPage;
use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\DriverDetail;
use App\Models\Admin\Transportation;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Payroll page: Add asks who is being added first — a teacher gets the
 * Teachers page's own form, a driver the driver's form (and becomes a
 * Transport driver), management and employees the payroll form. The list runs
 * management, teachers, drivers, employees. Mark Attendance is one slide-in
 * panel on today for everyone, teachers included and first, A to Z.
 */
class PayrollPanelTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('username')->nullable();
            $t->string('mobile_number')->nullable(); $t->string('password')->nullable(); $t->string('role')->nullable();
            $t->string('image')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id');
            $t->string('phone')->nullable(); $t->date('date_of_joining')->nullable(); $t->timestamps();
        });
        Schema::create('driver_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id');
            $t->string('image')->nullable(); $t->string('license_no')->nullable(); $t->string('vehicle_no')->nullable();
            $t->string('phone')->nullable(); $t->text('address')->nullable(); $t->integer('experience_years')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name'); $t->string('vehicle_type')->nullable();
            $t->unsignedBigInteger('driver_detail_id')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('teacher_detail_id')->nullable(); $t->unsignedBigInteger('driver_detail_id')->nullable();
            $t->string('email')->nullable(); $t->string('mobile')->nullable(); $t->string('designation')->nullable();
            $t->string('type'); $t->decimal('salary', 10, 2)->default(0); $t->text('address')->nullable();
            $t->string('bank_name')->nullable(); $t->string('bank_account_no')->nullable(); $t->string('bank_holder_name')->nullable();
            $t->string('bank_branch')->nullable(); $t->string('bank_ifsc')->nullable(); $t->string('photo')->nullable();
            $t->boolean('is_active')->default(true); $t->date('joining_date')->nullable(); $t->timestamps();
        });
        Schema::create('admin_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('admin_employee_id')->nullable();
            $t->date('date')->nullable(); $t->string('status')->default('present'); $t->string('note')->nullable();
            $t->timestamps(); $t->unique(['admin_employee_id', 'date']);
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id')->default(0); $t->unsignedBigInteger('organization_id')->default(0);
            $t->date('attendance_date')->nullable(); $t->integer('status')->default(0); $t->string('remarks')->nullable();
            $t->unsignedBigInteger('marked_by')->default(0); $t->timestamps();
        });
        Schema::create('admin_salary_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('admin_employee_id')->nullable();
            $t->string('month')->nullable(); $t->decimal('amount', 10, 2)->default(0); $t->string('payment_mode')->default('cash');
            $t->string('paid_by')->nullable(); $t->string('status')->default('pending'); $t->date('payment_date')->nullable();
            $t->string('transaction_id')->nullable(); $t->text('remark')->nullable(); $t->timestamps();
        });
        Schema::create('employee_id_cards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('admin_employee_id')->default(0); $t->unsignedBigInteger('organization_id')->default(0);
            $t->string('status')->default('active'); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Delhi Public School']);

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

    private function staff(string $name, string $type, array $more = []): AdminEmployee
    {
        return AdminEmployee::create(['organization_id' => $this->org, 'name' => $name, 'type' => $type, 'salary' => 1000] + $more);
    }

    public function test_add_asks_who_is_being_added_first(): void
    {
        $page = Livewire::test(PayrollPage::class)
            ->call('openEmpModal')
            ->assertSee('Who are you adding?')
            ->assertSee('Choose who you are adding')
            // Nothing of the form, and nothing to save, until that is answered.
            ->assertDontSeeHtml('wire:model.defer="empName"')
            ->assertDontSeeHtml('wire:click="saveEmployee"');

        $page->call('chooseEmpType', 'management')
            ->assertSet('empType', 'management')
            ->assertSee('New Management')
            ->assertSeeHtml('wire:model.defer="empName"')
            ->assertSeeHtml('wire:model.defer="empDesignation"')
            ->assertDontSeeHtml('wire:model.defer="drvLicenseNo"')
            // The type is the answer above — no Type box when adding.
            ->assertDontSeeHtml('wire:model.live="empType"')
            ->assertSee('Save Management');

        $page->call('chooseEmpType', 'employee')->assertSet('empType', 'employee')->assertSee('Save Employee');

        $page->set('empName', 'Sita Devi')->set('empSalary', 9000)
            ->call('saveEmployee')
            ->assertHasNoErrors()
            ->assertSet('showEmpModal', false);

        $row = AdminEmployee::first();
        $this->assertSame(['Sita Devi', 'employee'], [$row->name, $row->type]);
        $this->assertEquals(9000, $row->salary);
    }

    public function test_an_entry_being_edited_keeps_its_form_with_the_type_box(): void
    {
        $emp = $this->staff('Mohan Lal', 'management', ['designation' => 'Principal']);

        Livewire::test(PayrollPage::class)
            ->call('openEmpModal', $emp->id)
            ->assertSee('Edit Employee')
            ->assertDontSee('Who are you adding?')
            ->assertSeeHtml('wire:model.live="empType"')
            ->assertSet('empName', 'Mohan Lal')
            // The chooser does nothing to an entry being edited.
            ->call('chooseEmpType', 'driver')
            ->assertSet('empType', 'management')
            ->set('empType', 'employee')
            ->set('empSalary', 2500)
            ->call('saveEmployee')
            ->assertHasNoErrors();

        $this->assertSame('employee', $emp->fresh()->type);
        $this->assertEquals(2500, $emp->fresh()->salary);
        $this->assertSame(0, DriverDetail::count());
    }

    public function test_adding_a_driver_makes_the_transport_driver_and_the_payroll_row(): void
    {
        $route = Transportation::create(['organization_id' => $this->org, 'route_name' => 'Route 1', 'vehicle_type' => 'Bus']);
        $other = Transportation::create(['organization_id' => $this->org, 'route_name' => 'Route 2', 'vehicle_type' => 'Van']);

        $page = Livewire::test(PayrollPage::class)
            ->call('openEmpModal')
            ->call('chooseEmpType', 'driver')
            ->assertSee('New Driver')
            ->assertSeeHtml('wire:model.defer="drvLicenseNo"')
            ->assertSeeHtml('wire:model.defer="drvVehicleNo"')
            ->assertSee('Assign Routes')
            ->assertSee('Route 1')
            ->assertDontSeeHtml('wire:model.defer="empDesignation"');

        // The mobile is the driver's login, so it is needed here.
        $page->set('empName', 'Ravi Kumar')->set('empSalary', 8000)
            ->call('saveEmployee')
            ->assertHasErrors(['empMobile']);
        $this->assertSame(0, DriverDetail::count());

        $page->set('empMobile', '9811111111')
            ->set('drvLicenseNo', 'RJ14 2020 0012345')
            ->set('drvVehicleNo', 'RJ14 PA 1234')
            ->set('drvExperience', '6')
            ->set('drvRoutes', [(string) $route->id])
            ->set('empBankName', 'SBI')
            ->call('saveEmployee')
            ->assertHasNoErrors()
            ->assertSet('showEmpModal', false);

        $driver = DriverDetail::with('user')->first();
        $this->assertNotNull($driver);
        $this->assertSame(['Ravi Kumar', 'driver', '9811111111'], [$driver->user->name, $driver->user->role, $driver->user->mobile_number]);
        $this->assertStringEndsWith(\App\Livewire\Admin\Transport::DRIVER_EMAIL_DOMAIN, $driver->user->email);
        $this->assertSame(['RJ14 2020 0012345', 'RJ14 PA 1234', 6], [$driver->license_no, $driver->vehicle_no, $driver->experience_years]);
        $this->assertSame($driver->id, (int) $route->fresh()->driver_detail_id);
        $this->assertSame(0, (int) $other->fresh()->driver_detail_id);

        // One payroll row, linked to that driver, with the salary and bank from the form.
        $rows = AdminEmployee::all();
        $this->assertCount(1, $rows);
        $this->assertSame([$driver->id, 'driver', 'Driver', 'SBI'], [$rows[0]->driver_detail_id, $rows[0]->type, $rows[0]->designation, $rows[0]->bank_name]);
        $this->assertEquals(8000, $rows[0]->salary);
        $this->assertNull($rows[0]->email);
    }

    public function test_adding_a_teacher_opens_the_teachers_own_form(): void
    {
        $page = Livewire::test(PayrollPage::class)
            ->call('openEmpModal')
            ->call('chooseEmpType', 'teacher')
            ->assertSet('showEmpModal', false)
            ->assertSet('showTeacherForm', true)
            // The Teachers page's form, as that page shows it.
            ->assertSee('New Teacher')
            ->assertSeeHtml('wire:model.blur="teacherUsername"')
            ->assertSee('Create Teacher');

        // Closed without saving …
        $page->dispatch('teacherFormClosed')->assertSet('showTeacherForm', false)->assertDontSee('New Teacher');

        // … or saved: the form goes, and the new teacher has a payroll row.
        $page->call('openEmpModal')->call('chooseEmpType', 'teacher')->assertSet('showTeacherForm', true);
        $this->teacher('Anita Verma', '9833333333');
        $page->dispatch('onTeacherAddUpdate')
            ->assertSet('showTeacherForm', false)
            ->assertViewHas('employeesList', fn ($list) => $list->pluck('name')->all() === ['Anita Verma']);
    }

    public function test_the_teacher_form_on_its_own_is_the_teachers_page_form(): void
    {
        Livewire::test(TeacherPage::class, ['formOnly' => true])
            ->assertSet('open', true)
            ->assertSee('New Teacher')
            ->assertSeeHtml('wire:model.blur="teacherName"')
            // No list behind it.
            ->assertDontSee('Class Teacher')
            ->call('closeModal')
            ->assertDispatched('teacherFormClosed');

        // The Teachers page itself still opens on its list, form shut.
        Schema::create('assign_teacher_standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Livewire::test(TeacherPage::class)
            ->assertSet('formOnly', false)
            ->assertSet('open', false)
            ->assertSee('Class Teacher')
            ->assertDontSee('New Teacher')
            ->call('onAddTeacher')
            ->assertSee('New Teacher')
            ->call('closeModal')
            ->assertNotDispatched('teacherFormClosed');
    }

    public function test_the_list_runs_management_teachers_drivers_employees(): void
    {
        $this->staff('Zoya', 'employee');
        $this->staff('Yash', 'driver');
        $this->teacher('Xavier', '9000000001');
        $this->staff('Wasim', 'management');
        $this->staff('Aman', 'employee');

        Livewire::test(PayrollPage::class)
            ->assertViewHas('employeesList', fn ($list) => $list->pluck('name')->all() === ['Wasim', 'Xavier', 'Yash', 'Aman', 'Zoya'])
            ->assertSee('Type (Mgmt → Teacher → Driver → Employee)');
    }

    public function test_mark_attendance_is_one_panel_on_today_for_everyone(): void
    {
        $zoya   = $this->staff('Zoya', 'employee');
        $yash   = $this->staff('Yash', 'driver');
        $xavier = $this->teacher('Xavier', '9000000001');
        $bina   = $this->teacher('Bina', '9000000002');
        $wasim  = $this->staff('Wasim', 'management');
        $today  = now()->toDateString();

        $page = Livewire::test(PayrollPage::class)
            ->set('activeTab', 'attendance')
            // The Add Student button's look, and no date step before the panel.
            ->assertSeeHtml('wire:click="openMarkPanel"')
            ->assertDontSeeHtml('wire:click="startMarking"')
            ->call('openMarkPanel')
            ->assertSet('showMarkPanel', true)
            ->assertSet('panelDate', $today)
            ->assertSet('attendanceMode', 'view')
            // Teachers first, then management, drivers, employees — A to Z within.
            ->assertViewHas('markPeople', fn ($list) => $list->pluck('name')->all() === ['Bina', 'Xavier', 'Wasim', 'Yash', 'Zoya'])
            ->assertSee('Save Attendance');

        $ids = AdminEmployee::pluck('id', 'name');

        // A teacher's row offers Holiday; everyone else's, Leave.
        $html = $page->html();
        $this->assertStringContainsString("pick({$ids['Bina']}, 'holiday')", $html);
        $this->assertStringNotContainsString("pick({$ids['Bina']}, 'leave')", $html);
        $this->assertStringContainsString("pick({$ids['Zoya']}, 'leave')", $html);
        $this->assertStringNotContainsString("pick({$ids['Zoya']}, 'holiday')", $html);

        $page->set("panelRows.{$ids['Bina']}.status", 'present')
            ->set("panelRows.{$ids['Xavier']}.status", 'half_day')
            ->set("panelRows.{$ids['Xavier']}.remark", 'Left at noon')
            ->set("panelRows.{$ids['Wasim']}.status", 'absent')
            ->set("panelRows.{$ids['Yash']}.status", 'leave')
            // Not a status a driver's row offers: not written.
            ->set("panelRows.{$ids['Zoya']}.status", 'holiday')
            ->call('saveMarkPanel')
            ->assertSet('showMarkPanel', false)
            ->assertSet('attendanceDate', $today);

        // Teachers: the Attendance module's own records and codes.
        $t = TeacherAttendance::get()->keyBy('teacher_detail_id');
        $this->assertCount(2, $t);
        $this->assertSame([1, $this->org, 999], [(int) $t[$bina->id]->status, (int) $t[$bina->id]->organization_id, (int) $t[$bina->id]->marked_by]);
        $this->assertSame([2, 'Left at noon'], [(int) $t[$xavier->id]->status, $t[$xavier->id]->remarks]);
        $this->assertSame($today, $t[$bina->id]->attendance_date->toDateString());

        // Everyone else: payroll's records.
        $a = AdminAttendance::get()->keyBy('admin_employee_id');
        $this->assertSame(['absent', 'leave'], [$a[$wasim->id]->status, $a[$yash->id]->status]);
        $this->assertFalse($a->has($zoya->id));
        $this->assertFalse($a->has($ids['Bina']));

        // Opened again, the day comes back as saved; a row cleared is unmarked,
        // a row untouched is left exactly as it was.
        $stamp = $t[$bina->id]->updated_at;
        $page->call('openMarkPanel')
            ->assertSet('panelExisting', true)
            ->assertSet("panelRows.{$ids['Xavier']}", ['status' => 'half_day', 'remark' => 'Left at noon'])
            ->assertSet("panelRows.{$ids['Yash']}.status", 'leave')
            ->assertSee('Update Attendance')
            ->set("panelRows.{$ids['Xavier']}.status", 'holiday')
            ->set("panelRows.{$ids['Wasim']}.status", '')
            ->call('saveMarkPanel');

        $this->assertSame(3, (int) TeacherAttendance::where('teacher_detail_id', $xavier->id)->value('status'));
        $this->assertEquals($stamp, TeacherAttendance::where('teacher_detail_id', $bina->id)->first()->updated_at);
        $this->assertSame(2, TeacherAttendance::count());
        $this->assertNull(AdminAttendance::where('admin_employee_id', $wasim->id)->first());
        $this->assertSame('leave', AdminAttendance::where('admin_employee_id', $yash->id)->value('status'));
    }

    public function test_the_mark_panel_takes_an_earlier_day_but_not_one_to_come_and_needs_a_mark(): void
    {
        $emp = $this->staff('Zoya', 'employee');
        $yesterday = now()->subDay()->toDateString();
        AdminAttendance::create(['organization_id' => $this->org, 'admin_employee_id' => $emp->id, 'date' => $yesterday, 'status' => 'absent', 'note' => 'Sick']);

        Livewire::test(PayrollPage::class)
            ->call('openMarkPanel')
            ->assertSet("panelRows.{$emp->id}.status", '')
            // Nothing marked: nothing saved, the panel stays.
            ->call('saveMarkPanel')
            ->assertSet('showMarkPanel', true)
            ->set('panelDate', $yesterday)
            ->assertSet("panelRows.{$emp->id}", ['status' => 'absent', 'remark' => 'Sick'])
            ->assertSet('panelExisting', true)
            ->set('panelDate', now()->addDay()->toDateString())
            ->assertSet('panelDate', now()->toDateString());

        $this->assertSame(1, AdminAttendance::count());
    }
}
