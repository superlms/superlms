<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fee Autopay (PhonePe UPI AutoPay): a parent's mandate for a student's
     * academic installments, and each installment debited on it
     * (App\Services\FeeAutopay). A school uses it only when the Super Admin
     * turns Autopay on for its own PhonePe merchant — the money settles into
     * the school's account, never SuperLMS's.
     */
    public function up(): void
    {
        if (Schema::hasTable('organization_payment_settings') && !Schema::hasColumn('organization_payment_settings', 'autopay_enabled')) {
            Schema::table('organization_payment_settings', function (Blueprint $table) {
                $table->boolean('autopay_enabled')->default(false)->after('is_active');
            });
        }

        if (!Schema::hasTable('fee_autopay_mandates')) {
            Schema::create('fee_autopay_mandates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('student_detail_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('merchant_subscription_id', 63)->unique();
                $table->string('setup_order_id', 63)->unique();
                $table->string('phonepe_subscription_id')->nullable();
                $table->decimal('max_amount', 12, 2);
                // pending | active | paused | cancelling | cancelled | revoked | expired | failed
                $table->string('status', 20)->default('pending');
                $table->string('status_reason')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
                $table->index(['student_detail_id', 'status']);
            });
        }

        if (!Schema::hasTable('fee_autopay_debits')) {
            Schema::create('fee_autopay_debits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mandate_id');
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('student_detail_id');
                $table->unsignedBigInteger('fee_cycle_id');
                $table->date('due_date')->nullable();
                $table->string('label')->nullable();             // "2nd installment"
                $table->decimal('amount', 12, 2);
                $table->string('merchant_order_id', 63)->unique();
                $table->unsignedBigInteger('payment_transaction_id')->nullable();
                // notifying | notified | debiting | completed | failed | skipped
                $table->string('status', 20)->default('notifying');
                $table->string('failure_reason')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('valid_after')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                // One debit per installment on a mandate, however often the job runs.
                $table->unique(['mandate_id', 'fee_cycle_id']);
                $table->index(['organization_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_autopay_debits');
        Schema::dropIfExists('fee_autopay_mandates');

        if (Schema::hasTable('organization_payment_settings') && Schema::hasColumn('organization_payment_settings', 'autopay_enabled')) {
            Schema::table('organization_payment_settings', function (Blueprint $table) {
                $table->dropColumn('autopay_enabled');
            });
        }
    }
};
