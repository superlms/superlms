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
        Schema::create('super_admin_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_employee_id')
                ->constrained('super_admin_employees')
                ->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'half_day', 'leave'])->default('present');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['super_admin_employee_id', 'date'], 'unique_emp_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_attendances');
    }
};
