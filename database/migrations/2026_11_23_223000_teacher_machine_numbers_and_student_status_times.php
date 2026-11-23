<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fk_teacher_numbers: the number each teacher was last sent to this machine
     * under, so a teacher follows when their Employee ID changes (App\Support\BiometricFk).
     *
     * student_status_times: the times a student's first punch is Present,
     * Absent or Half Day (Attendance → Timing).
     */
    public function up(): void
    {
        if (Schema::hasTable('biometric_devices') && !Schema::hasColumn('biometric_devices', 'fk_teacher_numbers')) {
            Schema::table('biometric_devices', function (Blueprint $table) {
                $table->text('fk_teacher_numbers')->nullable()->after('fk_numbers_at');
            });
        }

        if (Schema::hasTable('biometric_settings') && !Schema::hasColumn('biometric_settings', 'student_status_times')) {
            Schema::table('biometric_settings', function (Blueprint $table) {
                $table->text('student_status_times')->nullable()->after('teacher_status_times');
            });
        }
    }

    public function down(): void
    {
        foreach (['biometric_devices' => 'fk_teacher_numbers', 'biometric_settings' => 'student_status_times'] as $t => $column) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, $column)) {
                Schema::table($t, fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
