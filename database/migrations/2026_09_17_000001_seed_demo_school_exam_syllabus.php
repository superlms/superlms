<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give The Demo School's published exams a syllabus where they have none, so
 * the app's Exam Syllabus (exam → subject → chapters) has something to show.
 *
 * `lms:seed-exam-syllabus --demo` does the work: it only fills exams with no
 * syllabus rows at all, reuses the school's own chapters (writing a few,
 * tagged `[seed]`, only where a class and subject has none), and leaves the
 * school alone if any class with students would lose sight of its exams.
 *
 * On a fresh database there is no such school, and this does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['organizations', 'exams', 'exam_syllabus_chapters', 'chapters', 'topics'] as $table) {
            if (!Schema::hasTable($table)) {
                return;
            }
        }

        if (!DB::table('organizations')->exists()) {
            return;
        }

        Artisan::call('lms:seed-exam-syllabus', ['--demo' => true]);

        logger()->info('Seeded the demo school\'s exam syllabus', [
            'output' => Artisan::output(),
        ]);
    }

    public function down(): void
    {
        // The syllabus rows and chapters are ordinary records once written, and
        // the school may have edited them since. Deliberately a no-op.
    }
};
