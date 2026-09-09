<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give schools that have never run an exam the set a report card expects.
 *
 * The card prints Term-1 (Unit Test-1, Unit Test-2, Mid Term) and Term-2 (Unit
 * Test-1, Unit Test-2, End Term). Without those exams the issue screen turns
 * every student away with "No published exams found", so a school that has
 * added students still cannot produce a single card.
 *
 * `lms:seed-report-card-data` does the work and refuses, on its own, any
 * organization that already has exams or marks — so a school with real records
 * is never touched. Subjects are only invented for a section that has none,
 * and attendance only for a school that records none.
 *
 * Nothing here runs on a fresh database: there are no organizations yet, and
 * the seeder leans on student_details.organization_id, which the `lms:migrate`
 * command adds after this file has run.
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

        if (!Schema::hasColumn('student_details', 'organization_id')) {
            return;
        }

        if (!DB::table('organizations')->exists()) {
            return;
        }

        Artisan::call('lms:seed-report-card-data');

        logger()->info('Seeded report-card exam data', [
            'output' => Artisan::output(),
        ]);
    }

    public function down(): void
    {
        // The seeded exams and marks are ordinary records once written; removing
        // them would take real data with them. Deliberately a no-op.
    }
};
