<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transport_fee_payments')) {
            Schema::create('transport_fee_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('transportation_id')->nullable();
                $table->unsignedBigInteger('student_detail_id');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('payment_mode')->default('cash'); // cash / online / cheque / upi
                $table->date('payment_date');
                $table->string('receipt_number')->unique();
                $table->string('academic_year')->nullable();
                $table->text('remark')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'student_detail_id']);
                $table->index(['organization_id', 'transportation_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_fee_payments');
    }
};
