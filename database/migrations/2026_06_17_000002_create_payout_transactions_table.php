<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbound payments an organization makes (salary, vendor, refund) from its own
 * account. Groundwork only — rows are recorded and tracked; actual money-out is
 * gated behind the org enabling PhonePe Payouts (KYC + onboarding).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('gateway')->default('phonepe');

            $table->string('beneficiary_name');
            $table->string('beneficiary_account')->nullable();
            $table->string('beneficiary_ifsc')->nullable();
            $table->string('beneficiary_upi')->nullable();

            $table->string('purpose')->default('other'); // salary | vendor | refund | other
            $table->string('reference_type')->nullable(); // e.g. salary_payment
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('merchant_payout_id')->unique();
            $table->string('gateway_payout_id')->nullable()->index();
            $table->enum('state', ['PENDING', 'PROCESSING', 'SUCCESS', 'FAILED'])->default('PENDING')->index();

            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_transactions');
    }
};
