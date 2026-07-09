<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 'per_student' is a new fee type — a flat rate applied to every student
        // in the school, replacing the old per-class ('class_wise') breakdown.
        DB::statement("ALTER TABLE `super_admin_fee_structures` MODIFY `fee_type` ENUM('class_wise','one_time','per_student') NOT NULL DEFAULT 'one_time'");

        // One-time fees are now a whole-school total split into monthly / quarterly /
        // yearly installments — `amount` holds the per-installment figure,
        // `total_amount` the whole total the split was made from.
        Schema::table('super_admin_fee_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('super_admin_fee_structures', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('super_admin_fee_structures', 'installment_frequency')) {
                $table->string('installment_frequency')->nullable()->after('total_amount');
            }
        });

        // One-time installment payments are org-level, not tied to a single student,
        // so student_detail_id must become optional and payments need a period label
        // (e.g. "2026-04" for a monthly installment) to identify which one they cover.
        if (!Schema::hasColumn('super_admin_fee_payments', 'installment_period')) {
            Schema::table('super_admin_fee_payments', function (Blueprint $table) {
                $table->string('installment_period')->nullable()->after('super_admin_fee_structure_id');
            });
        }

        $fkExists = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'super_admin_fee_payments'
               AND CONSTRAINT_NAME = 'super_admin_fee_payments_student_detail_id_foreign'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if (!empty($fkExists)) {
            DB::statement('ALTER TABLE `super_admin_fee_payments` DROP FOREIGN KEY `super_admin_fee_payments_student_detail_id_foreign`');
        }
        DB::statement('ALTER TABLE `super_admin_fee_payments` MODIFY `student_detail_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `super_admin_fee_payments` ADD CONSTRAINT `super_admin_fee_payments_student_detail_id_foreign` FOREIGN KEY (`student_detail_id`) REFERENCES `student_details` (`id`) ON DELETE CASCADE');

        // MySQL treats each NULL as distinct, so per-student rows (unique per
        // student+structure already) are unaffected; this just stops the same
        // org-level installment period being recorded twice for one structure.
        $idxExists = DB::select(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'super_admin_fee_payments'
               AND INDEX_NAME = 'unique_structure_installment_period'"
        );
        if (empty($idxExists)) {
            Schema::table('super_admin_fee_payments', function (Blueprint $table) {
                $table->unique(['super_admin_fee_structure_id', 'installment_period'], 'unique_structure_installment_period');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('super_admin_fee_payments', function (Blueprint $table) {
            $table->dropUnique('unique_structure_installment_period');
            if (Schema::hasColumn('super_admin_fee_payments', 'installment_period')) {
                $table->dropColumn('installment_period');
            }
        });

        DB::statement('ALTER TABLE `super_admin_fee_payments` MODIFY `student_detail_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('super_admin_fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('super_admin_fee_structures', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('super_admin_fee_structures', 'installment_frequency')) {
                $table->dropColumn('installment_frequency');
            }
        });

        DB::statement("ALTER TABLE `super_admin_fee_structures` MODIFY `fee_type` ENUM('class_wise','one_time') NOT NULL DEFAULT 'class_wise'");
    }
};
