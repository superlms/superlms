<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a receipt's money went: [[payment id, amount put on it], …], so a
     * payment edited from the school's Payments list (Fees) takes back or adds
     * to exactly those students / months. NULL on receipts written before.
     */
    public function up(): void
    {
        if (Schema::hasTable('super_admin_fee_receipts') && !Schema::hasColumn('super_admin_fee_receipts', 'allocation')) {
            Schema::table('super_admin_fee_receipts', function (Blueprint $table) {
                $table->json('allocation')->nullable()->after('items');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('super_admin_fee_receipts', 'allocation')) {
            Schema::table('super_admin_fee_receipts', function (Blueprint $table) {
                $table->dropColumn('allocation');
            });
        }
    }
};
