<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual ledger entries now carry a payment mode (Cash / UPI / Cheque …) and a
 * second party. `party` is the "From / By" side and `party_to` is the "To" side
 * (used by manual expenses, e.g. money paid out to a vendor). Both are guarded
 * with hasColumn so this is a no-op if an earlier deploy already added them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('ledger_transactions', 'mode')) {
                $table->string('mode')->nullable()->after('party');
            }
            if (!Schema::hasColumn('ledger_transactions', 'party_to')) {
                $table->string('party_to')->nullable()->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            foreach (['mode', 'party_to'] as $col) {
                if (Schema::hasColumn('ledger_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
