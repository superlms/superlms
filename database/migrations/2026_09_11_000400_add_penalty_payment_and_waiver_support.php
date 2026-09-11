<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Submission gains a third fee type — 'penalty' — so an installment's
 * accrued late fee can be paid off on its own, without touching the academic
 * or transport fee structure. Fee Concessions gain the matching waiver side:
 * `is_penalty` marks a concession as writing off accrued penalty (kept out of
 * the normal academic/transport discount math in concessionOn()), and
 * `collected_by` records who processed the waiver, same as fee_payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_payments') && Schema::hasColumn('fee_payments', 'fee_type')) {
            try {
                DB::statement("ALTER TABLE fee_payments MODIFY fee_type VARCHAR(20) NOT NULL DEFAULT 'academic'");
            } catch (\Throwable $e) {
                // Best-effort; ignore on drivers that don't support MODIFY.
            }
        }

        if (Schema::hasTable('fee_concessions')) {
            Schema::table('fee_concessions', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_concessions', 'is_penalty')) {
                    $table->boolean('is_penalty')->default(false)->after('fee_type');
                }
                if (!Schema::hasColumn('fee_concessions', 'collected_by')) {
                    $table->string('collected_by')->nullable()->after('created_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_concessions')) {
            Schema::table('fee_concessions', function (Blueprint $table) {
                foreach (['is_penalty', 'collected_by'] as $col) {
                    if (Schema::hasColumn('fee_concessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
