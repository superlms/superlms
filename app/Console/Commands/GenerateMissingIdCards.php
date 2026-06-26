<?php

namespace App\Console\Commands;

use App\Models\Admin\IdCardGenerationSetting;
use App\Services\IdCardService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * For every organization + person-type whose auto-generation is enabled (i.e. a
 * first batch of cards has already been issued), generate ID cards for any
 * persons of that type who don't yet have an active card — e.g. students,
 * teachers or employees added after the first batch.
 *
 * Scheduled to run daily at midnight — see routes/console.php.
 */
class GenerateMissingIdCards extends Command
{
    protected $signature = 'id-cards:generate-missing';

    protected $description = 'Generate ID cards for newly-added persons of any type that already had a card batch issued.';

    public function handle(IdCardService $service): int
    {
        $settings = IdCardGenerationSetting::with('organization')
            ->where('auto_enabled', true)
            ->get();

        if ($settings->isEmpty()) {
            $this->info('No organizations have auto ID-card generation enabled. Nothing to do.');
            return self::SUCCESS;
        }

        $totalGenerated = 0;

        foreach ($settings as $setting) {
            $organization = $setting->organization;
            if (!$organization) {
                continue;
            }

            // Reuse the stored expiry; if it has already passed, push it a year out.
            $expiry = $setting->expiry_date instanceof Carbon
                ? $setting->expiry_date
                : ($setting->expiry_date ? Carbon::parse($setting->expiry_date) : now()->addYear());

            if ($expiry->isPast()) {
                $expiry = now()->addYear();
            }

            $result = $service->generateForType(
                $organization,
                $setting->type,
                $expiry->format('Y-m-d'),
            );

            if ($result['generated'] > 0) {
                $totalGenerated += $result['generated'];
                $this->info("Org #{$organization->id} [{$setting->type}]: generated {$result['generated']} card(s).");
            }

            foreach ($result['errors'] as $error) {
                $this->warn("Org #{$organization->id} [{$setting->type}]: {$error}");
            }

            $setting->update(['last_generated_at' => now()]);
        }

        $this->info("Done. Generated {$totalGenerated} ID card(s) in total.");

        return self::SUCCESS;
    }
}
