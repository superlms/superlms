<?php

namespace Tests\Feature;

use App\Livewire\Admin\Fee;
use App\Livewire\Admin\FeeStructure;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The admin's Fee page: View Fee and Fee Submission are one tab (the ledger,
 * Submit Fee in the header); QR Payments carries Payment QR as its second tab;
 * Analytics stays, in the Dashboard's look with more detail; the ledger lists
 * the class's fees, then past years' dues, each with its total, then both;
 * and Add Dues takes earlier years' fees for a student, each named, with +.
 */
class FeeMergedTabsTest extends TestCase
{
    private int $org = 3;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('f', 32))]);

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
        Schema::create('payment_qr_codes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('qr_path')->nullable(); $t->string('upi_id')->nullable();
            $t->string('payee_name')->nullable(); $t->text('instructions')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type')->default('academic'); $t->integer('payment_serial')->default(1);
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable(); $t->date('due_date')->nullable();
            $t->decimal('penalty_per_day', 10, 2)->default(0); $t->decimal('fee_percent', 5, 2)->default(0); $t->decimal('amount', 12, 2)->nullable();
            $t->boolean('is_token')->default(false); $t->string('academic_year', 20)->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        app()->forgetInstance('fee_structures.student_rows');

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Demo School']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    /** Class 2 with a 1,000 fee head; Ravi owes 300 last year and 200 from 2023-24. */
    private function school(): array
    {
        $std  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 2']);
        $sec  = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'name' => 'A']);
        $ravi = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'full_name' => 'Ravi', 'father_name' => 'Mr Rao']);
        $sita = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'full_name' => 'Sita']);
        DB::table('fee_structures')->insert([
            ['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => null, 'student_detail_id' => null, 'fee_name' => 'Tuition', 'fee_type' => 'academic', 'amount' => 1000],
            ['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'student_detail_id' => $ravi, 'fee_name' => 'Last Year Dues', 'fee_type' => 'academic', 'amount' => 300],
            ['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'student_detail_id' => $ravi, 'fee_name' => '2023-24 Fee', 'fee_type' => 'academic', 'amount' => 200],
        ]);

        return compact('std', 'sec', 'ravi', 'sita');
    }

    public function test_cards_are_merged_and_old_links_land_on_them(): void
    {
        $this->school();
        $page = Livewire::test(Fee::class);
        $html = $page->html();

        $this->assertStringContainsString('View &amp; Submit Fee', $html);
        $this->assertStringNotContainsString('>Fee Submission</h3>', $html);
        $this->assertStringNotContainsString('>Payment QR</h3>', $html);
        $this->assertStringContainsString('>QR Payments</h3>', $html);
        $this->assertStringContainsString('>Analytics</h3>', $html);                // kept

        $page->call('showTab', 'fee_submission');
        $this->assertSame('view_fee', $page->get('activeTab'));
        $page->call('showTab', 'payment_qr');
        $this->assertSame(['qr_payments', 'qr'], [$page->get('activeTab'), $page->get('qrSubTab')]);
        $html = $page->html();
        $this->assertStringContainsString("wire:click=\"setQrSubTab('payments')\"", $html);
        $this->assertStringContainsString('Add QR', $html);                          // the QR tab's button
        $this->assertStringNotContainsString('wire:model.live="qrStatus"', $html);   // no payments filter here

        $html = $page->call('setQrSubTab', 'payments')->html();
        $this->assertStringContainsString('wire:model.live="qrStatus"', $html);
        $this->assertStringNotContainsString('Add QR', $html);
    }

    public function test_view_and_submit_fee_in_one_tab(): void
    {
        $s    = $this->school();
        $page = Livewire::test(Fee::class)->call('showTab', 'view_fee');
        $this->assertStringContainsString('wire:click="openFeeSubmit" disabled', $page->html());   // no student yet

        $page->set('viewStudentStandardId', (string) $s['std'])->set('viewStudentId', (string) $s['ravi']);
        $this->assertSame((string) $s['ravi'], (string) $page->get('selectedStudentId'));
        $html = $page->html();
        $this->assertStringNotContainsString('wire:click="openFeeSubmit" disabled', $html);
        $this->assertStringContainsString('Net Payable', $html);

        // The ledger: the class's fee and its total, past years' dues and theirs, then both.
        $this->assertMatchesRegularExpression('/Academic total<\/span>\s*<span[^>]*>₹1,000\.00/', $html);
        $this->assertStringContainsString("Past years' dues", $html);
        $this->assertStringContainsString('2023-24 Fee', $html);
        $this->assertMatchesRegularExpression('/Dues total<\/span>\s*<span[^>]*>₹500\.00/', $html);
        $this->assertMatchesRegularExpression('/Overall total<\/span>\s*<span[^>]*>₹1,500\.00/', $html);
        // In the Mark Attendance look: plain text — no capitals labels, no chips.
        $this->assertStringNotContainsString('uppercase tracking-wider', $html);
        $this->assertStringNotContainsString('rounded-full text-[10px] font-semibold', $html);

        // Submit Fee collects for the same student, and the ledger follows.
        $page->call('openFeeSubmit');
        $this->assertTrue($page->get('showSubmitPanel'));
        $page->set('submitAmount', 400)->set('submitFeeType', 'academic')->set('submittedBy', 'Head')->call('submitFeePayment');
        $this->assertSame(400.0, (float) DB::table('fee_payments')->where('student_detail_id', $s['ravi'])->sum('amount'));
        $this->assertSame(400.0, (float) $page->get('studentFeeView')['academic']['paid']);
        $this->assertFalse($page->get('showSubmitPanel'));
    }

    public function test_analytics_in_the_dashboard_look(): void
    {
        $this->school();
        $page = Livewire::test(Fee::class)->call('showTab', 'analytics');
        $html = $page->html();

        foreach (['Overview', 'Collection Trend', 'Month by Month', 'By Class', 'Students'] as $section) {
            $this->assertStringContainsString('uppercase tracking-widest mb-3">' . $section . '</h2>', $html);
        }
        $this->assertCount(6, $page->get('analyticsMonthly')['points']);
        $this->assertStringContainsString('wire:key="fa-daily-', $html);
    }

    public function test_add_dues_takes_earlier_years_fees_with_plus(): void
    {
        $s    = $this->school();
        $page = Livewire::test(FeeStructure::class)->call('openDuesPanel')->set('duesStandardId', (string) $s['std']);

        // Saved ones come back into the panel.
        $this->assertSame('300', $page->get('duesAmounts')['s' . $s['ravi']]);
        $this->assertSame('2023-24 Fee', $page->get('duesExtras')['s' . $s['ravi']][0]['name']);
        $this->assertStringContainsString('wire:click="addDuesExtra(' . $s['sita'] . ')"', $page->html());

        // + for Sita: a name without an amount is asked for.
        $page->call('addDuesExtra', $s['sita'])->set('duesExtras.s' . $s['sita'] . '.0.name', '2024-25 Fee')->call('saveDues');
        $page->assertHasErrors(['duesExtras.s' . $s['sita'] . '.0.amount']);

        $page->set('duesExtras.s' . $s['sita'] . '.0.amount', '750')
            ->call('addDuesExtra', $s['sita'])
            ->set('duesExtras.s' . $s['sita'] . '.1.name', '2023-24 Fee')->set('duesExtras.s' . $s['sita'] . '.1.amount', '120')
            ->call('removeDuesExtra', $s['ravi'], 0)                                 // Ravi's 2023-24 goes
            ->call('saveDues');
        $page->assertHasNoErrors();

        $own = fn ($id) => DB::table('fee_structures')->where('student_detail_id', $id)->orderBy('id')->pluck('amount', 'fee_name')->map(fn ($v) => (float) $v)->all();
        $this->assertSame(['Last Year Dues' => 300.0], $own($s['ravi']));
        $this->assertSame(['2024-25 Fee' => 750.0, '2023-24 Fee' => 120.0], $own($s['sita']));
    }
}
