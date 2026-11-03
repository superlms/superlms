<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\SchoolData;
use App\Models\User;
use App\Support\SchoolFeeTotals;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → School Data: one school's students, teachers, staff, classes
 * and fees, its figures up top — the fee as the school's own Analytics counts
 * it — and only that school's, with a search and an Excel export per list.
 */
class SuperAdminSchoolDataTest extends TestCase
{
    private const SCHOOL = 7;
    private const OTHER  = 8;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('s', 32))]);
        \Carbon\Carbon::setTestNow('2026-11-03 10:00:00');

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('logo')->nullable(); $t->boolean('status')->default(true);
            foreach (['email', 'mobile_number', 'state', 'education_board', 'medium', 'school_code'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('mobile_number')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->text('permissions')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('board')->nullable();
            $t->integer('order')->default(0); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            foreach (['full_name', 'admission_no', 'roll_no', 'phone', 'father_name'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->string('employee_id')->nullable(); $t->string('phone')->nullable(); $t->string('qualification')->nullable();
            $t->date('date_of_joining')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('teacher_detail_id')->nullable();
            $t->string('email')->nullable(); $t->string('mobile')->nullable(); $t->string('designation')->nullable(); $t->string('type')->nullable();
            $t->decimal('salary', 10, 2)->nullable(); $t->boolean('is_active')->default(true); $t->date('joining_date')->nullable(); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date'); $t->integer('status'); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->string('fee_type')->nullable();
            $t->decimal('amount', 10, 2)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type')->nullable(); $t->decimal('amount', 10, 2)->default(0); $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable(); $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->decimal('monthly_fee', 10, 2)->default(0); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('transportation_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('organization_id')->nullable(); $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 10, 2)->default(0); $t->date('payment_date')->nullable(); $t->timestamps();
        });

        $now = now();
        DB::table('organizations')->insert([
            ['id' => self::SCHOOL, 'name' => 'Adarsh Public School', 'education_board' => 'CBSE', 'created_at' => $now],
            ['id' => self::OTHER, 'name' => 'Sunrise Academy', 'education_board' => 'State', 'created_at' => $now],
        ]);
        DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => self::SCHOOL, 'name' => 'Class 8', 'order' => 8],
            ['id' => 2, 'organization_id' => self::OTHER, 'name' => 'Class 9', 'order' => 9],
        ]);
        DB::table('sections')->insert([
            ['id' => 1, 'standard_id' => 1, 'organization_id' => self::SCHOOL, 'name' => 'A'],
            ['id' => 2, 'standard_id' => 1, 'organization_id' => self::SCHOOL, 'name' => 'B'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Aarav', 'role' => 'user', 'organization_id' => self::SCHOOL, 'is_active' => true],
            ['id' => 2, 'name' => 'Bela', 'role' => 'user', 'organization_id' => self::SCHOOL, 'is_active' => false],
            ['id' => 3, 'name' => 'Zed', 'role' => 'user', 'organization_id' => self::OTHER, 'is_active' => true],
            ['id' => 4, 'name' => 'Meena Rao', 'role' => 'teacher', 'organization_id' => self::SCHOOL, 'is_active' => true],
        ]);
        DB::table('student_details')->insert([
            ['id' => 1, 'user_id' => 1, 'organization_id' => self::SCHOOL, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Aarav Sharma', 'admission_no' => '2024/118', 'created_at' => $now],
            ['id' => 2, 'user_id' => 2, 'organization_id' => self::SCHOOL, 'standard_id' => 1, 'section_id' => 2, 'full_name' => 'Bela Khan', 'admission_no' => '2024/119', 'created_at' => $now],
            ['id' => 3, 'user_id' => 3, 'organization_id' => self::OTHER, 'standard_id' => 2, 'section_id' => null, 'full_name' => 'Zed Other', 'admission_no' => 'X1', 'created_at' => $now],
        ]);
        DB::table('teacher_details')->insert(['user_id' => 4, 'organization_id' => self::SCHOOL, 'employee_id' => 'T-01', 'qualification' => 'M.Sc.', 'created_at' => $now]);
        DB::table('admin_employees')->insert([
            ['name' => 'Ramesh Driver', 'organization_id' => self::SCHOOL, 'type' => 'driver', 'designation' => 'Bus driver', 'salary' => 15000],
            ['name' => 'Meena Rao', 'organization_id' => self::SCHOOL, 'type' => 'teacher', 'designation' => 'Teacher', 'salary' => 30000],
            ['name' => 'Elsewhere Clerk', 'organization_id' => self::OTHER, 'type' => 'employee', 'designation' => 'Clerk', 'salary' => 9000],
        ]);
        DB::table('student_attendances')->insert([
            ['student_detail_id' => 1, 'organization_id' => self::SCHOOL, 'attendance_date' => '2026-11-03', 'status' => 1],
            ['student_detail_id' => 2, 'organization_id' => self::SCHOOL, 'attendance_date' => '2026-11-03', 'status' => 0],
        ]);
        // Class 8's head: ₹1,000 a student → ₹2,000 for the school; ₹500 paid this session.
        DB::table('fee_structures')->insert(['organization_id' => self::SCHOOL, 'standard_id' => 1, 'fee_type' => 'academic', 'amount' => 1000]);
        DB::table('fee_payments')->insert([
            ['organization_id' => self::SCHOOL, 'student_detail_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'fee_type' => 'academic', 'amount' => 500, 'payment_mode' => 'cash', 'payment_date' => '2026-10-10', 'receipt_number' => 'RCT-7-1'],
            ['organization_id' => self::OTHER, 'student_detail_id' => 3, 'standard_id' => 2, 'section_id' => null, 'fee_type' => 'academic', 'amount' => 999, 'payment_mode' => 'upi', 'payment_date' => '2026-10-11', 'receipt_number' => 'RCT-8-1'],
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(string $role = 'super-admin', ?int $onlySchool = null): void
    {
        $user = User::forceCreate([
            'name' => 'Boss', 'email' => 'boss@example.com', 'role' => $role,
            'permissions' => ['super-admin.school-data'], 'allowed_organization_id' => $onlySchool,
        ]);
        $this->actingAs($user);
    }

    public function test_the_fee_is_the_one_the_schools_own_analytics_counts(): void
    {
        $fee = SchoolFeeTotals::summary(self::SCHOOL);

        $this->assertEquals(2000, $fee['academicFee']);
        $this->assertEquals(500, $fee['collected']);
        $this->assertEquals(1500, $fee['remaining']);
    }

    public function test_one_schools_figures_and_lists_only(): void
    {
        $this->signIn();

        $page = Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
            ->assertSee('Adarsh Public School')
            ->assertSee('Present Today')
            ->assertSee('50%')            // 1 of 2 marked present
            ->assertSee('₹1,500')         // pending
            ->assertSee('Aarav Sharma')
            ->assertSee('Bela Khan')
            ->assertDontSee('Zed Other');

        $page->set('search', '119')->assertSee('Bela Khan')->assertDontSee('Aarav Sharma');

        $page->call('setTab', 'teachers')->assertSet('search', '')->assertSee('Meena Rao')->assertSee('T-01');
        $page->call('setTab', 'staff')->assertSee('Ramesh Driver')->assertDontSee('Elsewhere Clerk')
            ->assertDontSee('30,000'); // teachers are on Teachers, not Staff
        $page->call('setTab', 'classes')->assertSee('Class 8')->assertDontSee('Class 9');
        $page->call('setTab', 'fees')->assertSee('RCT-7-1')->assertDontSee('RCT-8-1');

        // Another school, and the lists follow it.
        $page->set('school', self::OTHER)->call('setTab', 'students')->assertSee('Zed Other')->assertDontSee('Aarav Sharma');
    }

    public function test_a_sub_super_admin_limited_to_one_school_sees_only_it(): void
    {
        $this->signIn('sub-super-admin', self::SCHOOL);

        Livewire::test(SchoolData::class, ['school' => self::OTHER])
            ->assertSet('school', self::SCHOOL)
            ->assertDontSee('Sunrise Academy')
            ->assertSee('Aarav Sharma')
            ->set('school', self::OTHER)
            ->assertSet('school', self::SCHOOL)
            ->assertDontSee('Zed Other');
    }

    public function test_an_unknown_tab_falls_back_to_students(): void
    {
        $this->signIn();

        Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
            ->set('tab', 'passwords')
            ->assertSet('tab', 'students')
            ->assertSee('Aarav Sharma');
    }

    public function test_each_list_downloads_as_excel(): void
    {
        $this->signIn();

        foreach (['staff', 'classes', 'fees'] as $tab) {
            Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
                ->call('setTab', $tab)
                ->call('export')
                ->assertFileDownloaded('adarsh-public-school-' . $tab . '-2026-11-03.xlsx');
        }
    }

    public function test_school_data_is_the_last_screen_in_the_menu(): void
    {
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame('super-admin.school-data', end($links));
    }
}
