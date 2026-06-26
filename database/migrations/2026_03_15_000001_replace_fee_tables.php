<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all old fee tables
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('fee_assignments');
        Schema::dropIfExists('fee_template_items');
        Schema::dropIfExists('fee_templates');
        Schema::dropIfExists('fee_concessions');
        Schema::dropIfExists('fee_cycles');
        Schema::dropIfExists('fee_heads');
        Schema::dropIfExists('student_fees');

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->default(0)->index();
            $table->unsignedBigInteger('standard_id')->default(0)->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->string('fee_name');
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('fee_type', ['academic', 'transport'])->default('academic');
            $table->string('academic_year', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
