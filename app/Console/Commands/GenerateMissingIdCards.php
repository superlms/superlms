<?php

namespace App\Console\Commands;

use App\Models\Admin\IdCardGenerationSetting;
use App\Models\Organization;
use App\Services\IdCardService;
use App\Support\AcademicYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Every night: in each school that issues ID cards, give a card to every
 * student, teacher and employee who has none — the people added during the
 * day. Each card runs to the end of the session, 31 March.
 *
 * A school "issues ID cards" once a batch of any kind has been generated for
 * it (that is what switches its auto-generation on). From then on all three
 * kinds are kept up: it used to be only the kind the batch was of, so a school
 * that had issued students' cards never got its new teachers' or employees'.
 *
 * It is "fill the gaps" (IdCardService), so it is safe to run again and again:
 * the schedule in routes/console.php runs it at midnight and then every half
 * hour until dawn, in case midnight itself was missed. One school's failure —
 * or one kind's — is logged and the rest go on.
 */
class GenerateMissingIdCards extends Command
{
    protected $signature = 'id-cards:generate-missing';

    protected $description = 'Give an ID card to every student, teacher and employee without one, in each school that issues ID cards.';

    public function handle(IdCardService $service): int
    {
        $organizations = Organization::whereIn(
            'id',
            IdCardGenerationSetting::where('auto_enabled', true)->select('organization_id')
        )->get();

        if ($organizations->isEmpty()) {
            $this->info('No organizations have auto ID-card generation enabled. Nothing to do.');
            return self::SUCCESS;
        }

        // Every card runs to the end of the running session: 31 March.
        $expiry = AcademicYear::end()->format('Y-m-d');

        $totalGenerated = 0;

        foreach ($organizations as $organization) {
            foreach (IdCardService::TYPES as $type) {
                try {
                    $result = $service->generateForType($organization, $type, $expiry);

                    if ($result['generated'] > 0) {
                        $totalGenerated += $result['generated'];
                        $this->info("Org #{$organization->id} [{$type}]: generated {$result['generated']} card(s).");
                    }

                    foreach ($result['errors'] as $error) {
                        $this->warn("Org #{$organization->id} [{$type}]: {$error}");
                    }

                    // Cards a Generate click made after its QR time ran out.
                    $filled = $service->fillMissingQrCodes($organization, $type);
                    if ($filled > 0) {
                        $this->info("Org #{$organization->id} [{$type}]: drew {$filled} missing QR code(s).");
                    }

                    IdCardGenerationSetting::updateOrCreate(
                        ['organization_id' => $organization->id, 'type' => $type],
                        ['auto_enabled' => true, 'expiry_date' => $expiry, 'last_generated_at' => now()],
                    );
                } catch (\Throwable $e) {
                    // This school's cards of this kind wait for the next run; the others go on.
                    $this->error("Org #{$organization->id} [{$type}]: " . $e->getMessage());
                    Log::error("id-cards:generate-missing failed for org {$organization->id} [{$type}]: " . $e->getMessage());
                }
            }
        }

        $this->info("Done. Generated {$totalGenerated} ID card(s) in total.");

        return self::SUCCESS;
    }
}
