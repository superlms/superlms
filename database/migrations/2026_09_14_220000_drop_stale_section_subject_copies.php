<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A section moved to another class kept its subject links under the old
 * class. Linking the same subject to it again under the new class left two
 * rows for one section, and the subject listed it twice ("SECTION A,
 * SECTION A") — the (section, subject, class) guard sees them as different.
 *
 * Drop the old-class copy only where the link under the section's current
 * class already exists, so no section loses a subject.
 *
 * Guarded and idempotent: nothing to fix means nothing runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('section_subjects') || ! Schema::hasTable('sections')) {
            return;
        }

        $stale = DB::table('section_subjects as ss')
            ->join('sections as sec', 'sec.id', '=', 'ss.section_id')
            ->whereColumn('ss.standard_id', '!=', 'sec.standard_id')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('section_subjects as ok')
                ->whereColumn('ok.section_id', 'ss.section_id')
                ->whereColumn('ok.subject_id', 'ss.subject_id')
                ->whereColumn('ok.standard_id', 'sec.standard_id'))
            ->pluck('ss.id');

        foreach ($stale->chunk(500) as $ids) {
            DB::table('section_subjects')->whereIn('id', $ids->all())->delete();
        }
    }

    public function down(): void
    {
        // The dropped rows were stale copies of links that remain. Nothing to undo.
    }
};
