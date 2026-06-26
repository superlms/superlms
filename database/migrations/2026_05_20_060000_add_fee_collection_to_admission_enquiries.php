<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_enquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_enquiries', 'collected_amount')) {
                $table->decimal('collected_amount', 10, 2)->nullable()->after('admission_fee');
            }
            if (!Schema::hasColumn('admission_enquiries', 'payment_mode')) {
                $table->string('payment_mode')->nullable()->after('collected_amount');
            }
            if (!Schema::hasColumn('admission_enquiries', 'collected_by')) {
                $table->string('collected_by')->nullable()->after('payment_mode');
            }
            if (!Schema::hasColumn('admission_enquiries', 'fee_collected_at')) {
                $table->date('fee_collected_at')->nullable()->after('collected_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_enquiries', function (Blueprint $table) {
            foreach (['collected_amount', 'payment_mode', 'collected_by', 'fee_collected_at'] as $col) {
                if (Schema::hasColumn('admission_enquiries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
