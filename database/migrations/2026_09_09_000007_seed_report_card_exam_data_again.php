<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Run the report-card seeder again, now that it no longer walks away from a
 * school that has any exam at all.
 *
 * The first pass (…_000006_) skipped every organization holding so much as one
 * exam, which is exactly the state a school is in after someone has tried the
 * Exam screen once — so nothing was seeded where it was wanted. The seeder is
 * additive and gap-filling: it matches its six exams on name, term and session
 * before creating them, and only writes a mark where none exists, so running
 * it a second time adds what is missing and changes nothing else.
 *
 * Anything it writes is tagged and can be removed again with
 * `lms:clear-seeded-report-card-data`.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['organizations', 'student_details', 'exams', 'exam_copies'] as $table) {
            if (!Schema::hasTable($table)) {
                return;
            }
        }

        // student_details.organization_id is added by `lms:migrate`, which runs
        // after migrations — so on a fresh database this simply does nothing.
        if (!Schema::hasColumn('student_details', 'organization_id')) {
            return;
        }

        if (!DB::table('organizations')->exists()) {
            return;
        }

        Artisan::call('lms:seed-report-card-data');

        logger()->info('Seeded report-card exam data (second pass)', [
            'output' => Artisan::output(),
        ]);
    }

    public function down(): void
    {
        // Use `lms:clear-seeded-report-card-data` to undo this deliberately.
    }
};
