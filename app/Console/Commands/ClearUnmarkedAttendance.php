<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove attendance for past dates that nobody actually marked.
 *
 * Two sources put rows in the table without a person behind them:
 *
 *   • `lms:seed-report-card-data`, which fills up to 150 days per student at
 *     roughly 92% present so report cards have something to average. Those
 *     rows carry `[seed]` as their remark.
 *   • the older mark screens, which started every row on Present and wrote the
 *     whole list on save — so opening the panel and saving marked an entire
 *     class present whether or not anyone had been looked at.
 *
 * Both leave `marked_by` at 0, because neither ran as a signed-in user marking
 * a register. Every real path — the admin panel, the accounts panel, and the
 * teacher app's API — records the id of whoever marked it. That makes
 * `marked_by = 0` a reliable "no person marked this" signal, and it is what
 * this command deletes, together with anything carrying the seed tag.
 *
 * Rows a person marked are never touched, and neither is today or the future.
 *
 * It reports and stops unless --delete is passed, so you can always look first.
 */
class ClearUnmarkedAttendance extends Command
{
    protected $signature = 'attendance:clear-unmarked
        {--org= : Only this organization id (default: every organization)}
        {--before= : Only dates before this Y-m-d (default: today, i.e. everything already past)}
        {--teachers : Clean teacher attendance as well as student attendance}
        {--delete : Actually delete. Without it the command only reports.}';

    protected $description = 'Delete past-dated attendance rows that no person marked (seeded or auto-present).';

    private const SEED_TAG = '[seed]';

    public function handle(): int
    {
        $before = $this->option('before')
            ? Carbon::parse($this->option('before'))->startOfDay()
            : Carbon::today();

        $orgIds = $this->option('org')
            ? [(int) $this->option('org')]
            : Organization::pluck('id')->all();

        if (empty($orgIds)) {
            $this->warn('No organizations found.');
            return self::SUCCESS;
        }

        $tables = [['student_attendances', 'student']];
        if ($this->option('teachers')) {
            $tables[] = ['teacher_attendances', 'teacher'];
        }

        $this->line('Unmarked attendance before ' . $before->toDateString() . ':');
        $this->newLine();

        $grandTotal = 0;

        foreach ($tables as [$table, $label]) {
            foreach ($orgIds as $orgId) {
                $rows = $this->scope($table, $orgId, $before);

                $total = (clone $rows)->count();
                if ($total === 0) {
                    continue;
                }

                $present = (clone $rows)->where('status', 1)->count();
                $seeded  = (clone $rows)->where('remarks', self::SEED_TAG)->count();
                $span    = (clone $rows)->selectRaw('MIN(attendance_date) as a, MAX(attendance_date) as b')->first();
                $org     = Organization::find($orgId);
                $name    = $org->name ?? 'unknown';

                $this->line(sprintf(
                    '  [%d] %s · %s: %d row(s) — %d present, %d seeded, %s → %s',
                    $orgId, $name, $label, $total, $present, $seeded, $span->a ?? '?', $span->b ?? '?'
                ));

                if ($this->option('delete')) {
                    $deleted = $this->scope($table, $orgId, $before)->delete();
                    $this->info("      deleted {$deleted}");
                }

                $grandTotal += $total;
            }
        }

        $this->newLine();

        if ($grandTotal === 0) {
            $this->info('Nothing to clean — every past attendance row was marked by a person.');
            return self::SUCCESS;
        }

        if ($this->option('delete')) {
            $this->info("Removed {$grandTotal} unmarked attendance row(s). Those days now read as not marked.");
        } else {
            $this->warn("{$grandTotal} row(s) would be removed. Re-run with --delete to do it.");
        }

        return self::SUCCESS;
    }

    /**
     * Past-dated rows for one organization that carry no marker: either the
     * report-card seed tag, or no marked_by at all.
     */
    private function scope(string $table, int $orgId, Carbon $before)
    {
        return DB::table($table)
            ->where('organization_id', $orgId)
            ->whereDate('attendance_date', '<', $before->toDateString())
            ->where(function ($q) {
                $q->whereNull('marked_by')
                    ->orWhere('marked_by', 0)
                    ->orWhere('remarks', self::SEED_TAG);
            });
    }
}
