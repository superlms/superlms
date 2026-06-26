<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fee_type column if not already present
        if (!Schema::hasColumn('super_admin_fee_structures', 'fee_type')) {
            Schema::table('super_admin_fee_structures', function (Blueprint $table) {
                $table->enum('fee_type', ['class_wise', 'one_time'])->default('class_wise')->after('organization_id');
            });
        }

        // 2. Drop standard_id FK on structures if it exists (needed to MODIFY the column)
        $fkExists = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'super_admin_fee_structures'
               AND CONSTRAINT_NAME = 'super_admin_fee_structures_standard_id_foreign'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if (!empty($fkExists)) {
            DB::statement('ALTER TABLE `super_admin_fee_structures` DROP FOREIGN KEY `super_admin_fee_structures_standard_id_foreign`');
        }

        // 3. Make standard_id nullable — unique index stays (MySQL allows multiple NULLs)
        DB::statement('ALTER TABLE `super_admin_fee_structures` MODIFY `standard_id` BIGINT UNSIGNED NULL');

        // 4. Drop standard_id FK on payments if it exists
        $payFkExists = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'super_admin_fee_payments'
               AND CONSTRAINT_NAME = 'super_admin_fee_payments_standard_id_foreign'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if (!empty($payFkExists)) {
            DB::statement('ALTER TABLE `super_admin_fee_payments` DROP FOREIGN KEY `super_admin_fee_payments_standard_id_foreign`');
        }

        // 5. Make standard_id nullable in payments
        DB::statement('ALTER TABLE `super_admin_fee_payments` MODIFY `standard_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('super_admin_fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('super_admin_fee_structures', 'fee_type')) {
                $table->dropColumn('fee_type');
            }
        });

        DB::statement('ALTER TABLE `super_admin_fee_structures` MODIFY `standard_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `super_admin_fee_payments` MODIFY `standard_id` BIGINT UNSIGNED NOT NULL');
    }
};
