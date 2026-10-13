<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Super Admin's Payroll marks attendance as the school Payroll does: one
 * panel, everyone at once, and Present / Absent / Half / Holiday on every row
 * (All holiday with a remark for everyone). super_admin_attendances.status only
 * took present, absent, half_day and leave; this adds holiday, as
 * 2026_10_02_000000 did for admin_attendances. A holiday cuts no pay and is not
 * a working day. Existing rows (leave included) are left as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasColumn('super_admin_attendances', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE super_admin_attendances MODIFY status ENUM('present','absent','half_day','leave','holiday') NOT NULL DEFAULT 'present'");
    }

    public function down(): void
    {
        // Left as it is: taking the value away would break the rows that carry it.
    }
};
