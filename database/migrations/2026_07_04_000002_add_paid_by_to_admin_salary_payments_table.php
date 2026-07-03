<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_salary_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_salary_payments', 'paid_by')) {
                $table->string('paid_by')->nullable()->after('payment_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_salary_payments', function (Blueprint $table) {
            if (Schema::hasColumn('admin_salary_payments', 'paid_by')) {
                $table->dropColumn('paid_by');
            }
        });
    }
};
