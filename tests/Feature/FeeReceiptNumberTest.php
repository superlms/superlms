<?php

namespace Tests\Feature;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A fee's receipt number is one past the highest the school's series has used,
 * never a count of the year's receipts: with a receipt deleted, or moved to
 * transport_fee_payments (as on 14 Sep 2026, keeping its RCT- number), the
 * count gave a number still in use and the unique key refused every fee.
 */
class FeeReceiptNumberTest extends TestCase
{
    private int $org = 4;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->string('fee_type')->default('academic'); $t->decimal('amount', 12, 2)->default(0);
            $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable();
            $t->string('receipt_number')->unique(); $t->string('submitted_by')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 12, 2)->default(0); $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable();
            $t->string('receipt_number')->unique(); $t->timestamps();
        });
        // What a fee's own hooks read once it is saved (the student it is for).
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        DB::table('student_details')->insert(['id' => 1, 'organization_id' => $this->org, 'full_name' => 'Ravi']);
    }

    private function rct(int $n, int $org = 4): string
    {
        return "RCT-{$org}-" . date('Y') . '-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    private function feeRow(string $receipt, int $org = 4): array
    {
        return ['organization_id' => $org, 'student_detail_id' => 1, 'amount' => 100, 'receipt_number' => $receipt, 'created_at' => now(), 'updated_at' => now()];
    }

    private function pay(int $org = 4): FeePayment
    {
        return DB::transaction(fn () => FeePayment::create([
            'organization_id' => $org, 'student_detail_id' => 1, 'fee_type' => 'academic',
            'amount' => 500, 'payment_mode' => 'cash', 'payment_date' => today()->toDateString(), 'submitted_by' => 'Head',
        ]));
    }

    public function test_a_school_whose_receipt_moved_to_transport_can_take_fees_again(): void
    {
        // As school 4 stood: 1–4, 6 and 7 here; 5 and 8 moved to transport with their RCT- numbers.
        foreach ([1, 2, 3, 4, 6, 7] as $n) {
            DB::table('fee_payments')->insert($this->feeRow($this->rct($n)));
        }
        foreach ([5, 8] as $n) {
            DB::table('transport_fee_payments')->insert($this->feeRow($this->rct($n)));
        }

        // The count (6) gave RCT-…-00007, already taken: every fee failed.
        $this->assertSame($this->rct(9), $this->pay()->receipt_number);
        $this->assertSame($this->rct(10), $this->pay()->receipt_number);
    }

    public function test_a_deleted_receipt_is_not_handed_out_again_while_a_later_one_stands(): void
    {
        foreach ([1, 2, 3] as $n) {
            DB::table('fee_payments')->insert($this->feeRow($this->rct($n)));
        }
        DB::table('fee_payments')->where('receipt_number', $this->rct(2))->delete();

        $this->assertSame($this->rct(4), $this->pay()->receipt_number);
    }

    public function test_each_school_keeps_its_own_series(): void
    {
        DB::table('fee_payments')->insert($this->feeRow($this->rct(41, 40), 40));   // RCT-40-… is not RCT-4-…

        $this->assertSame($this->rct(1), $this->pay()->receipt_number);
        $this->assertSame($this->rct(42, 40), $this->pay(40)->receipt_number);
    }

    public function test_past_99999_the_number_keeps_counting(): void
    {
        DB::table('fee_payments')->insert($this->feeRow($this->rct(99999)));
        DB::table('fee_payments')->insert($this->feeRow($this->rct(100000)));

        $this->assertSame($this->rct(100001), $this->pay()->receipt_number);
    }

    public function test_transport_receipts_after_a_deleted_one(): void
    {
        $trct = fn (int $n) => "TRCT-4-" . date('Y') . '-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        foreach ([1, 2, 3] as $n) {
            DB::table('transport_fee_payments')->insert($this->feeRow($trct($n)));
        }
        DB::table('transport_fee_payments')->where('receipt_number', $trct(1))->delete();

        $payment = DB::transaction(fn () => TransportFeePayment::create([
            'organization_id' => $this->org, 'student_detail_id' => 1, 'amount' => 300,
            'payment_mode' => 'cash', 'payment_date' => today()->toDateString(),
        ]));

        $this->assertSame($trct(4), $payment->receipt_number);   // the count (2) gave 00003, taken
    }
}
