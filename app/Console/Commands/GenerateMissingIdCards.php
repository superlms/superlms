<?php

namespace App\Console\Commands;

use App\Models\Admin\IdCardGenerationSetting;
use App\Models\Organization;
use App\Services\IdCardService;
use App\Support\AcademicYear;
use Carbon\Carbon;
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
 * Every other school — one that has never generated a batch — gets the same
 * for the people added from NEW_JOINERS_FROM on: each student, teacher and
 * employee (management, staff, driver) added that day has a card that night.
 * Those added before it wait for the school's own Generate, which then puts
 * the school on the run above. Its setting is not touched here.
 *
 * It is "fill the gaps" (IdCardService), so it is safe to run again and again:
 * the schedule in routes/console.php runs it at midnight and then every half
 * hour until dawn, in case midnight itself was missed. One school's failure —
 * or one kind's — is logged and the rest go on.
 */
class GenerateMissingIdCards extends Command
{
    /** The day (IST) from which every school's new people get a card by themselves. */
    public const NEW_JOINERS_FROM = '2026-10-04';

    protected $signature = 'id-cards:generate-missing';

    protected $description = 'Give an ID card to every student, teacher and employee without one, in each school that issues ID cards, and to the people added since 4 Oct 2026 in every other school.';

    public function handle(IdCardService $service): int
    {
        $organizations = Organization::whereIn(
            'id',
            IdCardGenerationSetting::where('auto_enabled', true)->select('organization_id')
        )->get();

        // Every card runs to the end of the running session: 31 March.
        $expiry = AcademicYear::end()->format('Y-m-d');

        $totalGenerated = 0;

        if ($organizations->isEmpty()) {
            $this->info('No organizations have auto ID-card generation enabled.');
        }

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

        // Every other school: the people added since NEW_JOINERS_FROM.
        $addedFrom = Carbon::parse(self::NEW_JOINERS_FROM, 'Asia/Kolkata')->startOfDay();

        foreach (Organization::whereNotIn('id', $organizations->pluck('id'))->get() as $organization) {
            foreach (IdCardService::TYPES as $type) {
                try {
                    $result = $service->generateForNewJoiners($organization, $type, $expiry, $addedFrom);

                    if ($result['generated'] > 0) {
                        $totalGenerated += $result['generated'];
                        $this->info("Org #{$organization->id} [{$type}]: generated {$result['generated']} card(s) for people added since " . self::NEW_JOINERS_FROM . '.');
                    }

                    foreach ($result['errors'] as $error) {
                        $this->warn("Org #{$organization->id} [{$type}]: {$error}");
                    }
                } catch (\Throwable $e) {
                    // This school's cards of this kind wait for the next run; the others go on.
                    $this->error("Org #{$organization->id} [{$type}]: " . $e->getMessage());
                    Log::error("id-cards:generate-missing (new joiners) failed for org {$organization->id} [{$type}]: " . $e->getMessage());
                }
            }
        }

        $this->info("Done. Generated {$totalGenerated} ID card(s) in total.");

        return self::SUCCESS;
    }
}
