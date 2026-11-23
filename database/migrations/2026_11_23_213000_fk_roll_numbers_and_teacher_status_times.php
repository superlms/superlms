<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SecurEye-style machines know a student by their roll number
     * (App\Support\BiometricFk). A machine already holding the old numbers
     * stays on them ("id") until it is moved over, so a punch is never read
     * under the wrong numbering: "moving" while that runs, then "roll".
     *
     * teacher_status_times: the times a teacher's first punch is Present,
     * Absent or Half Day (Attendance → Teacher Timing).
     */
    public function up(): void
    {
        if (Schema::hasTable('biometric_devices')) {
            $new = !Schema::hasColumn('biometric_devices', 'fk_numbers');

            Schema::table('biometric_devices', function (Blueprint $table) {
                if (!Schema::hasColumn('biometric_devices', 'fk_numbers')) {
                    $table->string('fk_numbers', 8)->default('roll')->after('fk_released');
                }
                if (!Schema::hasColumn('biometric_devices', 'fk_numbers_at')) {
                    $table->timestamp('fk_numbers_at')->nullable()->after('fk_numbers');
                }
            });

            if ($new) {
                DB::table('biometric_devices')->where('protocol', 'fk')->update(['fk_numbers' => 'id']);
            }
        }

        if (Schema::hasTable('biometric_settings') && !Schema::hasColumn('biometric_settings', 'teacher_status_times')) {
            Schema::table('biometric_settings', function (Blueprint $table) {
                $table->text('teacher_status_times')->nullable()->after('teacher_out_time');
            });
        }
    }

    public function down(): void
    {
        foreach (['biometric_devices' => ['fk_numbers', 'fk_numbers_at'], 'biometric_settings' => ['teacher_status_times']] as $t => $cols) {
            if (Schema::hasTable($t)) {
                $have = array_values(array_filter($cols, fn ($c) => Schema::hasColumn($t, $c)));
                if ($have) {
                    Schema::table($t, fn (Blueprint $table) => $table->dropColumn($have));
                }
            }
        }
    }
};
