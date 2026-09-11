<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A token fee is a one-time amount (e.g. an admission/registration charge)
 * collected up front with its own due date, distinct from the % based
 * installments a fee cycle otherwise defines. Marked by is_token = true; its
 * `amount` column then holds the real rupee value directly instead of the
 * usual 0 placeholder, and `payment_serial` stays 0 (it isn't installment #N).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_cycles') && !Schema::hasColumn('fee_cycles', 'is_token')) {
            Schema::table('fee_cycles', function (Blueprint $table) {
                $table->boolean('is_token')->default(false)->after('fee_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_cycles') && Schema::hasColumn('fee_cycles', 'is_token')) {
            Schema::table('fee_cycles', function (Blueprint $table) {
                $table->dropColumn('is_token');
            });
        }
    }
};
