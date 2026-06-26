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
        Schema::table('admin_salary_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_salary_payments', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'admin_employee_id')) {
                $table->unsignedBigInteger('admin_employee_id')->nullable()->after('organization_id');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'month')) {
                $table->string('month')->nullable()->after('admin_employee_id');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('month');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'payment_mode')) {
                $table->enum('payment_mode', ['cash', 'online', 'bank_transfer', 'cheque'])
                    ->default('cash')
                    ->after('amount');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'status')) {
                $table->enum('status', ['paid', 'pending'])->default('pending')->after('payment_mode');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('status');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_date');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'remark')) {
                $table->text('remark')->nullable()->after('transaction_id');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('remark');
            }

            if (!Schema::hasColumn('admin_salary_payments', 'organization_id')) {
                $table->index('organization_id');
            }
            if (!Schema::hasColumn('admin_salary_payments', 'admin_employee_id')) {
                $table->index('admin_employee_id');
            }
            if (!Schema::hasColumn('admin_salary_payments', 'month')) {
                $table->index('month');
            }
            if (!Schema::hasColumn('admin_salary_payments', 'status')) {
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_salary_payments', function (Blueprint $table) {
            if (Schema::hasColumn('admin_salary_payments', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
            if (Schema::hasColumn('admin_salary_payments', 'remark')) {
                $table->dropColumn('remark');
            }
            if (Schema::hasColumn('admin_salary_payments', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
            if (Schema::hasColumn('admin_salary_payments', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::hasColumn('admin_salary_payments', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('admin_salary_payments', 'payment_mode')) {
                $table->dropColumn('payment_mode');
            }
            if (Schema::hasColumn('admin_salary_payments', 'amount')) {
                $table->dropColumn('amount');
            }
            if (Schema::hasColumn('admin_salary_payments', 'month')) {
                $table->dropColumn('month');
            }
            if (Schema::hasColumn('admin_salary_payments', 'admin_employee_id')) {
                $table->dropColumn('admin_employee_id');
            }
            if (Schema::hasColumn('admin_salary_payments', 'organization_id')) {
                $table->dropColumn('organization_id');
            }
        });
    }
};
