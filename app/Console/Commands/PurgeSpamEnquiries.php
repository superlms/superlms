<?php

namespace App\Console\Commands;

use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use Illuminate\Console\Command;

/**
 * Removes junk website demo/contact enquiries created by bots or test scripts.
 *
 * The flood seen in production used reserved/test email domains (RFC 2606
 * example.*, plus common throwaway domains) — those can never be a genuine
 * lead, so they are safe to delete.
 *
 * Defaults to a DRY RUN (reports counts only). Pass --apply to actually delete.
 *
 *   php artisan website:purge-spam-enquiries           # report only
 *   php artisan website:purge-spam-enquiries --apply    # delete
 */
class PurgeSpamEnquiries extends Command
{
    protected $signature = 'website:purge-spam-enquiries
                            {--apply : Permanently delete the matched rows (otherwise just reports the count)}';

    protected $description = 'Remove junk website demo/contact enquiries from reserved/test email domains.';

    /** Email domains that can never be a real lead. */
    private const BLOCKED_DOMAINS = [
        'example.com', 'example.org', 'example.net',
        'test.com', 'test.test', 'mailinator.com', 'localhost', 'invalid',
    ];

    public function handle(): int
    {
        $demoCount    = $this->scopeQuery(WebsiteDemo::query())->count();
        $contactCount = $this->scopeQuery(WebsiteContact::query())->count();
        $total        = $demoCount + $contactCount;

        $this->info("Matched spam enquiries — demo: {$demoCount}, contact: {$contactCount} (total: {$total}).");

        if ($total === 0) {
            $this->info('Nothing to clean up.');
            return self::SUCCESS;
        }

        if (! $this->option('apply')) {
            $this->warn('Dry run — nothing deleted. Re-run with --apply to permanently delete these rows.');
            return self::SUCCESS;
        }

        $this->scopeQuery(WebsiteDemo::query())->delete();
        $this->scopeQuery(WebsiteContact::query())->delete();

        $this->info("Deleted {$total} spam enquiries.");

        return self::SUCCESS;
    }

    /** Constrain a query to rows whose email belongs to a blocked domain. */
    private function scopeQuery($query)
    {
        return $query->where(function ($q) {
            foreach (self::BLOCKED_DOMAINS as $domain) {
                $q->orWhere('email', 'like', '%@' . $domain);
            }
        });
    }
}
