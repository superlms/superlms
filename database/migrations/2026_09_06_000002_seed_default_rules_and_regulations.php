<?php

use App\Support\DefaultSchoolRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every school that has not written its own Rules & Regulations the
 * standard set to start from. Purely additive: an organization that already
 * has a row is left exactly as it is, so nothing a school wrote is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rules_and_regulations') || !Schema::hasTable('organizations')) {
            return;
        }

        $content = json_encode([
            'sections'        => DefaultSchoolRules::sections(),
            'additional_info' => [],
            'files'           => [],
            'last_updated'    => now()->toDateTimeString(),
        ]);

        $existing = DB::table('rules_and_regulations')->pluck('organization_id')->all();
        $now      = now();

        DB::table('organizations')
            ->whereNotIn('id', $existing ?: [0])
            ->orderBy('id')
            ->chunk(200, function ($organizations) use ($content, $now) {
                $rows = $organizations->map(fn ($org) => [
                    'organization_id' => $org->id,
                    'user_id'         => 0,
                    'content'         => $content,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ])->all();

                if ($rows) {
                    DB::table('rules_and_regulations')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // The rows are indistinguishable from ones a school edited afterwards,
        // so removing them on rollback would risk deleting real content.
    }
};
