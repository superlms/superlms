<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A payment a student made on the school's QR and reported from the app, with
 * its UTR and/or a screenshot. It waits here until the school approves it —
 * which books it as an ordinary fee payment (fee_payments, or
 * transport_fee_payments for the bus) — or rejects it with a reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('student_detail_id');
            $table->unsignedBigInteger('user_id');              // the login that sent it
            $table->string('fee_type', 20);                     // academic | transport
            $table->decimal('amount', 10, 2);                   // what the student says they paid
            $table->string('utr', 40)->nullable();              // UPI reference / transaction id
            $table->string('screenshot_path')->nullable();      // S3 key, private
            $table->date('paid_on');
            $table->string('note', 500)->nullable();
            $table->json('meta')->nullable();                   // transport months + route, installment
            $table->string('status', 20)->default('pending');   // pending | approved | rejected
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->unsignedBigInteger('fee_payment_id')->nullable();
            $table->unsignedBigInteger('transport_fee_payment_id')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'utr']);
            $table->index(['student_detail_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payment_requests');
    }
};
