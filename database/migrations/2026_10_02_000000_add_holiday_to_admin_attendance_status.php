<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll's Mark Attendance can give everyone a holiday at once. A teacher's
 * holiday already has a place (teacher_attendances, code 3); the rest of the
 * staff are marked in admin_attendances, whose status only took present,
 * absent, half_day and leave. This adds holiday to it. A holiday is not a
 * working day: it cuts no pay and is left out of "present / working days".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasColumn('admin_attendances', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE admin_attendances MODIFY status ENUM('present','absent','half_day','leave','holiday') NOT NULL DEFAULT 'present'");
    }

    public function down(): void
    {
        // Left as it is: taking the value away would break the rows that carry it.
    }
};
