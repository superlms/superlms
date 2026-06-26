<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-organization gateway credentials so each school's online fee collection
 * settles into ITS OWN PhonePe merchant account (and, later, its own payouts).
 * Secrets are encrypted at the model layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->unique();
            $table->string('gateway')->default('phonepe');

            // ── Collection (Standard Checkout) ──
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();      // encrypted
            $table->string('client_version')->default('1');
            $table->string('env')->default('sandbox');      // sandbox | production
            $table->string('webhook_username')->nullable();
            $table->text('webhook_password')->nullable();    // encrypted
            $table->boolean('is_active')->default(false);    // online collection enabled

            // ── Payout (groundwork — disbursing from the org's own account) ──
            $table->string('payout_client_id')->nullable();
            $table->text('payout_client_secret')->nullable(); // encrypted
            $table->string('payout_account_ref')->nullable(); // org's source/merchant ref at the gateway
            $table->boolean('payout_is_active')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_payment_settings');
    }
};
