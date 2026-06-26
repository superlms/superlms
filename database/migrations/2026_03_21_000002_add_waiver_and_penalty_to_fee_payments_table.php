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
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->decimal('waiver_amount', 10, 2)->nullable()->default(0)->after('amount');
            $table->string('waiver_reason')->nullable()->after('waiver_amount');
            $table->decimal('penalty_amount', 10, 2)->nullable()->default(0)->after('waiver_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn(['waiver_amount', 'waiver_reason', 'penalty_amount']);
        });
    }
};
