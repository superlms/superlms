<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('fee_type')->default('academic');
            $table->integer('payment_serial');
            $table->date('due_date');
            $table->decimal('penalty_per_day', 10, 2)->default(0);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->string('academic_year', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_cycles');
    }
};
