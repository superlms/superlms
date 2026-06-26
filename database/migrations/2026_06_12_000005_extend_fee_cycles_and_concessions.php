<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Cycle installments now carry an explicit start/end date and a computed
 * amount (base × percent). Fee concessions gain a 'penalty' scope so penalties
 * can be waived per student — so fee_type becomes a plain string.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_cycles')) {
            Schema::table('fee_cycles', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_cycles', 'start_date')) {
                    $table->date('start_date')->nullable()->after('payment_serial');
                }
                if (!Schema::hasColumn('fee_cycles', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('fee_cycles', 'amount')) {
                    $table->decimal('amount', 12, 2)->default(0)->after('fee_percent');
                }
            });
        }

        // Widen fee_concessions.fee_type from enum to string so 'penalty' is allowed.
        if (Schema::hasTable('fee_concessions') && Schema::hasColumn('fee_concessions', 'fee_type')) {
            try {
                DB::statement("ALTER TABLE fee_concessions MODIFY fee_type VARCHAR(20) NOT NULL DEFAULT 'all'");
            } catch (\Throwable $e) {
                // Best-effort; ignore on drivers that don't support MODIFY.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_cycles')) {
            Schema::table('fee_cycles', function (Blueprint $table) {
                foreach (['start_date', 'end_date', 'amount'] as $col) {
                    if (Schema::hasColumn('fee_cycles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
