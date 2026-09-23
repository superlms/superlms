<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminLedgerController;
use App\Models\Admin\LedgerTransaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Ledger reads the panel's LedgerService: fee payments and
 * salaries beside manual credits and expenses, a running balance newest
 * first, and manual entries editable for 7 days only.
 */
class AdminLedgerApiTest extends TestCase
{
    private int $org = 7;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->string('full_name')->nullable(); $t->string('admission_no')->nullable(); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->decimal('amount', 10, 2); $t->decimal('penalty_amount', 10, 2)->nullable(); $t->date('payment_date');
            $t->string('fee_type')->nullable(); $t->string('payment_mode')->nullable(); $t->string('receipt_number')->nullable();
            $t->string('submitted_by')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('admin_salary_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('admin_employee_id')->nullable();
            $t->decimal('amount', 10, 2); $t->string('status'); $t->date('payment_date')->nullable();
            $t->string('payment_mode')->nullable(); $t->string('month')->nullable(); $t->integer('year')->nullable(); $t->timestamps();
        });
        Schema::create('ledger_transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('type'); $t->decimal('amount', 10, 2);
            $t->date('txn_date'); $t->string('party')->nullable(); $t->string('party_to')->nullable(); $t->string('mode')->nullable();
            $t->text('reason')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Delhi Public School']);
        $this->admin = User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]);
        Auth::setUser($this->admin);
    }

    private function ledger(array $q): array
    {
        return app(AdminLedgerController::class)->index(new Request($q))->getData(true)['data'];
    }

    public function test_the_statement_carries_every_source_with_a_running_balance(): void
    {
        $sid = DB::table('student_details')->insertGetId(['full_name' => 'Aarav', 'admission_no' => 'A1']);
        DB::table('fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => $sid, 'amount' => 5000,
            'payment_date' => '2026-08-05', 'fee_type' => 'academic', 'payment_mode' => 'cash']);
        $emp = DB::table('admin_employees')->insertGetId(['name' => 'Meera']);
        DB::table('admin_salary_payments')->insert(['organization_id' => $this->org, 'admin_employee_id' => $emp,
            'amount' => 2000, 'status' => 'paid', 'payment_date' => '2026-08-10']);
        // Before the window: it only moves the opening balance.
        DB::table('fee_payments')->insert(['organization_id' => $this->org, 'amount' => 1000,
            'payment_date' => '2026-07-20', 'fee_type' => 'academic']);

        $store = app(AdminLedgerController::class)->store(new Request([
            'type' => 'expense', 'date' => '2026-08-12', 'amount' => 300, 'party_to' => 'Stationers', 'mode' => 'UPI', 'reason' => 'Chalk',
        ]));
        $this->assertSame(200, $store->status());

        $d = $this->ledger(['month' => '2026-08']);

        $this->assertSame(1000.0, (float) $d['summary']['opening']);
        $this->assertSame(5000.0, (float) $d['summary']['period_credit']);
        $this->assertSame(2300.0, (float) $d['summary']['period_expense']);
        $this->assertSame(3700.0, (float) $d['summary']['closing']);
        $this->assertSame(3700.0, (float) $d['summary']['net_balance']);

        // Newest first, each row with the balance after it.
        $this->assertSame(['Manual', 'Salary', 'Academic Fee'], array_column($d['entries'], 'source'));
        $this->assertSame([3700.0, 4000.0, 6000.0], array_map('floatval', array_column($d['entries'], 'balance')));
        $this->assertSame('Stationers', $d['entries'][0]['to']);
        $this->assertTrue($d['entries'][0]['editable']);
    }

    public function test_a_manual_entry_can_be_corrected_for_seven_days_only(): void
    {
        $txn = LedgerTransaction::create([
            'organization_id' => $this->org, 'type' => 'credit', 'amount' => 100, 'txn_date' => '2026-08-01', 'reason' => 'Donation',
        ]);

        $ok = app(AdminLedgerController::class)->update(new Request([
            'type' => 'credit', 'date' => '2026-08-01', 'amount' => 150, 'collected_by' => 'Office', 'reason' => 'Donation',
        ]), $txn->id);
        $this->assertSame(200, $ok->status());
        $this->assertSame(150.0, (float) $txn->fresh()->amount);
        $this->assertSame('Office', $txn->fresh()->party_to);

        $txn->forceFill(['created_at' => now()->subDays(8)])->save();
        $late = app(AdminLedgerController::class)->update(new Request([
            'type' => 'credit', 'date' => '2026-08-01', 'amount' => 999, 'reason' => 'Donation',
        ]), $txn->id);
        $this->assertSame(422, $late->status());
        $this->assertSame(150.0, (float) $txn->fresh()->amount);
    }

    public function test_a_remark_is_required_and_another_schools_entry_is_not_found(): void
    {
        $bad = app(AdminLedgerController::class)->store(new Request(['type' => 'credit', 'date' => '2026-08-01', 'amount' => 10]));
        $this->assertNotSame(200, $bad->status());

        $other = LedgerTransaction::create([
            'organization_id' => 99, 'type' => 'credit', 'amount' => 1, 'txn_date' => '2026-08-01', 'reason' => 'x',
        ]);
        $this->assertSame(404, app(AdminLedgerController::class)->show($other->id)->status());
    }
}
