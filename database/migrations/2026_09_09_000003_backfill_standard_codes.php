<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Classes are now auto-coded "01", "02", "03" … per school, and the student
 * roll number is built from the last digit of that code. Existing classes were
 * created before codes were stored, so number them here in their display order
 * (the `order` column, then id) so every school starts from a clean sequence.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('standards') || !Schema::hasColumn('standards', 'code')) {
            return;
        }

        $orgIds = DB::table('standards')
            ->select('organization_id')
            ->distinct()
            ->pluck('organization_id');

        foreach ($orgIds as $orgId) {
            $standards = DB::table('standards')
                ->where('organization_id', $orgId)
                ->orderBy('order')
                ->orderBy('id')
                ->pluck('id');

            foreach ($standards as $i => $id) {
                DB::table('standards')
                    ->where('id', $id)
                    ->update(['code' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)]);
            }
        }
    }

    public function down(): void
    {
        // Codes are the source of truth for roll numbers now — clearing them
        // would be destructive, so this is deliberately a no-op.
    }
};
