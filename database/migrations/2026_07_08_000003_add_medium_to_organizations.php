<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `medium` column (english / hindi / both) to organizations for the
 * super-admin Schools medium filter, and backfills it for existing schools
 * whose education_board string already encodes the medium (e.g. "UP BOARD
 * (HINDI MEDIUM)").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'medium')) {
                $table->string('medium', 20)->nullable()->after('education_board');
            }
        });

        // Backfill from the board label where the medium is unambiguous.
        DB::table('organizations')->whereNull('medium')
            ->where('education_board', 'like', '%HINDI & ENGLISH%')
            ->update(['medium' => 'both']);

        DB::table('organizations')->whereNull('medium')
            ->where('education_board', 'like', '%HINDI MEDIUM%')
            ->update(['medium' => 'hindi']);

        DB::table('organizations')->whereNull('medium')
            ->where('education_board', 'like', '%ENGLISH MEDIUM%')
            ->update(['medium' => 'english']);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'medium')) {
                $table->dropColumn('medium');
            }
        });
    }
};
