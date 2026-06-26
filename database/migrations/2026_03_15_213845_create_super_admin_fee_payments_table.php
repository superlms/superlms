<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('super_admin_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('standard_id')->constrained('standards')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('student_detail_id')->constrained('student_details')->cascadeOnDelete();
            $table->foreignId('super_admin_fee_structure_id')->constrained('super_admin_fee_structures')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('academic_year', 10);
            $table->string('payment_mode')->nullable(); 
            $table->date('payment_date')->nullable();
            $table->string('receipt_number')->nullable();
            $table->text('remark')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->unique(['student_detail_id', 'super_admin_fee_structure_id'], 'unique_student_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_fee_payments');
    }
};
