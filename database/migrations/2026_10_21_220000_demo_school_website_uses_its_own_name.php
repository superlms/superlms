<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Demo School's sample website text was written with the school's
 * account name ("THE DEMO SCHOOL") while its website goes by the name set in
 * Website Data — the slides said "Welcome to THE DEMO SCHOOL" under a
 * masthead with the website's own name (user's ask of 2026-10-21).
 *
 * `lms:seed-demo-website --demo --rename` puts the website's name in its
 * place: every text in Website Data, the sample About text while it is still
 * the sample, and the sample PDFs, made again where they are kept. A school
 * whose website uses its own name is left alone; on a fresh database there
 * is no such school. A failure is logged and never stops a deploy.
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
            Artisan::call('lms:seed-demo-website', ['--demo' => true, '--rename' => true]);
            logger()->info('Renamed the demo school\'s sample website text', ['output' => Artisan::output()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        // The text is ordinary content once written. Deliberately a no-op.
    }
};
