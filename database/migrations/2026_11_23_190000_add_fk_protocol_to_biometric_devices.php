<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SecurEye-style machines speak the "FK" push protocol instead of ADMS
     * (App\Http\Controllers\BiometricFkController). A machine is marked when
     * it first calls that way; until someone has checked the one test user
     * on it (fk_released), nobody else is sent. What a machine answered to a
     * command is a word there ("OK", "BUSY", "ERROR…"), kept in return_text.
     */
    public function up(): void
    {
        if (Schema::hasTable('biometric_devices')) {
            Schema::table('biometric_devices', function (Blueprint $table) {
                if (!Schema::hasColumn('biometric_devices', 'protocol')) {
                    $table->string('protocol', 8)->nullable()->after('name');     // null = ADMS, fk = FK push
                }
                if (!Schema::hasColumn('biometric_devices', 'fk_released')) {
                    $table->boolean('fk_released')->default(false)->after('protocol');
                }
            });
        }

        if (Schema::hasTable('biometric_commands') && !Schema::hasColumn('biometric_commands', 'return_text')) {
            Schema::table('biometric_commands', function (Blueprint $table) {
                $table->string('return_text', 64)->nullable()->after('return_code');
            });
        }
    }

    public function down(): void
    {
        foreach (['biometric_devices' => ['protocol', 'fk_released'], 'biometric_commands' => ['return_text']] as $t => $cols) {
            if (Schema::hasTable($t)) {
                $have = array_values(array_filter($cols, fn ($c) => Schema::hasColumn($t, $c)));
                if ($have) {
                    Schema::table($t, fn (Blueprint $table) => $table->dropColumn($have));
                }
            }
        }
    }
};
