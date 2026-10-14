<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Fees;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\SchoolWhatsApp;
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
        // The real migration: the receipts table (and its backfill, empty here).
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();
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

    public function test_the_update_panel_is_in_the_students_form_look_with_amount_received_folded(): void
    {
        $this->signIn();
        $this->alphaStudents();
        $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);

        $page = Livewire::test(Fees::class)->call('openUpdatePanel')->set('updateOrgId', '1')
            ->set('updateStandardId', '10')->call('loadStudentFeeList');
        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString('w-full max-w-3xl bg-white shadow-2xl', $html);
        // Amount Received folds away: a row that opens its form.
        $this->assertStringContainsString('x-data="{ open: false }"', $html);
        $this->assertStringContainsString('x-show="open" style="display: none"', $html);
        // The plan as plain text, no chips; statuses as plain words.
        $this->assertStringContainsString('Per Student · Annual Platform Fee · 2026-2027', $text);
        $this->assertStringNotContainsString('rounded-full font-medium border border-blue-100', $html);
        $this->assertStringNotContainsString('rounded-full font-semibold border', $html);
        $this->assertStringContainsString('Kiran Class 1 (A) · Adm: —', $text);
    }

    public function test_the_fee_structure_panel_is_in_the_update_fee_look_and_counts_the_schools_students(): void
    {
        $this->signIn();
        $this->alphaStudents();
        $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 70]);

        $html = Livewire::test(Fees::class)->call('selectSchool', 1)->call('openUpdateFeePanel')->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString('w-full max-w-3xl bg-white shadow-2xl', $html);
        $this->assertStringContainsString('wire:click="saveFeeStructures"', $html);
        $this->assertStringNotContainsString('bg-blue-50 border border-blue-100 rounded-xl', $html);
        // The school's own page has no student count on it; the panel counts them (it said 0).
        $this->assertStringContainsString('₹70.00 × 4 students', $text);
        $this->assertStringContainsString('₹280', $text);
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

    private function receipts(?string $date = null)
    {
        return DB::table('super_admin_fee_receipts')->when($date, fn ($q) => $q->whereDate('payment_date', $date));
    }

    public function test_each_time_money_comes_is_one_receipt_on_its_own_day(): void
    {
        $this->signIn();
        $s  = $this->alphaStudents();
        $fs = $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 70]);

        // One amount for many students is one receipt.
        SuperAdminFeeAllocator::apply($fs, 150, 'cash', '2026-10-12');        // Kiran 70, Ravi 70, Zoya 10
        $first = $this->receipts()->first();
        $this->assertSame(1, $this->receipts()->count());
        $this->assertEquals(150, $first->amount);
        $this->assertSame('3 students', $first->label);
        $this->assertSame(3, (int) $first->items);
        $this->assertSame('cash', $first->payment_mode);
        $this->assertMatchesRegularExpression('/^SARC-1-2026-\d{5} – SARC-1-2026-\d{5}$/', $first->receipt_number);

        // The next amount tops Zoya up: the new money is on its own day, the
        // first day keeps its 150.
        SuperAdminFeeAllocator::apply($fs, 130, 'upi', '2026-10-20');         // Zoya +60, Aman 70
        $this->assertEquals(150, $this->receipts('2026-10-12')->sum('amount'));
        $this->assertEquals(130, $this->receipts('2026-10-20')->sum('amount'));

        // A single Edit, a date change and a clear on the Fees screen note what they changed.
        $page = Livewire::test(Fees::class)->call('selectSchool', 1);
        $row  = fn ($who) => $this->payment($fs->id, ['student_detail_id' => $s[$who]]);

        $page->call('openPayModal', $s['aman'], $fs->id, 70, 70, $row('aman')->id)
            ->set('payAmount', '50')->set('payDate', '2026-10-20')->call('savePayment');
        $this->assertEquals(110, $this->receipts('2026-10-20')->sum('amount'));

        $page->call('openPayModal', $s['ravi'], $fs->id, 70, 70, $row('ravi')->id)
            ->set('payAmount', '70')->set('payDate', '2026-10-15')->call('savePayment');
        $this->assertEquals(80, $this->receipts('2026-10-12')->sum('amount'));
        $this->assertEquals(70, $this->receipts('2026-10-15')->sum('amount'));

        $page->call('openPayModal', $s['zoya'], $fs->id, 70, 70, $row('zoya')->id)
            ->set('payAmount', '0')->call('savePayment');
        $this->assertNull($row('zoya'));
        $this->assertSame('Zoya', $this->receipts()->orderByDesc('id')->value('label'));

        // The receipts always add up to the payments.
        $this->assertEquals(
            DB::table('super_admin_fee_payments')->sum('amount'),
            $this->receipts()->sum('amount')
        );
        $this->assertEquals(190, $this->receipts()->sum('amount'));   // Kiran 70 + Ravi 70 + Aman 50
    }

    public function test_the_payments_so_far_become_receipts_one_per_amount_received(): void
    {
        $per = $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 70]);
        $mon = $this->monthly(2, ['2026-04' => 1000, '2026-05' => 1000]);
        $s   = $this->alphaStudents();
        $row = fn ($values) => DB::table('super_admin_fee_payments')->insert($values + [
            'academic_year' => self::YEAR, 'payment_mode' => 'cash', 'payment_date' => '2026-10-12', 'is_paid' => true,
        ]);
        // Saved together in one Amount Received …
        $row(['organization_id' => 1, 'super_admin_fee_structure_id' => $per->id, 'student_detail_id' => $s['kiran'], 'amount' => 70, 'receipt_number' => 'SARC-1-2026-00180', 'created_at' => '2026-10-12 10:39:10']);
        $row(['organization_id' => 1, 'super_admin_fee_structure_id' => $per->id, 'student_detail_id' => $s['ravi'], 'amount' => 70, 'receipt_number' => 'SARC-1-2026-00181', 'created_at' => '2026-10-12 10:39:10']);
        $row(['organization_id' => 1, 'super_admin_fee_structure_id' => $per->id, 'student_detail_id' => $s['zoya'], 'amount' => 20, 'receipt_number' => 'SARC-1-2026-00182', 'created_at' => '2026-10-12 10:39:11', 'is_paid' => false]);
        // … and one month recorded on its own on another day.
        $row(['organization_id' => 2, 'super_admin_fee_structure_id' => $mon->id, 'installment_period' => '2026-04', 'amount' => 1000, 'receipt_number' => 'SARC-2-2026-00001', 'payment_date' => '2026-09-01', 'created_at' => '2026-09-01 12:00:00']);

        Schema::drop('super_admin_fee_receipts');
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();

        $receipts = $this->receipts()->orderBy('payment_date')->get();
        $this->assertCount(2, $receipts);
        $this->assertEquals(1000, $receipts[0]->amount);
        $this->assertSame('Apr 2026', $receipts[0]->label);
        $this->assertEquals(160, $receipts[1]->amount);
        $this->assertSame('3 students', $receipts[1]->label);
        $this->assertSame(3, (int) $receipts[1]->items);
        $this->assertSame('SARC-1-2026-00180 – SARC-1-2026-00182', $receipts[1]->receipt_number);
        $this->assertSame('2026-10-12', substr($receipts[1]->payment_date, 0, 10));
    }

    public function test_the_school_view_is_one_page_in_the_home_look(): void
    {
        $this->signIn();
        $this->alphaStudents();
        $per = $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);
        SuperAdminFeeAllocator::apply($per, 1200, 'cash', '2026-10-12');
        $this->monthly(2, ['2026-04' => 1000, '2026-05' => 1000]);

        $text = fn ($page) => preg_replace('/\s+/', ' ', strip_tags($page->html()));

        $alpha = $text(Livewire::test(Fees::class)->call('selectSchool', 1));
        foreach (['Alpha School', 'Per Student · Annual Platform Fee · 2026-2027', 'Overview', 'To Collect ₹2,000',
                  'Collected ₹1,200 60% of the fee', 'Students Paid 2 / 4', '1 part paid · 1 pending',
                  'Collected · Apr 26 – Mar 27 ₹1,200', 'Fee Structure', 'Amount ₹500 / student',
                  'Students All Classes'] as $seen) {
            $this->assertStringContainsString($seen, $alpha);
        }
        $this->assertStringNotContainsString('View Fee', $alpha);   // no tabs: one page

        // Class wise by default — the order Amount Received marks students
        // paid in — with the class and the section's last letter under the
        // name (2026-10-13); A to Z on asking.
        $this->assertStringContainsString('1 Kiran Class 1 (A) · Adm: — ₹500 ₹500 Paid Edit 2 Ravi Class 1 (A) · Adm: — ₹500 ₹500 Paid Edit 3 Zoya Class 1 (B) · Adm: — ₹500 ₹200 ₹300 due Partial', $alpha);
        $this->assertStringContainsString('4 Aman Class 2 (A) · Adm: — ₹500 ₹0 Pending', $alpha);
        $byName = $text(Livewire::test(Fees::class)->call('selectSchool', 1)->set('viewFeeSort', 'name'));
        $this->assertStringContainsString('1 Aman Class 2 (A) · Adm: — ₹500 ₹0 Pending', $byName);
        $this->assertStringContainsString('4 Zoya Class 1 (B) · Adm: — ₹500 ₹200 ₹300 due Partial', $byName);

        $beta = $text(Livewire::test(Fees::class)->call('selectSchool', 2));
        foreach (['Monthly · Annual Platform Fee · 2026-2027', 'Months Paid 0 / 2', 'Months', 'Apr 2026 ₹1,000 ₹0 Pending', 'Jun 2026 — ₹0 No fee'] as $seen) {
            $this->assertStringContainsString($seen, $beta);
        }
    }

    public function test_lists_show_the_board_without_its_medium(): void
    {
        $this->assertSame('UP BOARD', \App\Support\BoardName::short('UP BOARD (HINDI & ENGLISH MEDIUM)'));
        $this->assertSame('UP BOARD', \App\Support\BoardName::short('UP BOARD (ENGLISH MEDIUM)'));
        $this->assertSame('CBSE', \App\Support\BoardName::short('CBSE'));
        $this->assertSame('', \App\Support\BoardName::short(null));

        $this->signIn();
        DB::table('organizations')->where('id', 1)->update(['education_board' => 'UP BOARD (HINDI & ENGLISH MEDIUM)']);
        $html = Livewire::test(Fees::class)->html();
        // The list cell; the Board filter keeps the saved values it matches on.
        $this->assertMatchesRegularExpression('/whitespace-nowrap">UP BOARD<\/td>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<td[^>]*>[^<]*ENGLISH MEDIUM/', $html);
    }

    // ─── School view → WhatsApp (2026-10-15) ──────────────────────────────────

    public function test_the_school_page_opens_with_whatsapp_switches_and_a_months_messages(): void
    {
        $this->signIn();
        $text = fn ($page) => preg_replace('/\s+/', ' ', strip_tags($page->html()));

        // Before the migration there is no card.
        $this->assertStringNotContainsString('toggleWhatsApp', Livewire::test(Fees::class)->call('selectSchool', 1)->html());

        (require base_path('database/migrations/2026_10_14_200000_create_whatsapp_messages_tables.php'))->up();
        app()->forgetInstance('whatsapp_messages.tracked');
        $message = function (int $org, string $feature, string $status, string $at) {
            $charged = SchoolWhatsApp::FEATURES[$feature][1];
            WhatsAppMessage::forceCreate([
                'organization_id' => $org, 'feature' => $feature, 'template' => 't', 'status' => $status,
                'billable' => $charged, 'rate' => $charged ? 0.55 : 0, 'created_at' => $at, 'updated_at' => $at,
            ]);
        };
        $message(1, 'absent_alert', 'sent', '2026-10-01 09:00:00');
        $message(1, 'absent_alert', 'delivered', '2026-10-02 09:00:00');
        $message(1, 'absent_alert', 'failed', '2026-10-03 09:00:00');
        $message(1, 'fee_received', 'read', '2026-10-04 09:00:00');
        $message(1, 'credentials', 'sent', '2026-10-05 09:00:00');
        $message(1, 'welcome', 'sent', '2026-10-06 09:00:00');
        $message(1, 'fee_received', 'sent', '2026-09-20 09:00:00');
        $message(2, 'absent_alert', 'sent', '2026-10-01 09:00:00');

        $page = Livewire::test(Fees::class)->call('selectSchool', 1);
        $html = $page->html();
        $this->assertLessThan(strpos($html, '>Overview</h2>'), strpos($html, '>WhatsApp</h2>'));   // first on the page
        $this->assertStringContainsString("wire:click=\"toggleWhatsApp('absent_alert')\"", $html);
        $this->assertStringContainsString("wire:click=\"toggleWhatsApp('fee_received')\"", $html);
        $this->assertStringNotContainsString("toggleWhatsApp('credentials')", $html);
        $this->assertStringContainsString('role="switch" aria-checked="false"', $html);
        foreach (['Absent alert 2 Off · ₹0.55 each', 'Fee received 1 Off · ₹0.55 each', 'Credentials 1 Send Credentials · ₹0.55 each',
                  'Welcome (free) 1 On adding someone', 'Total · Oct 2026 ₹2.20 4 charged · 1 not delivered',
                  '₹0.55 a message · not charged when it is not delivered'] as $seen) {
            $this->assertStringContainsString($seen, $text($page));
        }

        // On for this school only, and off again; Credentials has no switch.
        $page->call('toggleWhatsApp', 'absent_alert');
        $this->assertTrue(SchoolWhatsApp::enabled(1, 'absent_alert'));
        $this->assertFalse(SchoolWhatsApp::enabled(2, 'absent_alert'));
        $this->assertStringContainsString('Absent alert 2 On · ₹0.55 each', $text($page));
        $this->assertStringContainsString('role="switch" aria-checked="true"', $page->html());
        $page->call('toggleWhatsApp', 'absent_alert')->call('toggleWhatsApp', 'credentials');
        $this->assertFalse(SchoolWhatsApp::enabled(1, 'absent_alert'));
        $this->assertSame(['absent_alert'], DB::table('whatsapp_school_features')->pluck('feature')->all());

        // Another month, and another school's own figures.
        $this->assertStringContainsString('Total · Sep 2026 ₹0.55 1 charged', $text($page->set('waMonth', '2026-09')));
        $this->assertStringContainsString('Total · Oct 2026 ₹0.55 1 charged', $text(Livewire::test(Fees::class)->call('selectSchool', 2)));
    }

    // ─── School view → Payments: View and Edit (2026-10-14) ───────────────────

    /** The receipts table with its allocation column (the migration of 14 Oct 2026). */
    private function trackAllocation(): void
    {
        (require base_path('database/migrations/2026_10_14_120000_add_allocation_to_super_admin_fee_receipts.php'))->up();
    }

    private function assertReceiptsAddUp(): void
    {
        $this->assertEquals(
            round((float) DB::table('super_admin_fee_payments')->sum('amount'), 2),
            round((float) $this->receipts()->sum('amount'), 2)
        );
    }

    public function test_a_payment_edited_from_the_school_page_takes_back_or_adds_where_it_went(): void
    {
        $this->trackAllocation();
        $this->signIn();
        $s   = $this->alphaStudents();
        $fs  = $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);
        $row = fn ($who) => $this->payment($fs->id, ['student_detail_id' => $s[$who]]);

        SuperAdminFeeAllocator::apply($fs, 1200, 'cash', '2026-10-12', 'First lot');   // Kiran 500, Ravi 500, Zoya 200
        SuperAdminFeeAllocator::apply($fs, 600, 'upi', '2026-10-15');                  // Zoya +300, Aman 300
        [$first, $second] = $this->receipts()->orderBy('id')->get()->all();
        $this->assertSame([[$row('kiran')->id, 500], [$row('ravi')->id, 500], [$row('zoya')->id, 200]], json_decode($first->allocation, true));

        // The Payments card: date, amount, mode, school, remark; View and Edit.
        $text = fn ($page) => preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $page = Livewire::test(Fees::class)->call('selectSchool', 1);
        $seen = $text($page);
        foreach (['Payments Payments · 2026-2027 2 payments · ₹1,800',
                  'S.No Date Amount Mode School Remark Actions',
                  '1 15 Oct 2026 ₹600 UPI Alpha School —',
                  '2 12 Oct 2026 ₹1,200 Cash Alpha School First lot'] as $part) {
            $this->assertStringContainsString($part, $seen);
        }
        $page->assertSeeHtml('wire:click="viewPayment(' . $second->id . ')"')
            ->assertSeeHtml('wire:click="openEditPayment(' . $first->id . ')"');

        $view = $text($page->call('viewPayment', $second->id));
        foreach (['Payment ₹600 · 15 Oct 2026', 'School Alpha School', 'Date 15 Oct 2026', 'Amount ₹600.00',
                  'Payment Mode UPI', 'Remark —', 'For 2 students'] as $part) {
            $this->assertStringContainsString($part, $view);
        }

        // Edit opens the Amount Received form filled in with the payment.
        $page->call('openEditPayment', $first->id)
            ->assertSet('viewPaymentId', null)
            ->assertSet('editPayAmount', '1200')
            ->assertSet('editPayMode', 'cash')
            ->assertSet('editPayDate', '2026-10-12')
            ->assertSet('editPayRemark', 'First lot');
        $this->assertStringContainsString('Edit Payment', $text($page));
        $this->assertStringContainsString('Amount Received Was ₹1,200 on 12 Oct 2026', $text($page));

        // Less: taken back from what it paid, last first — Zoya's 200, then 100 of Ravi's.
        $this->assertStringContainsString('₹300 less — taken back from what this payment paid, the last students first.',
            $text($page->set('editPayAmount', '900')));
        $page->set('editPayDate', '2026-10-13')->call('saveEditPayment')->assertHasNoErrors()->assertSet('editPaymentId', null);

        $this->assertEquals(500, $row('kiran')->amount);
        $this->assertEquals(400, $row('ravi')->amount);
        $this->assertEquals(0, $row('ravi')->is_paid);
        $this->assertEquals(300, $row('zoya')->amount);
        $this->assertEquals(300, $row('aman')->amount);
        $first = $this->receipts()->find($first->id);
        $this->assertEquals(900, $first->amount);
        $this->assertSame('2026-10-13', substr($first->payment_date, 0, 10));
        $this->assertSame('2 students', $first->label);
        $this->assertSame([[$row('kiran')->id, 500], [$row('ravi')->id, 400]], json_decode($first->allocation, true));
        // Its rows take the new date; Zoya's last money came on the 15th.
        $this->assertSame('2026-10-13', substr($row('kiran')->payment_date, 0, 10));
        $this->assertSame('2026-10-13', substr($row('ravi')->payment_date, 0, 10));
        $this->assertSame('2026-10-15', substr($row('zoya')->payment_date, 0, 10));
        $this->assertSame(2, $this->receipts()->count());   // no extra receipt rows
        $this->assertReceiptsAddUp();

        // More: this payment's own students first (Zoya, Aman), then the fee's order.
        $page->call('openEditPayment', $second->id)->set('editPayAmount', '1000');
        $this->assertStringContainsString('₹400 more — goes to the next students due, class by class:', $text($page));
        $page->call('saveEditPayment')->assertHasNoErrors();
        $this->assertEquals(500, $row('zoya')->amount);
        $this->assertEquals(500, $row('aman')->amount);
        $this->assertEquals(400, $row('ravi')->amount);
        $this->assertEquals(1000, $this->receipts()->find($second->id)->amount);
        $this->assertReceiptsAddUp();

        // Not more than is due.
        $page->call('openEditPayment', $second->id)->set('editPayAmount', '2000')->call('saveEditPayment')
            ->assertHasErrors('editPayAmount');
        $this->assertEquals(1000, $this->receipts()->find($second->id)->amount);

        // Another school's and a sum taken back are not listed.
        DB::table('super_admin_fee_receipts')->insert([
            ['organization_id' => 2, 'amount' => 999, 'academic_year' => self::YEAR, 'payment_mode' => 'cash', 'payment_date' => '2026-10-14', 'items' => 1],
            ['organization_id' => 1, 'amount' => -50, 'academic_year' => self::YEAR, 'payment_mode' => 'cash', 'payment_date' => '2026-10-14', 'items' => 1],
        ]);
        $seen = $text(Livewire::test(Fees::class)->call('selectSchool', 1));
        $this->assertStringContainsString('2 payments · ₹1,900', $seen);
        $this->assertStringNotContainsString('₹999', $seen);
    }

    public function test_a_payment_saved_before_knows_no_rows_and_gives_back_from_the_last_paid(): void
    {
        $this->trackAllocation();
        $this->signIn();
        $fs = $this->monthly(1, ['2026-04' => 1000, '2026-05' => 1000, '2026-06' => 1000]);
        foreach (['2026-04' => 1000, '2026-05' => 500] as $month => $amount) {
            DB::table('super_admin_fee_payments')->insert([
                'organization_id' => 1, 'super_admin_fee_structure_id' => $fs->id, 'installment_period' => $month,
                'amount' => $amount, 'academic_year' => self::YEAR, 'payment_mode' => 'cash',
                'payment_date' => '2026-09-01', 'is_paid' => $amount >= 1000,
            ]);
        }
        $receiptId = DB::table('super_admin_fee_receipts')->insertGetId([
            'organization_id' => 1, 'super_admin_fee_structure_id' => $fs->id, 'amount' => 1500,
            'academic_year' => self::YEAR, 'payment_mode' => 'cash', 'payment_date' => '2026-09-01',
            'label' => 'Apr 2026 – May 2026', 'items' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $month = fn ($key) => $this->payment($fs->id, ['installment_period' => $key]);

        $page = Livewire::test(Fees::class)->call('selectSchool', 1)->call('openEditPayment', $receiptId);
        $page->set('editPayAmount', '1200')->set('editPayDate', '2026-09-05')->set('editPayMode', 'online')
            ->call('saveEditPayment')->assertHasNoErrors();

        $this->assertEquals(1000, $month('2026-04')->amount);
        $this->assertEquals(200, $month('2026-05')->amount);
        $this->assertEquals(0, $month('2026-05')->is_paid);
        // The only payment that day: its rows take the new date and mode.
        $this->assertSame('2026-09-05', substr($month('2026-04')->payment_date, 0, 10));
        $this->assertSame('online', $month('2026-05')->payment_mode);
        $this->assertNull($this->receipts()->find($receiptId)->allocation);
        $this->assertReceiptsAddUp();

        // More goes to May, then June.
        $page->call('openEditPayment', $receiptId)->set('editPayAmount', '2500')->call('saveEditPayment')->assertHasNoErrors();
        $this->assertEquals(1000, $month('2026-05')->amount);
        $this->assertEquals(1, $month('2026-05')->is_paid);
        $this->assertEquals(500, $month('2026-06')->amount);
        $this->assertReceiptsAddUp();
    }

    public function test_editing_a_payment_works_before_the_allocation_column_exists(): void
    {
        $this->signIn();
        $s  = $this->alphaStudents();
        $fs = $this->structure(['organization_id' => 1, 'fee_type' => 'per_student', 'amount' => 500]);

        SuperAdminFeeAllocator::apply($fs, 1200, 'cash', '2026-10-12');   // Kiran 500, Ravi 500, Zoya 200
        $receipt = \App\Models\SuperAdmin\SuperAdminFeeReceipt::first();
        $this->assertNull($receipt->allocation);

        $result = SuperAdminFeeAllocator::editReceipt($receipt, 900, 'cash', '2026-10-12');
        $this->assertTrue($result['ok']);
        // The last paid in the fee's order give back: Zoya's 200 (now pending), then 100 of Ravi's.
        $this->assertNull($this->payment($fs->id, ['student_detail_id' => $s['zoya']]));
        $this->assertEquals(400, $this->payment($fs->id, ['student_detail_id' => $s['ravi']])->amount);
        $this->assertEquals(500, $this->payment($fs->id, ['student_detail_id' => $s['kiran']])->amount);
        $this->assertReceiptsAddUp();
    }
}
