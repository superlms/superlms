<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Custom Fee (App\Support\CustomFee): a school the super admin turns it on
     * for sets each student's fee by hand — particulars and amounts on that
     * student alone — and collects against it, instead of the class-wide fee
     * structure, cycles, concessions and penalties. A parent with several
     * children has all their fees put on one of them.
     *
     * Kept apart from fee_structures / fee_payments, so a school without it
     * reads exactly what it read before.
     */
    public function up(): void
    {
        if (!Schema::hasTable('custom_fee_settings')) {
            Schema::create('custom_fee_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->unique()->constrained('organizations')->cascadeOnDelete();
                $table->boolean('enabled')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('custom_fee_items')) {
            Schema::create('custom_fee_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->unsignedBigInteger('student_detail_id')->index();
                $table->string('fee_name');
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'student_detail_id']);
            });
        }

        if (!Schema::hasTable('custom_fee_payments')) {
            Schema::create('custom_fee_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->unsignedBigInteger('student_detail_id')->index();
                // The class the student was in when they paid.
                $table->unsignedBigInteger('standard_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('payment_mode', 30)->default('cash');
                $table->date('payment_date');
                $table->string('submitted_by')->nullable();   // the collector's name, as typed
                $table->string('remark')->nullable();
                $table->string('receipt_number', 40)->nullable()->unique();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'payment_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fee_payments');
        Schema::dropIfExists('custom_fee_items');
        Schema::dropIfExists('custom_fee_settings');
    }
};
