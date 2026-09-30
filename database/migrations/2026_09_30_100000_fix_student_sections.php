<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Students whose section is not one of their class's own — none, or a
 * section of another class. The latter showed its name in the Students list
 * ("Class 8-A") while the edit form had no section picked and the section
 * filter never found them. Each goes into their class's section of the same
 * name, or into the class's only section when it has just one — the rule
 * App\Support\StudentSection applies on every save from now on (copied here so
 * this never changes after it has run). Anyone else is left as they are.
 *
 * Written straight to the table, so no student is sent a push for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sections = DB::table('sections')->get(['id', 'standard_id', 'name']);
        $byId     = $sections->keyBy('id');
        $byClass  = $sections->groupBy('standard_id');
        $key      = fn ($name) => mb_strtolower(trim((string) $name));

        DB::table('student_details')
            ->whereNotNull('standard_id')
            ->select(['id', 'standard_id', 'section_id'])
            ->chunkById(500, function ($rows) use ($byId, $byClass, $key) {
                foreach ($rows as $row) {
                    $own = $byClass[$row->standard_id] ?? collect();

                    if ($row->section_id && $own->contains('id', $row->section_id)) {
                        continue;
                    }

                    $target = null;

                    if ($row->section_id && isset($byId[$row->section_id])) {
                        $name   = $key($byId[$row->section_id]->name);
                        $target = $name === '' ? null : $own->first(fn ($s) => $key($s->name) === $name)?->id;
                    }

                    if (!$target && $own->count() === 1) {
                        $target = $own->first()->id;
                    }

                    if ($target && (int) $target !== (int) $row->section_id) {
                        DB::table('student_details')->where('id', $row->id)->update(['section_id' => $target]);
                    }
                }
            });
    }

    public function down(): void
    {
        // The sections put right were wrong; there is nothing to put back.
    }
};
