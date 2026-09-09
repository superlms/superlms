<?php

namespace App\Console\Commands;

use App\Models\Admin\Exam;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Take back out whatever `lms:seed-report-card-data` put in.
 *
 * That command tags what it writes — `[seed]` at the head of an exam's
 * description, and as an attendance row's remark — so demo data can be removed
 * from a school without touching anything a person actually entered. Marks go
 * with the exams they belong to.
 */
class ClearSeededReportCardData extends Command
{
    protected $signature = 'lms:clear-seeded-report-card-data
                            {--org= : Only this organization id}
                            {--keep-attendance : Leave the seeded attendance in place}';

    protected $description = 'Remove the demo exams, marks and attendance written by lms:seed-report-card-data';

    public function handle(): int
    {
        $tag = SeedReportCardData::SEED_TAG;

        $orgIds = $this->option('org')
            ? [(int) $this->option('org')]
            : Organization::pluck('id')->all();

        foreach ($orgIds as $orgId) {
            $org   = Organization::find($orgId);
            $label = "[{$orgId}] " . ($org->name ?? 'unknown');

            $examIds = Exam::where('organization_id', $orgId)
                ->where('description', 'like', $tag . '%')
                ->pluck('id');

            $marks = 0;
            if ($examIds->isNotEmpty()) {
                $marks = DB::table('exam_copies')->whereIn('exam_id', $examIds)->delete();
                Exam::whereIn('id', $examIds)->delete();
            }

            $attendance = 0;
            if (!$this->option('keep-attendance')) {
                $attendance = DB::table('student_attendances')
                    ->where('organization_id', $orgId)
                    ->where('remarks', $tag)
                    ->delete();
            }

            if ($examIds->isEmpty() && $attendance === 0) {
                continue;
            }

            $this->info("{$label}: removed {$examIds->count()} exam(s), {$marks} mark(s), {$attendance} attendance row(s).");
        }

        $this->line('Subjects created for empty sections are left in place — they are part of the school setup, not demo data.');

        return self::SUCCESS;
    }
}
