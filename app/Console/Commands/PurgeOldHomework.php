<?php

namespace App\Console\Commands;

use App\Models\Admin\HomeWork;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently delete homework older than 30 days. Removes both the database
 * row and any attachment living on S3. Scheduled to run daily — see
 * routes/console.php.
 */
class PurgeOldHomework extends Command
{
    protected $signature = 'homework:purge-old {--days=30 : Age in days beyond which homework is permanently deleted}';

    protected $description = 'Hard-delete homework (and their S3 attachments) older than the cutoff in days (default 30).';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $stale = HomeWork::where('created_at', '<', $cutoff)->get();

        if ($stale->isEmpty()) {
            $this->info("No homework older than {$days} days. Nothing to purge.");
            return self::SUCCESS;
        }

        $files = 0;
        foreach ($stale as $row) {
            if ($row->file) {
                try {
                    Storage::disk('s3')->delete(ltrim(parse_url($row->file, PHP_URL_PATH), '/'));
                    $files++;
                } catch (\Throwable $e) {
                    $this->warn("Failed to delete S3 file for homework #{$row->id}: {$e->getMessage()}");
                }
            }
            $row->delete();
        }

        $this->info("Purged {$stale->count()} homework item(s) older than {$days} days (removed {$files} S3 file(s)).");

        return self::SUCCESS;
    }
}
