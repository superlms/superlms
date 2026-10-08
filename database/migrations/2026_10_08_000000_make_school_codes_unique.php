<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Every school has a code of its own. Super Admin → Schools has always checked
 * it before saving; this index makes the database hold to it as well, so two
 * schools saved at the same moment cannot both take one code.
 *
 * Index only — no row is changed. Where two schools already share a code (in
 * other capitals or with spaces around it too), the index is left off rather
 * than the deploy stopped: the form's check still keeps a new school from
 * taking a code, and the codes shared are logged.
 */
return new class extends Migration
{
    private const NAME = 'organizations_school_code_unique';

    public function up(): void
    {
        if (!Schema::hasTable('organizations')
            || !Schema::hasColumn('organizations', 'school_code')
            || Schema::hasIndex('organizations', self::NAME)) {
            return;
        }

        $shared = DB::table('organizations')
            ->whereNotNull('school_code')
            ->selectRaw('LOWER(TRIM(school_code)) as code')
            ->groupByRaw('LOWER(TRIM(school_code))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code');

        if ($shared->isNotEmpty()) {
            Log::warning('School codes shared by more than one school; unique index not added', [
                'codes' => $shared->all(),
            ]);
            return;
        }

        try {
            Schema::table('organizations', fn (Blueprint $t) => $t->unique('school_code', self::NAME));
        } catch (\Throwable $e) {
            Log::warning('Could not add the unique index on school codes', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('organizations') && Schema::hasIndex('organizations', self::NAME)) {
            Schema::table('organizations', fn (Blueprint $t) => $t->dropUnique(self::NAME));
        }
    }
};
