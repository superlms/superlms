<?php

namespace App\Console\Commands;

use App\Models\Admin\Announcement;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Delete admin announcements that are older than 60 days. Removes both the
 * database row and any image / PDF attachments living on S3. Scheduled to
 * run daily — see routes/console.php.
 */
class PurgeOldAnnouncements extends Command
{
    protected $signature = 'announcements:purge-old {--days=60 : Age in days beyond which announcements are deleted}';

    protected $description = 'Hard-delete admin announcements (and their S3 attachments) older than the cutoff in days (default 60).';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $stale = Announcement::where('created_at', '<', $cutoff)->get();

        if ($stale->isEmpty()) {
            $this->info("No announcements older than {$days} days. Nothing to purge.");
            return self::SUCCESS;
        }

        $files = 0;
        foreach ($stale as $row) {
            if ($row->announcement_image) {
                $p = parse_url($row->announcement_image, PHP_URL_PATH);
                try {
                    Storage::disk('s3')->delete(ltrim($p, '/'));
                    $files++;
                } catch (\Throwable $e) {
                    $this->warn("Failed to delete S3 image for announcement #{$row->id}: {$e->getMessage()}");
                }
            }
            if ($row->announcement_pdf) {
                $p = parse_url($row->announcement_pdf, PHP_URL_PATH);
                try {
                    Storage::disk('s3')->delete(ltrim($p, '/'));
                    $files++;
                } catch (\Throwable $e) {
                    $this->warn("Failed to delete S3 PDF for announcement #{$row->id}: {$e->getMessage()}");
                }
            }
            $row->delete();
        }

        $this->info("Purged {$stale->count()} announcement(s) older than {$days} days (removed {$files} S3 file(s)).");

        return self::SUCCESS;
    }
}
