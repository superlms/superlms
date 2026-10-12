<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Fees;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\User;
use App\Support\SuperAdminFeeAllocator;
use App\Support\SuperAdminFeeTotals;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin Fees: one amount received from a school goes against what it
 * owes in order — per student the first class's students, then the next
 * class's; monthly April, then May… — and the home / Analytics totals read
 * the fee structure set for each school.
 */
class SuperAdminFeeReceiveTest extends TestCase
{
    private const YEAR = '2026-2027';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('f', 32))]);
        Carbon::setTestNow('2026-10-12 10:00:00');

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
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
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

        // Never a real push from a test.
        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public array $sent = [];
            public function sendToUser(...$args) { $this->sent[] = $args; }
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Super', 'email' => 'super@example.com', 'role' => 'super-admin']);
        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Alpha School', 'school_code' => 'ALP'],
            ['id' => 2, 'name' => 'Beta School', 'school_code' => 'BET'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function structure(array $values): SuperAdminFeeStructure
    {
        return SuperAdminFeeStructure::withoutEvents(fn () => SuperAdminFeeStructure::create($values + [
            'academic_year' => self::YEAR, 'fee_label' => 'Annual Platform Fee', 'is_active' => true,
        ]));
    }

    private function monthly(int $org, array $byMonth): SuperAdminFeeStructure
    {
        $amounts = [];
        foreach ((new SuperAdminFeeStructure(['academic_year' => self::YEAR, 'installment_frequency' => 'monthly']))->installmentPeriods() as $p) {
            $amounts[$p['key']] = $byMonth[$p['key']] ?? null;
        }

        return $this->structure([
            'organization_id' => $org, 'fee_type' => 'one_time', 'installment_frequency' => 'monthly',
            'period_amounts' => $amounts, 'total_amount' => array_sum($amounts), 'amount' => 0,
        ]);
    }

    /** Alpha: Class 2 (order 2) before it in the table, Class 1 with sections B and A. */
    private function alphaStudents(): array
    {
        DB::table('standards')->insert([
            ['id' => 20, 'organization_id' => 1, 'name' => 'Class 2', 'order' => 2],
            ['id' => 10, 'organization_id' => 1, 'name' => 'Class 1', 'order' => 1],
        ]);
        DB::table('sections')->insert([
            ['id' => 12, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'B'],
            ['id' => 11, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'A'],
            ['id' => 21, 'organization_id' => 1, 'standard_id' => 20, 'name' => 'A'],
        ]);
        $add = fn ($name, $class, $section) => DB::table('student_details')->insertGetId([
            'organization_id' => 1, 'standard_id' => $class, 'section_id' => $section, 'full_name' => $name,
        ]);

        return [
            'aman'  => $add('Aman', 20, 21),
            'zoya'  => $add('Zoya', 10, 12),
            'ravi'  => $add('Ravi', 10, 11),
            'kiran' => $add('Kiran', 10, 11),
        ];
    }

    private function payment(int $structure, array $where): ?object
    {
        return DB::table('super_admin_fee_payments')->where('super_admin_fee_structure_id', $structure)->where($where)->first();
    }

    public function test_a_per_student_amount_fills_the_first_class_then_the_next(): void
    {
        $s  = $this->alphaStudents();
        $fs = $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);
        // Kiran has paid 200 already.
        $kiranRow = DB::table('super_admin_fee_payments')->insertGetId([
            'organization_id' => 1, 'student_detail_id' => $s['kiran'], 'super_admin_fee_structure_id' => $fs->id,
            'amount' => 200, 'academic_year' => self::YEAR, 'payment_mode' => 'cash', 'payment_date' => '2026-09-01',
            'receipt_number' => 'SARC-1-2026-00001', 'remark' => 'first part', 'is_paid' => false,
        ]);

        $dues = SuperAdminFeeAllocator::dues($fs);
        // Class 1 first (section A A–Z, then B), Class 2 after.
        $this->assertSame(['Kiran', 'Ravi', 'Zoya', 'Aman'], array_column($dues, 'label'));
        $this->assertEquals(1800, array_sum(array_column($dues, 'due')));

        $result = SuperAdminFeeAllocator::apply($fs, 1100, 'online', '2026-10-12', '');

        $this->assertSame(['applied' => 1100.0, 'full' => 2, 'part' => 1, 'kind' => 'student', 'rows' => 3], $result);

        $kiran = $this->payment($fs->id, ['student_detail_id' => $s['kiran']]);
        $this->assertSame($kiranRow, $kiran->id);                 // the same row, topped up
        $this->assertEquals(500, $kiran->amount);
        $this->assertEquals(1, $kiran->is_paid);
        $this->assertSame('first part', $kiran->remark);          // a blank remark keeps the old one
        $this->assertSame('online', $kiran->payment_mode);
        $this->assertSame('2026-10-12', substr($kiran->payment_date, 0, 10));

        $ravi = $this->payment($fs->id, ['student_detail_id' => $s['ravi']]);
        $this->assertEquals(500, $ravi->amount);
        $this->assertEquals(1, $ravi->is_paid);
        $this->assertEquals(11, $ravi->section_id);
        $this->assertSame(self::YEAR, $ravi->academic_year);
        $this->assertStringStartsWith('SARC-1-2026-', $ravi->receipt_number);

        $zoya = $this->payment($fs->id, ['student_detail_id' => $s['zoya']]);
        $this->assertEquals(300, $zoya->amount);
        $this->assertEquals(0, $zoya->is_paid);

        $this->assertNull($this->payment($fs->id, ['student_detail_id' => $s['aman']]));

        // One notification for the whole amount, not one a student.
        $this->assertSame(1, DB::table('notifications')->count());
        $this->assertStringContainsString('₹1,100.00 received from Alpha School — 2 students paid in full, 1 part paid', json_decode(DB::table('notifications')->value('data'), true)['body']);

        // The rest goes on from Zoya, then to Class 2.
        SuperAdminFeeAllocator::apply($fs, 700, 'cash', '2026-10-13');
        $this->assertEquals(500, $this->payment($fs->id, ['student_detail_id' => $s['zoya']])->amount);
        $this->assertEquals(500, $this->payment($fs->id, ['student_detail_id' => $s['aman']])->amount);
        $this->assertSame([], SuperAdminFeeAllocator::dues($fs));
    }

    public function test_a_monthly_amount_fills_april_then_may_and_skips_months_without_a_fee(): void
    {
        $fs = $this->monthly(1, ['2026-04' => 1000, '2026-05' => 1000, '2026-07' => 1000, '2026-08' => 1000]);
        DB::table('super_admin_fee_payments')->insert([
            'organization_id' => 1, 'super_admin_fee_structure_id' => $fs->id, 'installment_period' => '2026-04',
            'amount' => 400, 'academic_year' => self::YEAR, 'is_paid' => false,
        ]);

        $plan = SuperAdminFeeAllocator::plan(SuperAdminFeeAllocator::dues($fs), 2000);
        $this->assertSame(['Apr 2026', 'May 2026', 'Jul 2026'], array_column($plan, 'label'));
        $this->assertSame([
            ['label' => 'Apr 2026', 'text' => '₹600 · paid in full', 'full' => true],
            ['label' => 'May 2026', 'text' => '₹1,000 · paid in full', 'full' => true],
            ['label' => 'Jul 2026', 'text' => '₹400 · ₹400 of ₹1,000 paid', 'full' => false],
        ], SuperAdminFeeAllocator::lines($plan));

        SuperAdminFeeAllocator::apply($fs, 2000, 'cash', '2026-10-12', 'Cheque 123');

        $this->assertEquals(1000, $this->payment($fs->id, ['installment_period' => '2026-04'])->amount);
        $this->assertEquals(1, $this->payment($fs->id, ['installment_period' => '2026-04'])->is_paid);
        $this->assertEquals(1000, $this->payment($fs->id, ['installment_period' => '2026-05'])->amount);
        $this->assertNull($this->payment($fs->id, ['installment_period' => '2026-06']));
        $july = $this->payment($fs->id, ['installment_period' => '2026-07']);
        $this->assertEquals(400, $july->amount);
        $this->assertEquals(0, $july->is_paid);
        $this->assertNull($july->student_detail_id);
        $this->assertSame('Cheque 123', $july->remark);
        $this->assertNull($this->payment($fs->id, ['installment_period' => '2026-08']));
    }

    public function test_a_one_time_amount_goes_to_the_years_fee(): void
    {
        $fs = $this->structure([
            'organization_id' => 2, 'fee_type' => 'one_time', 'installment_frequency' => 'yearly',
            'period_amounts' => [self::YEAR => 6000], 'total_amount' => 6000, 'amount' => 6000,
        ]);

        SuperAdminFeeAllocator::apply($fs, 2500, 'upi', '2026-10-12');
        SuperAdminFeeAllocator::apply($fs, 3500, 'upi', '2026-10-20');

        $row = $this->payment($fs->id, ['installment_period' => self::YEAR]);
        $this->assertEquals(6000, $row->amount);
        $this->assertEquals(1, $row->is_paid);
        $this->assertSame(1, DB::table('super_admin_fee_payments')->count());
    }

    private function signIn(): void
    {
        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 1;
        $user->role = 'super-admin';
        $this->actingAs($user);
    }

    public function test_the_update_panel_previews_refuses_too_much_and_saves(): void
    {
        $this->signIn();
        $this->alphaStudents();
        $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);

        $page = Livewire::test(Fees::class)
            ->call('openUpdatePanel')
            ->set('updateOrgId', '1')
            ->assertSee('Amount Received')
            ->assertSee('Goes to the first class\'s students first', false)
            ->set('receiveAmount', '1200')
            ->assertSee('Class 1')
            ->assertSee('2 students paid in full')
            ->assertSee('part paid: Zoya ₹200 of ₹500');

        $page->set('receiveAmount', '2500')
            ->assertSee('More than the total due (₹2,000).')
            ->call('applyReceivedAmount')
            ->assertHasErrors('receiveAmount');
        $this->assertSame(0, DB::table('super_admin_fee_payments')->count());

        $page->set('receiveAmount', '2000')
            ->set('receiveMode', 'cheque')
            ->call('applyReceivedAmount')
            ->assertHasNoErrors()
            ->assertSet('receiveAmount', '');

        $this->assertSame(4, DB::table('super_admin_fee_payments')->where('is_paid', true)->count());
        $this->assertSame(['cheque'], DB::table('super_admin_fee_payments')->distinct()->pluck('payment_mode')->all());
    }

    public function test_the_home_and_analytics_totals_read_the_fee_structures(): void
    {
        $this->alphaStudents();   // 4 students in Alpha
        $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);
        $this->monthly(2, ['2026-04' => 1000, '2026-05' => 1000, '2026-06' => 1000]);
        $this->structure(['organization_id' => 2, 'fee_type' => 'per_student', 'amount' => 99, 'is_active' => false]);
        $this->structure(['organization_id' => 2, 'fee_type' => 'per_student', 'amount' => 99, 'academic_year' => '2025-2026']);
        DB::table('super_admin_fee_payments')->insert([
            ['organization_id' => 2, 'super_admin_fee_structure_id' => 2, 'amount' => 3000, 'academic_year' => self::YEAR, 'is_paid' => true],
            ['organization_id' => 1, 'super_admin_fee_structure_id' => 1, 'amount' => 200, 'academic_year' => self::YEAR, 'is_paid' => false],
            ['organization_id' => 1, 'super_admin_fee_structure_id' => 1, 'amount' => 900, 'academic_year' => '2025-2026', 'is_paid' => true],
        ]);

        $sum = SuperAdminFeeTotals::summary();

        $this->assertSame(self::YEAR, $sum['year']);
        $this->assertEquals(5000, $sum['expected']);   // 500 × 4 + 3 months of 1000
        $this->assertEquals(3200, $sum['collected']);
        $this->assertEquals(1800, $sum['remaining']);
        $this->assertSame(64.0, $sum['pct']);
        $this->assertSame(2, $sum['schools']);
        $this->assertSame(1, $sum['schools_due']);
        // Biggest fee first.
        $this->assertSame(['Beta School', 'Alpha School'], $sum['by_school']->pluck('name')->all());
        $this->assertSame(['Monthly', 'Per Student'], $sum['by_school']->pluck('plan')->all());
    }
}
