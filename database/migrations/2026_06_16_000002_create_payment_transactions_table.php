<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('student_detail_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('fee_type', ['academic', 'transport'])->default('academic');
            $table->string('gateway')->default('phonepe');

            // Our id sent to PhonePe; their order id returned by the API.
            $table->string('merchant_order_id')->unique();
            $table->string('phonepe_order_id')->nullable()->index();

            $table->decimal('amount', 10, 2)->default(0); // in rupees
            $table->enum('state', ['PENDING', 'COMPLETED', 'FAILED'])->default('PENDING')->index();

            // Set once we record a successful payment into fee_payments (idempotency guard).
            $table->unsignedBigInteger('fee_payment_id')->nullable()->index();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
