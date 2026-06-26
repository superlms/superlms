<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->default(0)->index();
            $table->unsignedBigInteger('student_detail_id')->default(0)->index();
            $table->unsignedBigInteger('standard_id')->default(0)->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->enum('fee_type', ['academic', 'transport'])->default('academic');
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('payment_mode', ['cash', 'online', 'cheque', 'bank_transfer'])->default('cash');
            $table->date('payment_date');
            $table->text('remark')->nullable();
            $table->string('submitted_by');
            $table->string('receipt_number')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
