<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fill The Demo School's website with sample data on every page, so the site
 * can be seen as it looks with real content (user's ask of 2026-10-21).
 *
 * `lms:seed-demo-website --demo` does the work: Website Data's empty fields
 * are filled and every page switched on; the School Profile's texts and team,
 * Rules & Regulations, Transport and the calendar get sample records only
 * where the school has none. Classes and fees are never touched.
 *
 * On a fresh database there is no such school, and this does nothing. A
 * failure is logged and never stops a deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['organizations', 'school_websites'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }
        if (! DB::table('organizations')->exists()) {
            return;
        }

        try {
            Artisan::call('lms:seed-demo-website', ['--demo' => true]);
            logger()->info('Seeded the demo school\'s website', ['output' => Artisan::output()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        // The seeded content and records are ordinary data once written, and
        // the school may have edited them since. Deliberately a no-op.
    }
};
