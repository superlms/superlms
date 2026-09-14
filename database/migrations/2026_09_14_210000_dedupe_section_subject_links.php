<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A subject linked to the same section more than once listed that section
 * twice ("CLASS 7 · SECTION A, SECTION A"). section_subjects had nothing
 * stopping it, and the app's subject save wrote a row per section id it was
 * sent — a repeated id or a double-tapped save made a second link.
 *
 *  1. Keep the oldest row of each (section, subject, class) link, drop the rest.
 *  2. Make that link unique, so it cannot happen again.
 *
 * Guarded and idempotent: nothing to fix means nothing runs.
 */
return new class extends Migration
{
    private const INDEX = 'section_subjects_link_unique';

    public function up(): void
    {
        if (! Schema::hasTable('section_subjects')) {
            return;
        }

        $dupes = DB::table('section_subjects')
            ->select('section_id', 'subject_id', 'standard_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('section_id', 'subject_id', 'standard_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $link) {
            DB::table('section_subjects')
                ->where('section_id', $link->section_id)
                ->where('subject_id', $link->subject_id)
                ->where('standard_id', $link->standard_id)
                ->where('id', '!=', $link->keep_id)
                ->delete();
        }

        if (! Schema::hasIndex('section_subjects', self::INDEX)) {
            Schema::table('section_subjects', function (Blueprint $table) {
                $table->unique(['section_id', 'subject_id', 'standard_id'], self::INDEX);
            });
        }
    }

    public function down(): void
    {
        // The duplicate rows were copies; only the guard can be undone.
        if (Schema::hasTable('section_subjects') && Schema::hasIndex('section_subjects', self::INDEX)) {
            Schema::table('section_subjects', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }
    }
};
