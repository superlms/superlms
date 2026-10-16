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
        $this->assertStringNotContainsString('>Fee Submission</span>', $html);
        $this->assertStringNotContainsString('>Payment QR</span>', $html);
        $this->assertStringContainsString('>QR Payments</span>', $html);
        $this->assertStringContainsString('>Analytics</span>', $html);              // kept
        // Quick Links tiles: a round icon and the name — no description under it.
        $this->assertStringContainsString('wire:key="fee-tile-view_fee"', $html);
        $this->assertStringContainsString('grid grid-cols-3 auto-rows-fr', $html);              // 3 x 3, filling the screen
        $this->assertSame(9, substr_count($html, 'wire:key="fee-tile-'));
        $this->assertStringNotContainsString("A student's fee ledger — collect fee from it", $html);

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

    public function test_analytics_counts_a_student_as_view_fee_does(): void
    {
        $s = $this->school();   // Ravi: 1,000 this year + 500 of dues; Sita: 1,000
        DB::table('fee_concessions')->insert(['organization_id' => $this->org, 'student_detail_id' => $s['ravi'],
            'fee_type' => 'academic', 'concession_type' => 'amount', 'value' => 100]);
        // Ravi's bus: 500 for April; an old route he is still listed on is not billed again.
        $months = array_fill_keys(['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'], false);
        foreach ([[500, true], [300, false]] as [$fee, $active]) {
            $route = DB::table('transportations')->insertGetId(['organization_id' => $this->org, 'route_name' => 'R' . $fee, 'monthly_fee' => $fee, 'is_active' => $active]);
            DB::table('transportation_students')->insert(['organization_id' => $this->org, 'transportation_id' => $route,
                'student_detail_id' => $s['ravi'], 'billable_months' => json_encode(['apr' => true] + $months)]);
        }
        // Ravi pays all of it: 1,400 academic (booked under an old class) and the 500 bus.
        DB::table('fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => $s['ravi'], 'standard_id' => 999,
            'fee_type' => 'academic', 'amount' => 1400, 'payment_mode' => 'cash', 'payment_date' => now()->toDateString()]);
        DB::table('transport_fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => $s['ravi'],
            'amount' => 500, 'payment_mode' => 'cash', 'payment_date' => now()->toDateString()]);

        // View Fee: Ravi owes nothing.
        $sv = Livewire::test(Fee::class)->call('showTab', 'view_fee')
            ->set('viewStudentStandardId', (string) $s['std'])->set('viewStudentId', (string) $s['ravi'])->get('studentFeeView');
        $this->assertSame([1900.0, 1900.0, 0.0], [$sv['totals']['net'], $sv['totals']['paid'], $sv['totals']['remaining']]);

        // Analytics: Ravi paid up, Sita owes her 1,000; billable is this year's
        // academic fee (900 + 1,000), the dues (500) and the bus (500).
        $page = Livewire::test(Fee::class)->call('showTab', 'analytics');
        $a = $page->get('analyticsSummary');
        $this->assertSame([1900.0, 500.0, 500.0, 2900.0], [$a['year_billable'], $a['dues_billable'], $a['transport_billable'], $a['total_billable']]);
        $this->assertSame([1900.0, 1000.0, 65.5], [$a['total_collected'], $a['total_due'], $a['rate']]);
        $this->assertSame([1, 1], [$a['fully_paid'], $a['defaulters']]);

        $text = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('Total Billable ₹2,900 Academic + transport + ₹500 dues', $text);
        $this->assertStringContainsString('Academic · This Year ₹1,900 2 students', $text);
        $this->assertStringContainsString('Transport ₹500 1 on transport', $text);
        $this->assertStringContainsString('Fully Paid Students 1 / 2', $text);
    }

    public function test_the_calculator_shows_every_student_of_the_class_or_of_the_section(): void
    {
        $s = $this->school();   // Class 2 · A: Ravi (with 500 of dues) and Sita; 1,000 a student
        $secB  = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $s['std'], 'name' => 'B']);
        DB::table('student_details')->insert(['organization_id' => $this->org, 'standard_id' => $s['std'], 'section_id' => $secB, 'full_name' => 'Mohan']);
        DB::table('fee_structures')->insert(['organization_id' => $this->org, 'standard_id' => $s['std'], 'section_id' => $secB, 'fee_name' => 'Lab', 'fee_type' => 'academic', 'amount' => 400]);
        DB::table('fee_cycles')->insert([
            ['organization_id' => $this->org, 'fee_type' => 'academic', 'payment_serial' => 0, 'is_token' => true, 'amount' => 200, 'fee_percent' => 0, 'due_date' => '2026-04-10', 'academic_year' => '2026-2027'],
            ['organization_id' => $this->org, 'fee_type' => 'academic', 'payment_serial' => 1, 'is_token' => false, 'amount' => null, 'fee_percent' => 50, 'due_date' => '2026-07-10', 'academic_year' => '2026-2027'],
            ['organization_id' => $this->org, 'fee_type' => 'academic', 'payment_serial' => 2, 'is_token' => false, 'amount' => null, 'fee_percent' => 50, 'due_date' => '2026-12-10', 'academic_year' => '2026-2027'],
        ]);
        DB::table('fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => $s['sita'], 'fee_type' => 'academic',
            'amount' => 700, 'payment_mode' => 'cash', 'payment_date' => now()->toDateString()]);

        // The listing: the fee type in plain words, View / Edit / Delete as on Students.
        $page = Livewire::test(Fee::class)->call('showTab', 'cycle');
        $html = $page->html();
        $this->assertStringContainsString('<td class="px-4 py-3 text-gray-600">Academic</td>', $html);
        $this->assertStringContainsString('wire:click="openCycleModal(', $html);
        $this->assertStringContainsString('wire:click="deleteCycle(', $html);
        $this->assertStringContainsString('Registration Fee', $html);
        $this->assertStringNotContainsString('Token', strip_tags($html));

        // The class picked: all three students — each with their own fee
        // (Mohan's section has its Lab on top), Registration Fee first.
        $page->call('switchCycleTab', 'calculator')->set('calcStandardId', (string) $s['std']);
        $html  = $page->html();
        $names = fn () => array_column($page->viewData('calcStudents'), 'name');
        $this->assertSame(['Mohan', 'Ravi', 'Sita'], $names());
        $this->assertSame([['Registration', 200.0, 600.0, 200.0], ['#1', null, 1400.0, 400.0], ['#2', null, 1400.0, 100.0]],
            array_map(fn ($r) => [$r['serial'], $r['amount'], $r['class_total'], $r['collected']], $page->viewData('calcRows')));
        $summary = $page->viewData('calcSummary');
        $this->assertSame([3, 1000.0, 1400.0, 3400.0, 700.0, 2700.0], [$summary['students'], $summary['fee_min'], $summary['fee_max'], $summary['total'], $summary['collected'], $summary['remaining']]);
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Fee per student: ₹1,000.00 – ₹1,400.00', $text);
        $this->assertStringContainsString('Sita Adm — · Roll — A ₹1,000.00 ₹700.00 ₹300.00 2 of 3 Partial', $text);

        // A section: only its students.
        $page->set('calcSectionId', (string) $s['sec']);
        $this->assertSame(['Ravi', 'Sita'], $names());
        $this->assertSame(400.0, $page->viewData('calcRows')[1]['amount']);

        // An installment: each student's share of it.
        $page->set('calcSerial', '2');
        $this->assertStringContainsString('Sita Adm — · Roll — A ₹400.00 ₹100.00 ₹300.00 Partial', preg_replace('/\s+/', ' ', strip_tags($page->html())));

        // Another class starts from all its sections again.
        $page->set('calcStandardId', (string) $s['std']);
        $this->assertSame('', $page->get('calcSectionId'));
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

    public function test_dues_are_paid_apart_and_the_receipt_shows_them(): void
    {
        // fee_payments.applies_to (the migration of 14 Oct 2026).
        (require base_path('database/migrations/2026_10_14_150000_add_applies_to_to_fee_payments.php'))->up();
        app()->forgetInstance('fee_payments.applies_to');

        $s = $this->school();   // Ravi: 1,000 this year + 500 of dues (300 Last Year Dues, 200 of 2023-24)
        foreach ([1, 2, 3, 4] as $q) {
            DB::table('fee_cycles')->insert(['organization_id' => $this->org, 'fee_type' => 'academic', 'payment_serial' => $q,
                'fee_percent' => 25, 'due_date' => '2027-0' . ($q + 1) . '-10', 'academic_year' => '2026-2027']);
        }

        $page = Livewire::test(Fee::class)->call('showTab', 'view_fee')
            ->set('viewStudentStandardId', (string) $s['std'])->set('viewStudentId', (string) $s['ravi']);

        // An ordinary academic payment goes to this year's installments.
        $page->call('openFeeSubmit')->set('submitAmount', 400)->set('submitFeeType', 'academic')->set('submittedBy', 'Head')->call('submitFeePayment');

        // Collect Fee offers Dues; any fee takes up to all that is owed (600 of
        // this year's and 500 of dues).
        $page->call('openFeeSubmit');
        $html = $page->html();
        $this->assertStringContainsString('<option value="dues"', $html);
        $page->set('submitFeeType', 'dues');
        $this->assertStringContainsString('Up to ₹1,100.00 in all.', $page->html());
        $page->set('submitAmount', 300)->set('submittedBy', 'Head');
        $this->assertStringContainsString('All of it to Last Year Dues.', $page->html());
        $page->call('submitFeePayment');

        $dues = DB::table('fee_payments')->where('student_detail_id', $s['ravi'])->orderByDesc('id')->first();
        $this->assertSame(['academic', 'dues', 300.0], [$dues->fee_type, $dues->applies_to, (float) $dues->amount]);

        // The ledger: academic paid as before (both payments); the installments
        // carry only the 400; the dues their 300.
        $sv = $page->get('studentFeeView');
        $this->assertSame(700.0, (float) $sv['academic']['paid']);
        $this->assertSame(['dues_total' => 500.0, 'dues_paid' => 300.0, 'dues_remaining' => 200.0, 'cycle_total' => 1000.0, 'cycle_paid' => 400.0, 'has' => true], $sv['dues']);
        $academicCycle = collect($sv['cycles'])->firstWhere('fee_type', 'academic');
        $this->assertSame([250.0, 150.0, 0.0, 0.0], array_map(fn ($i) => (float) $i['paid'], $academicCycle['installments']));

        $text = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('5 Last Year Dues — ₹500.00 ₹300.00 ₹200.00 Partial', $text);
        $this->assertStringContainsString('Total ₹1,500.00 ₹700.00 ₹800.00', $text);
        $this->assertStringContainsString('Dues', $text);

        // Not more than the student owes in all (800).
        $page->call('openFeeSubmit')->set('submitFeeType', 'dues')->set('submitAmount', 900)->set('submittedBy', 'Head')->call('submitFeePayment');
        $page->assertHasErrors(['submitAmount']);
        $this->assertStringContainsString('You cannot collect more than ₹800.00', $page->html());
        $this->assertSame(2, DB::table('fee_payments')->where('student_detail_id', $s['ravi'])->count());

        // The printed receipt: the payment as Last Year Dues, the dues after the
        // installments in the fee cycle, the total with both, and the overall split.
        $receipt = app(\App\Http\Controllers\Admin\FeeReceiptController::class)
            ->show(request(), $this->org, $dues->id)->render();
        $rt = preg_replace('/\s+/', ' ', strip_tags($receipt));
        $this->assertStringContainsString('Last Year Dues ₹300.00', $rt);
        $this->assertStringContainsString('5 Last Year Dues — 500.00 300.00 200.00', $rt);
        $this->assertStringContainsString('1 of 4 cleared · with dues 1,500.00 700.00 800.00', $rt);
        $this->assertStringContainsString('Academic fee (this year) 1,000.00 Paid 400.00 Last Year Dues 500.00 Paid 300.00', $rt);
        $this->assertStringContainsString('Total payable 1,500.00 Balance 800.00', $rt);

        // The app's PDF receipt says the same.
        $data = \App\Support\FeeReceipt::data(\App\Models\Admin\Fee\FeePayment::with(\App\Support\FeeReceipt::WITH)->find($dues->id));
        $this->assertTrue($data['isDues']);
        $this->assertSame(200.0, $data['dues']['dues_remaining']);

        // A student without dues: this year's fee is the whole academic fee, as before.
        $sita = Livewire::test(Fee::class)->call('showTab', 'view_fee')
            ->set('viewStudentStandardId', (string) $s['std'])->set('viewStudentId', (string) $s['sita'])->get('studentFeeView');
        $this->assertFalse($sita['dues']['has']);
        $this->assertSame(1000.0, (float) collect($sita['cycles'])->firstWhere('fee_type', 'academic')['total']);
    }

    public function test_a_collection_clears_the_fee_picked_then_transport_then_dues(): void
    {
        (require base_path('database/migrations/2026_10_14_150000_add_applies_to_to_fee_payments.php'))->up();
        app()->forgetInstance('fee_payments.applies_to');

        $s = $this->school();   // Ravi: 1,000 this year + 500 of dues
        // …and a bus billed for April alone: 500.
        $route = DB::table('transportations')->insertGetId(['organization_id' => $this->org, 'route_name' => 'R1', 'monthly_fee' => 500]);
        $months = array_fill_keys(['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'], false);
        DB::table('transportation_students')->insert(['organization_id' => $this->org, 'transportation_id' => $route,
            'student_detail_id' => $s['ravi'], 'billable_months' => json_encode(['apr' => true] + $months)]);

        $page = Livewire::test(Fee::class)->call('showTab', 'view_fee')
            ->set('viewStudentStandardId', (string) $s['std'])->set('viewStudentId', (string) $s['ravi']);

        // 1,700 as Academic: 1,000 clears this year's fee, 500 the bus, 200 the dues.
        $page->call('openFeeSubmit')->set('submitFeeType', 'academic')->set('submittedBy', 'Head')->set('submitAmount', 1700);
        $this->assertStringContainsString('Up to ₹2,000.00 in all.', $page->html());
        $this->assertStringContainsString('Goes to Academic ₹1,000.00, then Transport ₹500.00, then Last Year Dues ₹200.00.', $page->html());
        $page->call('submitFeePayment');
        $page->assertHasNoErrors();

        $fees = DB::table('fee_payments')->where('student_detail_id', $s['ravi'])->orderBy('id')->get();
        $this->assertSame([['academic', null, 1000.0], ['academic', 'dues', 200.0]],
            $fees->map(fn ($p) => [$p->fee_type, $p->applies_to, (float) $p->amount])->all());
        $this->assertSame(500.0, (float) DB::table('transport_fee_payments')->where('student_detail_id', $s['ravi'])->sum('amount'));

        $sv = $page->get('studentFeeView');
        $this->assertSame([1000.0, 0.0, 300.0], [$sv['dues']['cycle_paid'], (float) $sv['transport']['remaining'], $sv['dues']['dues_remaining']]);

        // 300 is all that is left: 400 is refused, whichever fee is picked.
        $page->call('openFeeSubmit');
        $this->assertSame('dues', $page->get('submitFeeType'));   // the only fee with anything left
        $page->set('submitAmount', 400)->set('submittedBy', 'Head')->call('submitFeePayment');
        $page->assertHasErrors(['submitAmount']);
        $this->assertStringContainsString('You cannot collect more than ₹300.00 — that is all this student still owes.', $page->html());
        $this->assertSame(2, DB::table('fee_payments')->where('student_detail_id', $s['ravi'])->count());

        // A fee with nothing left is not taken at all.
        $page->set('submitFeeType', 'transport')->set('submitAmount', 100)->call('submitFeePayment');
        $this->assertSame(500.0, (float) DB::table('transport_fee_payments')->where('student_detail_id', $s['ravi'])->sum('amount'));
    }

    public function test_a_concession_on_last_year_dues_comes_off_the_dues_alone(): void
    {
        Schema::table('fee_concessions', function (Blueprint $t) {
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('created_by')->nullable();
        });
        $s = $this->school();   // Ravi: 1,000 this year + 500 of dues; Sita: no dues

        // Add Concession: Academic, Transport or Last Year Dues — no "All Fees" on a new one.
        $page = Livewire::test(Fee::class)->call('showTab', 'concession')->call('openConcessionModal');
        $html = $page->html();
        $this->assertStringContainsString('<option value="academic">Academic</option>', $html);
        $this->assertStringContainsString('<option value="transport">Transport</option>', $html);
        $this->assertStringContainsString('<option value="dues">Last Year Dues</option>', $html);
        $this->assertStringNotContainsString('<option value="all">', $html);
        $this->assertSame('academic', $page->get('concFeeType'));

        // A student with no dues cannot have a concession on them.
        $page->set('concFilterStandard', (string) $s['std'])->set('concStudentId', (string) $s['sita'])
            ->set('concType', 'percent')->set('concValue', '50')->set('concFeeType', 'dues')->call('saveConcession');
        $page->assertHasErrors(['concFeeType']);
        $this->assertSame(0, DB::table('fee_concessions')->count());

        // Ravi: 50% off his dues, and ₹100 off the academic fee.
        $page->set('concStudentId', (string) $s['ravi'])->call('saveConcession');
        $page->assertHasNoErrors();
        $this->assertSame('dues', DB::table('fee_concessions')->value('fee_type'));
        // The list reads what it is on in plain text.
        $this->assertStringContainsString('<td class="px-4 py-3 text-gray-600">Last Year Dues</td>', $page->html());
        DB::table('fee_concessions')->insert(['organization_id' => $this->org, 'student_detail_id' => $s['ravi'],
            'fee_type' => 'academic', 'concession_type' => 'amount', 'value' => 100]);

        // 250 off the dues (50% of 500), 100 off this year's fee.
        $sv = Livewire::test(Fee::class)->call('showTab', 'view_fee')
            ->set('viewStudentStandardId', (string) $s['std'])->set('viewStudentId', (string) $s['ravi'])->get('studentFeeView');
        $this->assertSame([350.0, 1150.0], [(float) $sv['academic']['concession'], (float) $sv['academic']['net']]);
        $this->assertSame(250.0, $sv['dues']['dues_total']);
        $this->assertSame(900.0, $sv['dues']['cycle_total']);
        $this->assertSame('Last Year Dues', collect($sv['concessions'])->firstWhere('value', '50%')['scope']);
        $this->assertSame(1150.0, \App\Support\StudentFeeTotals::overall($this->org, \App\Models\Student\StudentDetail::find($s['ravi']))['total']);

        // Never more than the dues come to, and never on transport.
        $off = \App\Models\Admin\Fee\FeeConcession::takeOff(collect([
            new \App\Models\Admin\Fee\FeeConcession(['fee_type' => 'dues', 'concession_type' => 'amount', 'value' => 900]),
        ]), 'academic', 1500, 500);
        $this->assertSame([500.0, 500.0], [$off['taken'], $off['dues']]);
        $this->assertSame(0.0, \App\Models\Admin\Fee\FeeConcession::takeOff(collect([
            new \App\Models\Admin\Fee\FeeConcession(['fee_type' => 'dues', 'concession_type' => 'amount', 'value' => 900]),
        ]), 'transport', 1500, 500)['taken']);
    }

    public function test_payments_read_with_father_section_collector_kind_and_two_header_figures(): void
    {
        $s   = $this->school();
        $sub = User::forceCreate(['name' => 'Seema', 'role' => 'sub-admin', 'organization_id' => $this->org]);
        $pay = fn ($student, $amount, $by, $mode = 'cash') => DB::table('fee_payments')->insert([
            'organization_id' => $this->org, 'student_detail_id' => $student, 'standard_id' => $s['std'], 'section_id' => $s['sec'],
            'fee_type' => 'academic', 'amount' => $amount, 'payment_mode' => $mode, 'payment_date' => now()->toDateString(),
            'submitted_by' => $by, 'receipt_number' => 'RCT-' . $amount, 'created_at' => now(),
        ]);
        $pay($s['ravi'], 400, 'Head');                 // the admin, by name
        $pay($s['sita'], 300, 'seema');                // a sub-admin, the name typed in lower case
        $pay($s['sita'], 200, (string) $sub->id);      // a sub-admin, by user id
        $pay($s['ravi'], 100, null, 'online');         // paid in the app
        $pay($s['ravi'], 50, 'Somebody Else');         // a name nobody on the staff has

        $page = Livewire::test(Fee::class)->call('showTab', 'payments');
        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        // The header: only the whole fee and what is collected; no strip under the filters.
        $this->assertStringContainsString('Total Fee: ₹2,500 Collected: ₹1,050', $text);
        $this->assertStringNotContainsString('Academic Collected', $text);
        $this->assertStringNotContainsString('Transport Remaining', $text);

        // The listing: no Penalty column; the father under the name, the section under the class.
        $this->assertStringContainsString('# Student Adm No Class Date Type Mode Collected By Amount Slip', $text);
        $this->assertStringContainsString('Ravi Mr Rao — Class 2 A', $text);
        // Collected By says who by kind, never by name.
        foreach (['Admin', 'Sub Admin', 'Online'] as $kind) {
            $this->assertMatchesRegularExpression('/>\s*' . $kind . '\s*<\/td>/', $html, $kind);
        }
        $this->assertSame(2, preg_match_all('/>\s*Sub Admin\s*<\/td>/', $html));
        $this->assertStringNotContainsString('Somebody Else', $text);
        $this->assertStringNotContainsString('>Head<', $html);
        // The slip in green; fifty to a page.
        $this->assertStringContainsString('border-emerald-200 text-emerald-600', $html);
        $this->assertSame(50, $page->get('paymentsPerPage'));
    }

    public function test_last_year_dues_have_their_own_payments_tab_before_penalty(): void
    {
        (require base_path('database/migrations/2026_10_14_150000_add_applies_to_to_fee_payments.php'))->up();
        app()->forgetInstance('fee_payments.applies_to');

        $s   = $this->school();
        $pay = fn ($amount, $appliesTo = null, $type = 'academic') => DB::table('fee_payments')->insert([
            'organization_id' => $this->org, 'student_detail_id' => $s['ravi'], 'standard_id' => $s['std'], 'section_id' => $s['sec'],
            'fee_type' => $type, 'applies_to' => $appliesTo, 'amount' => $amount, 'payment_mode' => 'cash',
            'payment_date' => now()->toDateString(), 'submitted_by' => 'Head', 'receipt_number' => 'RCT-' . $amount, 'created_at' => now(),
        ]);
        $pay(400);                 // academic
        $pay(300, 'dues');         // Last Year Dues
        $pay(25, null, 'penalty'); // late fee

        $page = Livewire::test(Fee::class)->call('showTab', 'payments');
        $text = fn () => preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('All Academic Transport Last Year Dues Penalty', $text());

        $page->call('setFeeTypeFilter', 'dues');
        $this->assertStringContainsString('Dues cash Admin ₹300.00', $text());
        $this->assertStringNotContainsString('₹400.00', $text());

        // Academic no longer lists the dues payment; All lists all three.
        $page->call('setFeeTypeFilter', 'academic');
        $this->assertStringContainsString('₹400.00', $text());
        $this->assertStringNotContainsString('₹300.00', $text());
        $page->call('setFeeTypeFilter', '');
        $this->assertSame(3, $page->viewData('payments')->total());
    }

    public function test_a_class_fee_structure_takes_half_the_screen_a_section(): void
    {
        $s    = $this->school();
        $secB = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $s['std'], 'name' => 'B']);
        DB::table('fee_structures')->insert([
            'organization_id' => $this->org, 'standard_id' => $s['std'], 'section_id' => $secB, 'fee_name' => 'Lab', 'fee_type' => 'academic', 'amount' => 200,
        ]);

        $html = Livewire::test(\App\Livewire\Admin\FeeStructure::class)->set('filterStructureStandard', (string) $s['std'])->html();

        // Two to a row on a laptop: the class-wide card and section B's sit side by side.
        $this->assertStringContainsString('grid grid-cols-1 lg:grid-cols-2 gap-4 items-start', $html);
        $this->assertSame(2, preg_match_all('/wire:key="grp-\d+-\d+"/', $html));
    }
}
