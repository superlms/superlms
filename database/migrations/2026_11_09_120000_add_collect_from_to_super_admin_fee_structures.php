<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who pays a Per Student Super Admin fee (FeeAccess):
 *   school  — the school pays and the super admin marks it paid (as before);
 *   student — each student pays it in the app (PhonePe); until then the app
 *             shows only the payment screen, and the school can neither take
 *             the student's fee nor mark their attendance.
 *
 * An online payment of it is a payment_transactions row of fee_type
 * "platform", so that column takes any short word now, not just the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('super_admin_fee_structures', 'collect_from')) {
            Schema::table('super_admin_fee_structures', function (Blueprint $table) {
                $table->string('collect_from', 10)->default('school')->after('fee_type');
            });
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('fee_type', 20)->default('academic')->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('super_admin_fee_structures', 'collect_from')) {
            Schema::table('super_admin_fee_structures', function (Blueprint $table) {
                $table->dropColumn('collect_from');
            });
        }
    }
};
