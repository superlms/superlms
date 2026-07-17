<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_listings', function (Blueprint $table) {
            if (! Schema::hasColumn('school_listings', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('remark'); // monthly | one_time | student_based
            }
            if (! Schema::hasColumn('school_listings', 'payment_amount')) {
                $table->decimal('payment_amount', 12, 2)->nullable()->after('payment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_listings', function (Blueprint $table) {
            foreach (['payment_type', 'payment_amount'] as $col) {
                if (Schema::hasColumn('school_listings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
