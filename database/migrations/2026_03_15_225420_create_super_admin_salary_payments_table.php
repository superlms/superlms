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
        Schema::create('super_admin_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_employee_id')
                ->constrained('super_admin_employees')
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('month');        
            $table->enum('payment_mode', ['cash', 'online', 'bank_transfer', 'cheque'])
                ->default('cash');
            $table->enum('status', ['paid', 'pending'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('remark')->nullable();
            $table->string('receipt_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_salary_payments');
    }
};
