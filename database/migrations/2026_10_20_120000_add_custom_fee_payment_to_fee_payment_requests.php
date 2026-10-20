<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A QR payment approved in a school on Custom Fee (App\Support\CustomFee)
     * is booked into custom_fee_payments, not fee_payments; the request keeps
     * which payment it made, as it keeps fee_payment_id for the ordinary fee.
     */
    public function up(): void
    {
        if (Schema::hasTable('fee_payment_requests') && !Schema::hasColumn('fee_payment_requests', 'custom_fee_payment_id')) {
            Schema::table('fee_payment_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('custom_fee_payment_id')->nullable()->after('transport_fee_payment_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_payment_requests', 'custom_fee_payment_id')) {
            Schema::table('fee_payment_requests', function (Blueprint $table) {
                $table->dropColumn('custom_fee_payment_id');
            });
        }
    }
};
