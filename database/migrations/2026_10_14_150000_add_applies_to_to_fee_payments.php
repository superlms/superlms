<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What an academic payment was taken for: 'dues' when Collect Fee's
     * "Dues" was picked — it goes against the student's Last Year Dues, not
     * this year's fee cycle (App\Support\FeeDues). NULL is an ordinary
     * academic payment. The row stays fee_type 'academic', so every total
     * that adds up academic payments reads it as before.
     */
    public function up(): void
    {
        if (Schema::hasTable('fee_payments') && !Schema::hasColumn('fee_payments', 'applies_to')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                $table->string('applies_to', 20)->nullable()->after('fee_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_payments', 'applies_to')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                $table->dropColumn('applies_to');
            });
        }
    }
};
