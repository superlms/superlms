<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A penalty waiver now targets one specific fee cycle installment (not just
 * "academic" or "transport" as a whole) and records how it was authorised —
 * cash / online / cheque / bank transfer, same vocabulary as a real payment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_concessions')) {
            Schema::table('fee_concessions', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_concessions', 'fee_cycle_id')) {
                    $table->unsignedBigInteger('fee_cycle_id')->nullable()->after('fee_type')->index();
                }
                if (!Schema::hasColumn('fee_concessions', 'payment_mode')) {
                    $table->string('payment_mode')->nullable()->after('collected_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_concessions')) {
            Schema::table('fee_concessions', function (Blueprint $table) {
                foreach (['fee_cycle_id', 'payment_mode'] as $col) {
                    if (Schema::hasColumn('fee_concessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
