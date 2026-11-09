<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Fees;
use App\Models\Admin\Fee\FeePayment;
use App\Models\PaymentTransaction;
use App\Models\Student\StudentAttendance;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\User;
use App\Support\FeeAccess;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Fees: who pays a Per Student fee. "School" — the super admin
 * marks it paid, and an unpaid student cannot sign in (as before). "Student"
 * — each student pays in the app: until then the app's API answers only the
 * payment screen (402), Pay Now goes through the platform's PhonePe and books
 * the fee, and the school can neither take the student's fee nor mark their
 * attendance.
 */
class PlatformFeeCollectFromTest extends TestCase
{
    private const YEAR = '2026-2027';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);
        config(['services.phonepe' => [
            'client_id' => 'test-client', 'client_secret' => 'test-secret', 'client_version' => '1',
            'env' => 'sandbox', 'webhook_username' => 'u', 'webhook_password' => 'p',
        ]]);
        Carbon::setTestNow('2026-11-09 10:00:00');
        FeeAccess::flush();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('school_code')->nullable();
            $t->string('logo')->nullable();
            $t->string('education_board')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('standard_id');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->text('permissions')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('super_admin_fee_structures', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->string('fee_type');
            $t->decimal('amount', 10, 2)->default(0);
            $t->decimal('total_amount', 10, 2)->nullable();
            $t->string('installment_frequency')->nullable();
            $t->text('period_amounts')->nullable();
            $t->string('academic_year');
            $t->string('fee_label')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('super_admin_fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->unsignedBigInteger('super_admin_fee_structure_id');
            $t->string('installment_period')->nullable();
            $t->decimal('amount', 10, 2);
            $t->string('academic_year');
            $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable();
            $t->string('receipt_number')->nullable();
            $t->text('remark')->nullable();
            $t->boolean('is_paid')->default(false);
            $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date');
            $t->tinyInteger('status')->default(1);
            $t->string('remarks')->nullable();
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->string('fee_type')->default('academic');
            $t->decimal('amount', 10, 2);
            $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable();
            $t->string('receipt_number')->nullable();
            $t->timestamps();
        });
        // The real migrations: the receipts table, the online payments table,
        // and this change (collect_from; fee_type taking "platform").
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();
        (require base_path('database/migrations/2026_06_16_000002_create_payment_transactions_table.php'))->up();
        (require base_path('database/migrations/2026_11_09_120000_add_collect_from_to_super_admin_fee_structures.php'))->up();

        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public function sendToUser(...$args) {}
        });

        DB::table('organizations')->insert(['id' => 1, 'name' => 'Adarsh School', 'school_code' => 'ADS']);
        DB::table('standards')->insert(['id' => 10, 'organization_id' => 1, 'name' => 'Class 5', 'order' => 5]);
        DB::table('sections')->insert(['id' => 11, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'A']);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Super', 'email' => 'super@example.com', 'role' => 'super-admin', 'organization_id' => null],
            ['id' => 2, 'name' => 'Riya', 'email' => 'riya@example.com', 'role' => 'user', 'organization_id' => 1],
            ['id' => 3, 'name' => 'Aman', 'email' => 'aman@example.com', 'role' => 'user', 'organization_id' => 1],
        ]);
        DB::table('student_details')->insert([
            ['id' => 21, 'user_id' => 2, 'organization_id' => 1, 'standard_id' => 10, 'section_id' => 11, 'full_name' => 'Riya', 'admission_no' => 'A1'],
            ['id' => 22, 'user_id' => 3, 'organization_id' => 1, 'standard_id' => 10, 'section_id' => 11, 'full_name' => 'Aman', 'admission_no' => 'A2'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        FeeAccess::flush();
        parent::tearDown();
    }

    private function perStudent(string $from): SuperAdminFeeStructure
    {
        return SuperAdminFeeStructure::create([
            'organization_id' => 1, 'fee_type' => 'per_student', 'collect_from' => $from, 'amount' => 70,
            'academic_year' => self::YEAR, 'fee_label' => 'Annual Platform Fee', 'is_active' => true,
        ]);
    }

    private function paid(SuperAdminFeeStructure $fs, int $studentId): void
    {
        DB::table('super_admin_fee_payments')->insert([
            'organization_id' => 1, 'student_detail_id' => $studentId, 'super_admin_fee_structure_id' => $fs->id,
            'amount' => 70, 'academic_year' => self::YEAR, 'payment_mode' => 'cash', 'payment_date' => '2026-11-01',
            'receipt_number' => 'SARC-1-2026-00009', 'is_paid' => true,
        ]);
        FeeAccess::flush();
    }

    public function test_school_pays_keeps_the_old_login_block_and_locks_nothing(): void
    {
        $fs = $this->perStudent('school');
        $this->paid($fs, 22);

        $this->assertTrue(FeeAccess::studentLoginBlocked(User::find(2)));   // unpaid: no sign-in, as before
        $this->assertFalse(FeeAccess::studentLoginBlocked(User::find(3)));
        $this->assertSame([], FeeAccess::lockedIds(1));
        $this->assertFalse(FeeAccess::userMustPay(User::find(2)));
    }

    public function test_students_pay_sign_in_to_the_payment_screen_and_nothing_else(): void
    {
        $fs = $this->perStudent('student');
        $this->paid($fs, 22);

        $riya = User::find(2);
        $this->assertFalse(FeeAccess::studentLoginBlocked($riya));        // signs in — to pay
        $this->assertTrue(FeeAccess::userMustPay($riya));
        $this->assertSame([21 => true], FeeAccess::lockedIds(1));

        Sanctum::actingAs($riya);
        $this->getJson('/api/v1/fees/summary')
            ->assertStatus(402)
            ->assertJsonPath('code', 'platform_fee_due')
            ->assertJsonPath('data.due', 70);

        $this->getJson('/api/v1/platform-fee')
            ->assertOk()
            ->assertJsonPath('data.must_pay', true)
            ->assertJsonPath('data.amount', 70)
            ->assertJsonPath('data.label', 'Annual Platform Fee')
            ->assertJsonPath('data.school.name', 'Adarsh School')
            ->assertJsonPath('data.student.class', 'Class 5 - A');

        // Aman has paid: the app is his as usual.
        Sanctum::actingAs(User::find(3));
        $this->assertNotSame(402, $this->getJson('/api/v1/fees/summary')->status());
        $this->getJson('/api/v1/platform-fee')->assertOk()->assertJsonPath('data.must_pay', false);
    }

    public function test_pay_now_goes_through_the_platform_phonepe_and_books_the_fee(): void
    {
        $fs = $this->perStudent('student');

        Http::fake([
            '*/v1/oauth/token'        => Http::response(['access_token' => 'tok', 'expires_at' => now()->addHour()->timestamp]),
            '*/checkout/v2/pay'       => Http::response(['orderId' => 'OMO1', 'state' => 'PENDING', 'redirectUrl' => 'https://mercury.phonepe.test/pay/OMO1']),
            '*/checkout/v2/order/*'   => Http::response(['orderId' => 'OMO1', 'state' => 'COMPLETED', 'amount' => 7000]),
        ]);

        Sanctum::actingAs(User::find(2));
        $start = $this->postJson('/api/v1/platform-fee/pay')
            ->assertOk()
            ->assertJsonPath('data.redirect_url', 'https://mercury.phonepe.test/pay/OMO1')
            ->assertJsonPath('data.amount', 70);

        $orderId = $start->json('data.merchant_order_id');
        $txn     = PaymentTransaction::where('merchant_order_id', $orderId)->first();
        $this->assertSame('platform', $txn->fee_type);
        $this->assertSame('platform', $txn->meta['merchant_scope']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/checkout/v2/pay') && $r['amount'] === 7000);

        $this->getJson('/api/v1/platform-fee/pay/' . $orderId)
            ->assertOk()
            ->assertJsonPath('data.paid', true)
            ->assertJsonPath('data.fee.must_pay', false);

        $row = DB::table('super_admin_fee_payments')->where('student_detail_id', 21)->first();
        $this->assertEquals(70, $row->amount);
        $this->assertEquals(1, $row->is_paid);
        $this->assertSame('online', $row->payment_mode);
        $this->assertStringContainsString($orderId, $row->remark);
        // Noted on the day it came, for Reports and the Ledger.
        $this->assertEquals(70, DB::table('super_admin_fee_receipts')->where('super_admin_fee_structure_id', $fs->id)->sum('amount'));

        // Settling again (the webhook after the poll) books nothing more.
        PaymentTransaction::where('merchant_order_id', $orderId)->first()->settle('COMPLETED', []);
        $this->assertEquals(70, DB::table('super_admin_fee_payments')->where('student_detail_id', 21)->value('amount'));

        // The app is open again, and so are the school's fee and attendance
        // for her — Aman has not paid yet, so he is still locked.
        $this->assertNotSame(402, $this->getJson('/api/v1/fees/summary')->status());
        $this->assertSame([22 => true], FeeAccess::lockedIds(1));
        $this->postJson('/api/v1/platform-fee/pay')->assertStatus(422);
    }

    public function test_a_locked_student_gets_no_fee_and_no_attendance(): void
    {
        $fs = $this->perStudent('student');
        $this->paid($fs, 22);

        // Attendance: the locked student's mark is not written; the paid one's is.
        StudentAttendance::create(['student_detail_id' => 21, 'user_id' => 2, 'organization_id' => 1, 'attendance_date' => '2026-11-09', 'status' => 1]);
        StudentAttendance::create(['student_detail_id' => 22, 'user_id' => 3, 'organization_id' => 1, 'attendance_date' => '2026-11-09', 'status' => 1]);
        $this->assertSame([22], DB::table('student_attendances')->pluck('student_detail_id')->map(fn ($v) => (int) $v)->all());

        // Fee: refused for the locked student.
        try {
            FeePayment::create(['organization_id' => 1, 'student_detail_id' => 21, 'standard_id' => 10, 'fee_type' => 'academic', 'amount' => 500, 'payment_mode' => 'cash', 'payment_date' => '2026-11-09']);
            $this->fail('A locked student\'s fee was taken.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('platform fee', $e->getMessage());
        }
        $this->assertSame(0, DB::table('fee_payments')->count());

        // Money a gateway already took is still booked.
        FeeAccess::allowingFeeWrites(fn () => FeeAccess::guardFeeWrite(21, 1));
        $this->addToAssertionCount(1);
    }

    public function test_the_super_admin_sets_and_switches_who_pays(): void
    {
        $this->actingAs(User::find(1));

        $page = Livewire::test(Fees::class)->call('selectSchool', 1)
            ->call('openAddFeePanel')
            ->set('feePlan', 'per_student')
            ->set('perStudentAmount', '70')
            ->set('perStudentLabel', 'Annual Platform Fee')
            ->assertSee('Who Pays')
            ->assertSee('Each student, in the app')
            ->set('feeCollectFrom', 'student')
            ->call('saveFeeStructures')
            ->assertHasNoErrors();

        $fs = SuperAdminFeeStructure::where('organization_id', 1)->first();
        $this->assertSame('student', $fs->collect_from);

        $page->assertSee('Each student pays in the app')
            ->assertSee('Locked till they pay')
            ->assertSee('Fee Collection')
            ->assertSee('Students Paid');

        $page->call('setCollectFrom', 'school')
            ->assertSee('The school pays')
            ->assertSee('Mark as Paid');
        $this->assertSame('school', $fs->fresh()->collect_from);

        // The edit form opens on what is saved.
        $page->call('setCollectFrom', 'student')->call('openUpdateFeePanel')->assertSet('feeCollectFrom', 'student');
    }
}
