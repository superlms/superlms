<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A fee particular can be one student's own — Last Year Dues, added from Fee
 * Structure's Add Dues — instead of their whole class's. Every class-wide read
 * leaves such rows out (FeeStructure's class_wide scope). Guarded, so it is
 * safe on any database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_structures') && !Schema::hasColumn('fee_structures', 'student_detail_id')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->unsignedBigInteger('student_detail_id')->nullable()->index()->after('section_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_structures') && Schema::hasColumn('fee_structures', 'student_detail_id')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->dropIndex(['student_detail_id']);
                $table->dropColumn('student_detail_id');
            });
        }
    }
};
