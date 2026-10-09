<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an admit card was issued on: 'fee', 'attendance' or 'none' (everyone /
 * one student issued by hand). The listing shows that figure for the student —
 * fee paid %, attendance %, or both. NULL = issued before this was kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admit_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('admit_cards', 'issue_criteria')) {
                $table->string('issue_criteria', 20)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admit_cards', function (Blueprint $table) {
            if (Schema::hasColumn('admit_cards', 'issue_criteria')) {
                $table->dropColumn('issue_criteria');
            }
        });
    }
};
