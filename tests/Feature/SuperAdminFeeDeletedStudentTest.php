<?php

namespace Tests\Feature;

use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Support\Cascade;
use App\Support\SuperAdminFeeAllocator;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Super Admin Fees: a deleted student's money stays received. It moves onto
 * the next students still owing on the school's Per Student fee (the order
 * Amount Received pays them), or is carried for the next student added — so
 * the collected total stays what came in (₹15,000, not ₹14,860).
 */
class SuperAdminFeeDeletedStudentTest extends TestCase
{
    private const YEAR = '2026-2027';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('f', 32))]);
        Carbon::setTestNow('2026-11-01 10:00:00');
        Cascade::forgetSchema();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
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
            $t->string('roll_no')->nullable();
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
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();
        (require base_path('database/migrations/2026_10_14_120000_add_allocation_to_super_admin_fee_receipts.php'))->up();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
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

        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public function sendToUser(...$args) {}
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Super', 'email' => 'super@example.com', 'role' => 'super-admin']);
        DB::table('organizations')->insert(['id' => 1, 'name' => 'Alpha School']);
        DB::table('standards')->insert(['id' => 10, 'organization_id' => 1, 'name' => 'Class 1', 'order' => 1]);
        DB::table('sections')->insert(['id' => 11, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'A']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cascade::forgetSchema();
        parent::tearDown();
    }

    private function fee(int $amount = 70): SuperAdminFeeStructure
    {
        return SuperAdminFeeStructure::withoutEvents(fn () => SuperAdminFeeStructure::create([
            'organization_id' => 1, 'fee_type' => 'per_student', 'amount' => $amount,
            'academic_year' => self::YEAR, 'fee_label' => 'Annual Platform Fee', 'is_active' => true,
        ]));
    }

    private function student(string $name): int
    {
        return DB::table('student_details')->insertGetId([
            'organization_id' => 1, 'standard_id' => 10, 'section_id' => 11, 'full_name' => $name,
        ]);
    }

    private function collected(int $fee): float
    {
        return (float) DB::table('super_admin_fee_payments')->where('super_admin_fee_structure_id', $fee)->sum('amount');
    }

    private function paidOn(int $fee, int $student): float
    {
        return (float) DB::table('super_admin_fee_payments')
            ->where('super_admin_fee_structure_id', $fee)->where('student_detail_id', $student)->value('amount');
    }

    public function test_a_deleted_students_money_moves_to_the_next_student_owing(): void
    {
        $s = [
            'a' => $this->student('Aarav'), 'b' => $this->student('Bela'),
            'c' => $this->student('Chirag'), 'd' => $this->student('Dev'),
        ];
        $fee = $this->fee();

        // ₹230 received: Aarav, Bela and Chirag paid in full, Dev ₹20 of ₹70.
        SuperAdminFeeAllocator::apply($fee, 230, 'online', '2026-10-20');
        $this->assertEquals(20, $this->paidOn($fee->id, $s['d']));
        $receipts = DB::table('super_admin_fee_receipts')->count();

        StudentDetail::find($s['a'])->delete();

        // The ₹70 goes on: Dev is now paid in full, ₹20 is carried for the next student.
        $this->assertEquals(230, $this->collected($fee->id));
        $this->assertEquals(70, $this->paidOn($fee->id, $s['d']));
        $this->assertEquals(20, SuperAdminFeeAllocator::carriedFor(1, self::YEAR));
        $this->assertNull(DB::table('super_admin_fee_payments')->where('student_detail_id', $s['a'])->first());
        // No money came or went: no receipt is noted.
        $this->assertSame($receipts, DB::table('super_admin_fee_receipts')->count());
        $this->assertEquals(230, DB::table('super_admin_fee_receipts')->sum('amount'));

        // A student added later takes what was carried.
        $new = StudentDetail::create(['organization_id' => 1, 'standard_id' => 10, 'section_id' => 11, 'full_name' => 'Esha']);
        $this->assertEquals(20, $this->paidOn($fee->id, $new->id));
        $this->assertEquals(0, SuperAdminFeeAllocator::carriedFor(1, self::YEAR));
        $this->assertEquals(230, $this->collected($fee->id));
    }

    public function test_money_lost_with_students_deleted_before_is_placed_again(): void
    {
        $s   = ['a' => $this->student('Aarav'), 'b' => $this->student('Bela'), 'c' => $this->student('Chirag')];
        $fee = $this->fee();
        SuperAdminFeeAllocator::apply($fee, 140, 'cash', '2026-10-20');

        // The old way: the database's cascade took a paid student's row, nothing else.
        DB::table('super_admin_fee_payments')->where('student_detail_id', $s['a'])->delete();
        DB::table('student_details')->where('id', $s['a'])->delete();
        $this->assertEquals(70, $this->collected($fee->id));

        $restored = SuperAdminFeeAllocator::restoreLost();

        $this->assertSame([$fee->id => 70.0], $restored);
        $this->assertEquals(140, $this->collected($fee->id));
        $this->assertEquals(70, $this->paidOn($fee->id, $s['c']));
        // Run again, nothing more moves.
        $this->assertSame([], SuperAdminFeeAllocator::restoreLost());
        $this->assertEquals(140, $this->collected($fee->id));
    }
}
