<?php

use App\Models\SchoolWebsite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Website Data used to save a copy of everything it fetched from the school's
 * profile, so a site kept showing that copy after the profile changed — The
 * Demo School's site showed its logo from the old CloudFront address, gone
 * with the old AWS account, while its profile holds the logo in today's
 * bucket. Each site's stored content is cut back to its real overrides
 * (SchoolWebsite::overridesOnly), so it follows the profile again. Nothing a
 * school typed differently from its profile is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_websites') || ! Schema::hasTable('organizations')) {
            return;
        }

        SchoolWebsite::with('organization.schoolInfo')->get()->each(function (SchoolWebsite $site) {
            if (! is_array($site->content) || ! $site->organization) {
                return;
            }
            $trimmed = $site->overridesOnly($site->content);
            if ($trimmed !== $site->content) {
                // Quietly: the site's go-live notice listens for updates.
                SchoolWebsite::withoutEvents(fn () => $site->forceFill(['content' => $trimmed])->save());
            }
        });
    }

    public function down(): void
    {
        // The dropped copies equalled the profile (or were the dead logo link);
        // nothing to bring back.
    }
};
