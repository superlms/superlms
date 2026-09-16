<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A class's display order is now unique within its school (see
 * App\Support\StandardOrder). Schools already have classes sharing an order —
 * a class set to 12 while another held 12, or classes all left at the default
 * 0 — so each school's classes are walked in display order here and any class
 * that shares an order moves down one, taking the run behind it along, as a
 * save does now.
 *
 * Of two classes set to the same order, the one changed last is the one the
 * admin meant to put there, so it keeps it. Classes left at 0 keep the order
 * they were created in.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('standards')) {
            return;
        }

        $orgIds = DB::table('standards')
            ->select('organization_id')
            ->distinct()
            ->pluck('organization_id');

        foreach ($orgIds as $orgId) {
            $standards = DB::table('standards')
                ->where('organization_id', $orgId)
                ->get(['id', 'order', 'updated_at'])
                ->sort(function ($a, $b) {
                    if ((int) $a->order !== (int) $b->order) {
                        return (int) $a->order <=> (int) $b->order;
                    }
                    if ((int) $a->order > 0 && $a->updated_at != $b->updated_at) {
                        return strcmp((string) $b->updated_at, (string) $a->updated_at);
                    }
                    return $a->id <=> $b->id;
                });

            $taken = null;
            foreach ($standards as $standard) {
                $order = (int) $standard->order;
                if ($taken !== null && $order <= $taken) {
                    $order = $taken + 1;
                    DB::table('standards')->where('id', $standard->id)->update(['order' => $order]);
                }
                $taken = $order;
            }
        }
    }

    public function down(): void
    {
        // The orders that were shared aren't recorded, so there is nothing to
        // put back.
    }
};
