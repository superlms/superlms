<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll no longer marks anyone on Leave: the Mark Attendance panel offers
 * Present, Absent, Half and Holiday. The leave already marked for staff
 * (admin_attendances) is turned into absent, as asked — so it reads, and is
 * counted for salary, as an absent day from now on.
 *
 * Teachers never had a Leave (their days are in teacher_attendances). The
 * column keeps 'leave' as a value, because the app can still send it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_attendances') || !Schema::hasColumn('admin_attendances', 'status')) {
            return;
        }

        DB::table('admin_attendances')->where('status', 'leave')->update(['status' => 'absent']);
    }

    public function down(): void
    {
        // Cannot be told apart from the days that were absent all along.
    }
};
