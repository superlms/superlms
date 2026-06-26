<?php

namespace Database\Seeders;

use App\Models\WebsitePage;
use Illuminate\Database\Seeder;

/**
 * Seeds the six dynamic marketing pages with their initial content from
 * config/website_pages.php. Uses firstOrCreate so re-running the seeder
 * never clobbers edits made from the super-admin panel.
 */
class WebsitePageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('website_pages', []) as $slug => $metadata) {
            WebsitePage::firstOrCreate(
                ['slug' => $slug],
                ['metadata' => $metadata, 'last_updated' => now()->toDateString()]
            );
        }
    }
}
