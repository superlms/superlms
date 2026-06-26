<?php

namespace App\Console\Commands;

use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Wipe orphan student User rows — User records with role=user that have no
 * matching StudentDetail. These get left behind when a save fails mid-flow
 * (User saved → StudentDetail save crashed). The form's own self-heal handles
 * them on the next save attempt, but if the form is wedged (504-loop) you can
 * run this command from EC2 to clear the state manually:
 *
 *   docker compose exec -T app php artisan students:cleanup-orphans
 *   docker compose exec -T app php artisan students:cleanup-orphans --org=1
 *   docker compose exec -T app php artisan students:cleanup-orphans --dry-run
 */
class CleanupOrphanStudents extends Command
{
    protected $signature = 'students:cleanup-orphans
                            {--org= : Only clean orphans for this organization_id}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete student User rows that have no matching StudentDetail (orphans from failed saves).';

    public function handle(): int
    {
        $orgId  = $this->option('org');
        $dryRun = (bool) $this->option('dry-run');

        $query = User::where('role', 'user')
            ->when($orgId, fn($q) => $q->where('organization_id', $orgId))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('student_details')
                  ->whereColumn('student_details.user_id', 'users.id');
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No orphan student User rows found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} orphan student User row(s)" . ($orgId ? " in org {$orgId}" : '') . '.');

        // Show a small sample so the operator can sanity check before deleting
        $sample = (clone $query)->take(10)->get(['id', 'name', 'email', 'organization_id', 'created_at']);
        $this->table(['id', 'name', 'email', 'org_id', 'created_at'], $sample->toArray());

        if ($dryRun) {
            $this->info('Dry run — nothing deleted.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Delete all {$count} orphan(s)?", true)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} orphan(s).");

        return self::SUCCESS;
    }
}
