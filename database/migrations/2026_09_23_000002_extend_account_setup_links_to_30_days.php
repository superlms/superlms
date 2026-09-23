<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Setup links now last 30 days, not 7. The ones already sent get the same:
 * 30 days from when each was made.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_setup_links')) {
            return;
        }

        DB::table('account_setup_links')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('account_setup_links')->where('id', $row->id)->update([
                    'expires_at' => \Illuminate\Support\Carbon::parse($row->created_at ?? now())->addDays(30),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Left at 30 days.
    }
};
