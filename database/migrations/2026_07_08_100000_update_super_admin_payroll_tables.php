<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The superadmin employee types (user/counsellor/team/management/other)
        // diverged from the original enum — store as a plain string instead.
        DB::statement("ALTER TABLE super_admin_employees MODIFY COLUMN type VARCHAR(30) NOT NULL DEFAULT 'user'");

        if (!Schema::hasColumn('super_admin_salary_payments', 'paid_by')) {
            Schema::table('super_admin_salary_payments', function (Blueprint $table) {
                $table->string('paid_by')->nullable()->after('payment_mode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('super_admin_salary_payments', 'paid_by')) {
            Schema::table('super_admin_salary_payments', function (Blueprint $table) {
                $table->dropColumn('paid_by');
            });
        }
    }
};
