<?php

namespace App\Console\Commands;

use App\Models\Admin\HomeWork;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fill in homework for the recent past so the Homework and Homework Status
 * screens have something to show: one entry per class → section → subject for
 * each of the last N days (default 5).
 *
 * It never touches a day that already has homework for that combination, so
 * running it twice adds nothing the second time and real entries are left
 * alone. Run it deliberately — it is not scheduled.
 */
class SeedRecentHomework extends Command
{
    protected $signature = 'homework:seed-recent
        {--days=5 : How many days back to fill, counting today}
        {--org= : Only this organization id (default: every organization)}
        {--dry-run : List what would be created without writing anything}';

    protected $description = 'Create homework for every class-section-subject for each of the last N days (default 5).';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $orgIds = $this->option('org')
            ? [(int) $this->option('org')]
            : Organization::pluck('id')->all();

        if (empty($orgIds)) {
            $this->warn('No organizations found.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($orgIds as $orgId) {
            [$c, $s] = $this->seedOrganization($orgId, $days, $dryRun);
            $created += $c;
            $skipped += $s;
        }

        $verb = $dryRun ? 'Would create' : 'Created';
        $this->info("{$verb} {$created} homework entr(ies); skipped {$skipped} that already existed.");

        return self::SUCCESS;
    }

    /** @return array{0:int,1:int} [created, skipped] */
    private function seedOrganization(int $orgId, int $days, bool $dryRun): array
    {
        $combos = $this->classSubjectCombos($orgId);

        if ($combos->isEmpty()) {
            $this->line("Org #{$orgId}: no class/section/subject set up — nothing to seed.");
            return [0, 0];
        }

        // Attribute the entries to a real teacher where one exists, so the
        // Homework tab's teacher filter has something to match on.
        $authorId = User::where('organization_id', $orgId)->where('role', 'teacher')
                ->where('is_active', true)->orderBy('id')->value('id')
            ?? User::where('organization_id', $orgId)->where('role', 'admin')->orderBy('id')->value('id')
            ?? 0;

        $dates = collect(range(0, $days - 1))->map(fn($i) => Carbon::today()->subDays($i));

        // Everything already on the books for this window, so a re-run is a no-op.
        $existing = HomeWork::where('organization_id', $orgId)
            ->whereDate('created_at', '>=', $dates->last()->toDateString())
            ->get(['standard_id', 'section_id', 'subject_id', 'created_at'])
            ->map(fn($h) => $this->key($h->standard_id, $h->section_id, $h->subject_id, Carbon::parse($h->created_at)->toDateString()))
            ->flip();

        $rows = [];
        $skipped = 0;

        foreach ($combos as $combo) {
            foreach ($dates as $date) {
                $key = $this->key($combo->standard_id, $combo->section_id, $combo->subject_id, $date->toDateString());
                if ($existing->has($key)) {
                    $skipped++;
                    continue;
                }

                // Land it mid-morning rather than at midnight so the assigned-on
                // timestamp reads like a real school day.
                $at = $date->copy()->setTime(9, 30);

                $rows[] = [
                    'organization_id' => $orgId,
                    'user_id'         => $authorId,
                    'standard_id'     => $combo->standard_id,
                    'section_id'      => $combo->section_id,
                    'subject_id'      => $combo->subject_id,
                    'title'           => $combo->subject_name . ' homework — ' . $date->format('d M'),
                    'description'     => 'Classwork and exercises covered on ' . $date->format('l, d M Y') . '.',
                    'created_at'      => $at,
                    'updated_at'      => $at,
                ];
            }
        }

        if (empty($rows)) {
            $this->line("Org #{$orgId}: already covered for the last {$days} day(s).");
            return [0, $skipped];
        }

        if ($dryRun) {
            $this->line("Org #{$orgId}: would create " . count($rows) . ' entr(ies).');
            return [count($rows), $skipped];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('home_works')->insert($chunk);
        }

        $this->line("Org #{$orgId}: created " . count($rows) . ' entr(ies).');

        return [count($rows), $skipped];
    }

    /**
     * Every class → section → subject the organization actually teaches. Uses
     * section_subjects where sections carry their own subjects, and falls back
     * to standard_subjects (spread across the class's sections) where they
     * don't. A class with no sections is stored with section_id 0, matching how
     * the homework screen saves "no section".
     */
    private function classSubjectCombos(int $orgId)
    {
        $sectionLevel = DB::table('section_subjects')
            ->join('subjects', 'subjects.id', '=', 'section_subjects.subject_id')
            ->where('section_subjects.organization_id', $orgId)
            ->where('subjects.is_active', true)
            ->select([
                'section_subjects.standard_id',
                'section_subjects.section_id',
                'section_subjects.subject_id',
                'subjects.name as subject_name',
            ])
            ->distinct()
            ->get();

        // Classes already covered section-by-section need no fallback.
        $covered = $sectionLevel->pluck('standard_id')->unique()->all();

        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->whereNotIn('id', $covered)->pluck('id');

        if ($standards->isEmpty()) {
            return $sectionLevel;
        }

        $classLevel = DB::table('standard_subjects')
            ->join('subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
            ->whereIn('standard_subjects.standard_id', $standards)
            ->where('subjects.organization_id', $orgId)
            ->where('subjects.is_active', true)
            ->select(['standard_subjects.standard_id', 'standard_subjects.subject_id', 'subjects.name as subject_name'])
            ->distinct()
            ->get();

        $sectionsByStandard = Section::whereIn('standard_id', $standards)->where('is_active', true)
            ->get(['id', 'standard_id'])->groupBy('standard_id');

        foreach ($classLevel as $row) {
            $sections = $sectionsByStandard->get($row->standard_id);

            if ($sections === null || $sections->isEmpty()) {
                // section_id is NOT NULL default 0 on home_works — 0 means "none".
                $sectionLevel->push((object) [
                    'standard_id'  => $row->standard_id,
                    'section_id'   => 0,
                    'subject_id'   => $row->subject_id,
                    'subject_name' => $row->subject_name,
                ]);
                continue;
            }

            foreach ($sections as $section) {
                $sectionLevel->push((object) [
                    'standard_id'  => $row->standard_id,
                    'section_id'   => $section->id,
                    'subject_id'   => $row->subject_id,
                    'subject_name' => $row->subject_name,
                ]);
            }
        }

        return $sectionLevel;
    }

    private function key($standardId, $sectionId, $subjectId, string $date): string
    {
        return $standardId . ':' . $sectionId . ':' . $subjectId . ':' . $date;
    }
}
