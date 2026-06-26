<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * student_details.standard_id and section_id were created NOT NULL (default 0),
 * but the app deliberately sets them to NULL to "orphan" a student when their
 * class or section is deleted (see Admin\Standard::performDeleteSection /
 * performDeleteStandard and Admin\Student::getIsOrphanedProperty).
 *
 * With the columns NOT NULL, deleting a section that still has students threw
 * SQLSTATE[23000] 1048 "Column 'section_id' cannot be null" → a 500 for the
 * admin. Make both columns nullable so orphaning works as designed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_details')) {
            return;
        }

        Schema::table('student_details', function (Blueprint $table) {
            $table->unsignedBigInteger('standard_id')->nullable()->default(null)->change();
            $table->unsignedBigInteger('section_id')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_details')) {
            return;
        }

        // Restore the old non-null shape. Coalesce any orphaned rows to 0 first
        // so the NOT NULL change can't fail on existing NULLs.
        DB::table('student_details')->whereNull('standard_id')->update(['standard_id' => 0]);
        DB::table('student_details')->whereNull('section_id')->update(['section_id' => 0]);

        Schema::table('student_details', function (Blueprint $table) {
            $table->unsignedBigInteger('standard_id')->default(0)->nullable(false)->change();
            $table->unsignedBigInteger('section_id')->default(0)->nullable(false)->change();
        });
    }
};
