<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A school's own UPI QR, shown to students in the app's Fees screen. The money
 * goes straight to the school's account; the student then reports the payment
 * (fee_payment_requests) and the school checks it before it counts as paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->unique();
            $table->string('qr_path');                      // S3 key of the QR image
            $table->string('upi_id')->nullable();           // e.g. school@okaxis — for "Pay in UPI app"
            $table->string('payee_name')->nullable();
            $table->string('instructions', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_qr_codes');
    }
};
