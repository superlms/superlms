<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One-time fees can now set a different amount per period (e.g. a
        // school might owe less in vacation months) instead of an even split.
        Schema::table('super_admin_fee_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('super_admin_fee_structures', 'period_amounts')) {
                $table->json('period_amounts')->nullable()->after('installment_frequency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('super_admin_fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('super_admin_fee_structures', 'period_amounts')) {
                $table->dropColumn('period_amounts');
            }
        });
    }
};
