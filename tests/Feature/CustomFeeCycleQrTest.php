<?php

namespace Tests\Feature;

use App\Models\Admin\Fee\CustomFeePayment;
use App\Models\Admin\Fee\FeePaymentRequest;
use App\Models\User;
use App\Support\CustomFee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Custom Fee's Fee Cycle and QR Payments, as the ordinary fee has them: a
 * student's own fee splits on the school's cycle — the Registration Fee off
 * the top, each installment its % of the rest, what they paid against the
 * earliest first — and a QR payment approved in a school on Custom Fee is
 * booked into custom_fee_payments with its CFR receipt, never fee_payments.
 */
class CustomFeeCycleQrTest extends TestCase
{
    private int $org = 7;
    private int $student;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('full_name')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('custom_fee_settings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->boolean('enabled')->default(false);
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('custom_fee_items', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->string('fee_name'); $t->decimal('amount', 12, 2); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('custom_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->decimal('amount', 12, 2); $t->string('payment_mode')->default('cash'); $t->date('payment_date');
            $t->string('submitted_by')->nullable(); $t->string('remark')->nullable(); $t->string('receipt_number')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 12, 2); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type')->default('academic');
            $t->integer('payment_serial'); $t->date('start_date')->nullable(); $t->date('end_date')->nullable();
            $t->date('due_date')->nullable(); $t->decimal('penalty_per_day', 10, 2)->default(0);
            $t->decimal('fee_percent', 5, 2)->default(0); $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_token')->default(false); $t->string('academic_year', 20); $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('fee_payment_requests', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('user_id'); $t->string('fee_type', 20); $t->decimal('amount', 10, 2);
            $t->string('utr', 40)->nullable(); $t->string('screenshot_path')->nullable(); $t->date('paid_on');
            $t->string('note', 500)->nullable(); $t->json('meta')->nullable(); $t->string('status', 20)->default('pending');
            $t->decimal('approved_amount', 10, 2)->nullable(); $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable(); $t->string('review_note', 500)->nullable();
            $t->unsignedBigInteger('fee_payment_id')->nullable(); $t->unsignedBigInteger('transport_fee_payment_id')->nullable();
            $t->unsignedBigInteger('custom_fee_payment_id')->nullable(); $t->timestamps();
        });

        $this->admin   = User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]);
        $this->student = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'Zoya Khan', 'standard_id' => 5, 'section_id' => 1]);

        foreach ([
            ['payment_serial' => 0, 'is_token' => true, 'amount' => 1000, 'due_date' => '2026-04-10'],
            ['payment_serial' => 1, 'fee_percent' => 40, 'due_date' => '2026-05-10', 'start_date' => '2026-04-01', 'end_date' => '2026-09-30'],
            ['payment_serial' => 2, 'fee_percent' => 60, 'due_date' => '2099-01-10', 'start_date' => '2026-10-01', 'end_date' => '2027-03-31'],
        ] as $row) {
            DB::table('fee_cycles')->insert($row + ['organization_id' => $this->org, 'academic_year' => '2026-27', 'fee_type' => 'academic']);
        }
        // An older year's cycle is not read.
        DB::table('fee_cycles')->insert(['organization_id' => $this->org, 'academic_year' => '2025-26', 'payment_serial' => 1, 'fee_percent' => 100]);
    }

    public function test_a_students_own_fee_splits_on_the_cycle_earliest_first(): void
    {
        $rows = CustomFee::installments($this->org, 11000, 4500);

        $this->assertSame(['Registration Fee', 'Installment 1', 'Installment 2'], array_column($rows, 'label'));
        // 1,000 off the top; 40% and 60% of the 10,000 left.
        $this->assertSame([1000.0, 4000.0, 6000.0], array_column($rows, 'amount'));
        // 4,500 paid: the registration, the first installment, 500 of the second.
        $this->assertSame([1000.0, 3500.0, 0.0], array_column($rows, 'paid'));
        $this->assertSame(['Paid', 'Partial', 'Due'], array_column($rows, 'status'));
        $this->assertSame('Apr–Sep 2026', $rows[1]['span']);

        // Nothing paid on an installment whose date has gone: Overdue.
        $this->assertSame('Overdue', CustomFee::installments($this->org, 11000, 1000)[1]['status']);
        // A fee smaller than the Registration Fee is all registration.
        $this->assertSame([500.0, 0.0, 0.0], array_column(CustomFee::installments($this->org, 500, 0), 'amount'));
        // No cycle, no installments.
        $this->assertSame([], CustomFee::installments(99, 11000, 0));
    }

    public function test_a_qr_payment_approved_on_custom_fee_books_a_custom_fee_payment(): void
    {
        CustomFee::setEnabled($this->org, true);

        $req = FeePaymentRequest::create([
            'organization_id' => $this->org, 'student_detail_id' => $this->student, 'user_id' => $this->admin->id,
            'fee_type' => 'academic', 'amount' => 2500, 'utr' => '412345678901', 'paid_on' => '2026-10-19', 'status' => 'pending',
        ]);
        $req->approve($this->admin, 2400.0, 'Matched');

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertNull($req->fee_payment_id);
        $this->assertSame(0, DB::table('fee_payments')->count());

        $payment = CustomFeePayment::findOrFail($req->custom_fee_payment_id);
        $this->assertSame('2400.00', (string) $payment->amount);
        $this->assertSame('upi', $payment->payment_mode);
        $this->assertSame('2026-10-19', $payment->payment_date->toDateString());
        $this->assertSame(5, (int) $payment->standard_id);
        $this->assertStringContainsString('UTR 412345678901', $payment->remark);
        $this->assertStringStartsWith('CFR-' . $this->org . '-', $req->receiptNumber());
        $this->assertSame($payment->receipt_number, $req->receiptNumber());
    }
}
