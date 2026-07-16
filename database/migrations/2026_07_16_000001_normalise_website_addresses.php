<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Terms of Use / Privacy Policy / Terms & Conditions pages render their
 * legal body from the DB (metadata JSON on term_of_uses, privacy_policies,
 * term_and_conditions) — seeded once on 2026-07-07. Editing those seed
 * migrations does NOT re-run them, so the live rows still carry the old
 * address. This migration rewrites the address in-place to the single
 * canonical form used across the whole site.
 */
return new class extends Migration
{
    private string $canonical = 'Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137';

    /** Every address variant that may exist in an already-seeded row. */
    private array $old = [
        'House No. 02, Braj Vihar Colony, Jattari, Khair, Aligarh, Uttar Pradesh – 202137',
        'Office No. 02, Braj Vihar Colony, Jattari, Khair, Aligarh, Uttar Pradesh – 202137',
        'Office No. 2, Braj Vihar Colony, Jattari, Khair, Aligarh, Uttar Pradesh – 202137',
        'House No. 1, Brij Vihar Colony Jattari Khair Aligarh, Uttar Pradesh, India',
    ];

    private array $tables = ['term_of_uses', 'privacy_policies', 'term_and_conditions'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($this->old as $needle) {
                DB::table($table)->update([
                    'metadata' => DB::raw(
                        'REPLACE(metadata, ' . $this->quote($needle) . ', ' . $this->quote($this->canonical) . ')'
                    ),
                ]);
            }
        }
    }

    public function down(): void
    {
        // One-way data normalisation; nothing to reverse.
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
};
