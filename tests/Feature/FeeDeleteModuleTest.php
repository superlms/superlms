<?php

namespace Tests\Feature;

use App\Livewire\Admin\Fee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Schools → Modules → Delete Fee: off by default; on, the school
 * admin's Fee → Payments has a Delete beside each payment taken at the school
 * (asked first), never on one paid online in the app, never another school's,
 * and never on the accounts desk.
 */
class FeeDeleteModuleTest extends TestCase
{
    private int $org = 3;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);

        Schema::create('module_organization', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('module_key'); $t->boolean('enabled')->default(true); $t->timestamps();
        });
        $this->forgetModuleTable();
        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->string('image')->nullable();
            $t->boolean('is_active')->default(true); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('father_name')->nullable(); $t->string('mother_name')->nullable();
            $t->string('admission_no')->nullable(); $t->string('roll_no')->nullable(); $t->string('phone')->nullable();
            $t->boolean('transportation_required')->default(false); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable(); $t->string('fee_name')->nullable(); $t->string('fee_type')->default('academic');
            $t->decimal('amount', 12, 2)->default(0); $t->string('academic_year')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->string('fee_type')->default('academic'); $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('penalty_amount', 12, 2)->default(0); $t->decimal('waiver_amount', 12, 2)->default(0);
            $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable(); $t->string('receipt_number')->nullable();
            $t->text('remark')->nullable(); $t->string('submitted_by')->nullable(); $t->timestamps();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->string('fee_type')->default('all');
            $t->string('concession_type')->default('amount'); $t->decimal('value', 12, 2)->default(0); $t->boolean('is_penalty')->default(false);
            $t->string('reason')->nullable(); $t->string('academic_year')->nullable(); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name')->nullable(); $t->decimal('monthly_fee', 12, 2)->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id'); $t->unsignedBigInteger('student_detail_id');
            $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id')->nullable(); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 12, 2)->default(0); $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable();
            $t->string('academic_year')->nullable(); $t->text('remark')->nullable(); $t->unsignedBigInteger('submitted_by')->nullable();
            $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('fee_payment_requests', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id')->nullable(); $t->string('fee_type')->default('academic');
            $t->decimal('amount', 12, 2)->default(0); $t->date('paid_on')->nullable(); $t->string('status')->default('pending'); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type')->default('academic'); $t->integer('payment_serial')->default(1);
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable(); $t->date('due_date')->nullable();
            $t->decimal('penalty_per_day', 10, 2)->default(0); $t->decimal('fee_percent', 5, 2)->default(0); $t->decimal('amount', 12, 2)->nullable();
            $t->boolean('is_token')->default(false); $t->string('academic_year', 20)->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        app()->forgetInstance('fee_structures.student_rows');

        DB::table('organizations')->insert([['id' => $this->org, 'name' => 'Demo School'], ['id' => 4, 'name' => 'Other School']]);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    protected function tearDown(): void
    {
        $this->forgetModuleTable();
        parent::tearDown();
    }

    /** The model remembers whether the modules table exists for the whole process. */
    private function forgetModuleTable(): void
    {
        $known = new \ReflectionProperty(Organization::class, 'moduleTableExists');
        $known->setAccessible(true);
        $known->setValue(null, null);
    }

    /** Ravi paid 500 in cash and 700 online in the app today; a bus fee of 300 at the counter; another school's 900. */
    private function payments(): array
    {
        $std  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 2']);
        $ravi = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'full_name' => 'Ravi', 'admission_no' => 'A-1']);
        $row  = fn (array $v) => DB::table('fee_payments')->insertGetId($v + [
            'organization_id' => $this->org, 'student_detail_id' => $ravi, 'standard_id' => $std, 'fee_type' => 'academic', 'payment_date' => now()->toDateString(),
        ]);

        return [
            'cash'   => $row(['amount' => 500, 'payment_mode' => 'cash', 'receipt_number' => 'RCT-3-2026-00001', 'submitted_by' => 'Head']),
            'online' => $row(['amount' => 700, 'payment_mode' => 'online', 'receipt_number' => 'RCT-3-2026-00002', 'submitted_by' => 'PhonePe']),
            'bus'    => DB::table('transport_fee_payments')->insertGetId(['organization_id' => $this->org, 'student_detail_id' => $ravi, 'amount' => 300,
                'payment_mode' => 'cash', 'payment_date' => now()->toDateString(), 'receipt_number' => 'TRCT-3-2026-00001', 'submitted_by' => 1]),
            'other'  => DB::table('fee_payments')->insertGetId(['organization_id' => 4, 'student_detail_id' => $ravi, 'standard_id' => $std, 'amount' => 900,
                'payment_mode' => 'cash', 'payment_date' => now()->toDateString(), 'receipt_number' => 'RCT-4-2026-00001', 'submitted_by' => 'X']),
        ];
    }

    private function turnOn(): void
    {
        DB::table('module_organization')->insert(['organization_id' => $this->org, 'module_key' => 'fee_delete', 'enabled' => true]);
    }

    public function test_it_is_a_module_off_until_the_super_admin_turns_it_on(): void
    {
        $def = config('modules.fee_delete');
        $this->assertSame('Delete Fee', $def['label']);
        $this->assertFalse($def['default']);
        $this->assertSame([], $def['links']);
        $this->assertSame(['fees', 'fee_delete'], array_slice(array_keys(config('modules')), 0, 2));   // listed beside Fees
        $this->assertSame('notification', array_key_last(config('modules')));

        $this->assertFalse(Organization::find($this->org)->hasModule('fee_delete'));
        $this->assertArrayHasKey('fee_delete', (new \App\Livewire\SuperAdmin\Schools())->shownModules());

        // Off: no Delete on the list, and asking does nothing.
        $p = $this->payments();
        $page = Livewire::test(Fee::class)->call('showTab', 'payments');
        $html = $page->html();
        $this->assertStringContainsString('₹500.00', $html);
        $this->assertStringNotContainsString('askDeleteFeePayment', $html);
        $page->call('askDeleteFeePayment', 'fee', $p['cash'])->call('deleteFeePayment');
        $this->assertNull($page->get('feePaymentToDelete'));
        $this->assertTrue(DB::table('fee_payments')->where('id', $p['cash'])->exists());
    }

    public function test_on_a_payment_taken_at_the_school_is_deleted_after_asking(): void
    {
        $this->turnOn();
        $p = $this->payments();

        $page = Livewire::test(Fee::class)->call('showTab', 'payments');
        $html = $page->html();
        $this->assertStringContainsString("askDeleteFeePayment('fee', {$p['cash']})", $html);
        $this->assertStringContainsString("askDeleteFeePayment('transport', {$p['bus']})", $html);
        // Paid online in the app: no Delete, only a greyed one saying why.
        $this->assertStringNotContainsString("askDeleteFeePayment('fee', {$p['online']})", $html);
        $this->assertStringContainsString('Paid online in the app — cannot be deleted', $html);

        // Asked first: whose, how much, which receipt.
        $page->call('askDeleteFeePayment', 'fee', $p['cash']);
        $this->assertSame(['kind' => 'fee', 'id' => $p['cash']], array_intersect_key($page->get('feePaymentToDelete'), ['kind' => 1, 'id' => 1]));
        $html = $page->html();
        $this->assertStringContainsString('Delete this payment?', $html);
        $this->assertStringContainsString('₹500.00', $html);
        $this->assertStringContainsString('RCT-3-2026-00001', $html);
        $this->assertStringContainsString('Ravi', $html);

        // Cancel keeps it; Delete takes it away.
        $page->call('keepFeePayment');
        $this->assertNull($page->get('feePaymentToDelete'));
        $this->assertStringNotContainsString('Delete this payment?', $page->html());
        $this->assertTrue(DB::table('fee_payments')->where('id', $p['cash'])->exists());

        $page->call('askDeleteFeePayment', 'fee', $p['cash'])->call('deleteFeePayment');
        $this->assertFalse(DB::table('fee_payments')->where('id', $p['cash'])->exists());
        $this->assertStringNotContainsString("askDeleteFeePayment('fee', {$p['cash']})", $page->html());

        // The bus fee taken at the counter too.
        $page->call('askDeleteFeePayment', 'transport', $p['bus'])->call('deleteFeePayment');
        $this->assertFalse(DB::table('transport_fee_payments')->where('id', $p['bus'])->exists());
    }

    public function test_never_one_paid_in_the_app_another_school_s_or_on_the_accounts_desk(): void
    {
        $this->turnOn();
        $p = $this->payments();

        $page = Livewire::test(Fee::class)->call('showTab', 'payments');
        $page->call('askDeleteFeePayment', 'fee', $p['online'])->call('deleteFeePayment');
        $page->call('askDeleteFeePayment', 'fee', $p['other'])->call('deleteFeePayment');
        $page->call('askDeleteFeePayment', 'nonsense', $p['cash'])->call('deleteFeePayment');
        $this->assertSame(3, DB::table('fee_payments')->count());
        // What the box holds cannot be set from the browser.
        try {
            $page->set('feePaymentToDelete', ['kind' => 'fee', 'id' => $p['online']]);
            $this->fail('The payment in the box was set from outside.');
        } catch (\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException) {
        }

        // PhonePe Autopay is the app's too.
        DB::table('fee_payments')->where('id', $p['online'])->update(['submitted_by' => 'PhonePe Autopay']);
        $page->call('askDeleteFeePayment', 'fee', $p['online']);
        $this->assertNull($page->get('feePaymentToDelete'));

        // The accounts desk shares the list but never deletes.
        $accounts = new \App\Livewire\Accounts\Payments();
        $this->assertFalse($accounts->canDeleteFeePayments());
        $this->assertTrue((new Fee())->canDeleteFeePayments());
    }
}
