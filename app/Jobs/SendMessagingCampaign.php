<?php

namespace App\Jobs;

use App\Models\Messaging\MessagingCampaign;
use App\Services\MessagingWallet;
use App\Services\SchoolEmail;
use App\Services\SchoolWhatsApp;
use App\Support\MessagingAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Sends a school's message to many (More → Messaging → Send), a part at a
 * time: about forty seconds' worth a run, then the job queues itself again
 * for the rest, so a whole school never outlasts the worker's time limit.
 * Each person is sent to once (a dedupe key per message and person); a
 * person with no number or email, or the wallet running short with Prepaid
 * on, is skipped.
 */
class SendMessagingCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 110;

    /** Seconds of sending a run. */
    private const RUN_SECONDS = 40;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(): void
    {
        // One run at a time for a message.
        $lock = Cache::lock('messaging-campaign:' . $this->campaignId, 120);
        if (!$lock->get()) {
            return;
        }

        try {
            $more = $this->run();
        } finally {
            $lock->release();
        }

        // The rest in a run of its own, once this one has let go of the message.
        if ($more) {
            self::dispatch($this->campaignId);
        }
    }

    /** Sends a run's worth; whether there is more to send. */
    private function run(): bool
    {
        $campaign = MessagingCampaign::find($this->campaignId);
        if (!$campaign || $campaign->status === 'done') {
            return false;
        }

        $recipients = MessagingAudience::forCampaign($campaign);
        $total      = count($recipients);
        $orgId      = (int) $campaign->organization_id;
        $started    = microtime(true);
        $i          = (int) $campaign->done;

        $campaign->forceFill(['status' => 'sending', 'recipients' => max($total, (int) $campaign->recipients)])->save();

        for (; $i < $total; $i++) {
            if (microtime(true) - $started > self::RUN_SECONDS) {
                break;
            }

            $r      = $recipients[$i];
            $who    = array_filter(['user_id' => $r['user_id'], 'student_detail_id' => $r['student_detail_id']]);
            $dedupe = 'campaign:' . $campaign->id . ':' . $r['key'];

            if ($campaign->channel === 'whatsapp') {
                if (!$r['phone']) {
                    $outcome = 'skipped';
                } elseif (!MessagingWallet::canSpend($orgId, SchoolWhatsApp::rate())) {
                    $outcome = 'skipped';
                } elseif (SchoolWhatsApp::sentFor($dedupe)) {
                    $outcome = null;   // gone in an earlier run
                } else {
                    $outcome = SchoolWhatsApp::notice($orgId, $r['phone'], $campaign->body, $who, $dedupe) ? 'sent' : 'failed';
                }
            } else {
                $outcome = SchoolEmail::send($orgId, $r['email'], $r['name'], (string) $campaign->subject, $campaign->body, $who, $campaign->id, $dedupe);
            }

            $campaign->done = $i + 1;
            if ($outcome !== null) {
                $campaign->{$outcome} = (int) $campaign->{$outcome} + 1;
            }
            // Keep the count the school sees moving.
            if ($i % 10 === 0) {
                $campaign->save();
            }
        }

        if ($i >= $total) {
            $campaign->status      = 'done';
            $campaign->finished_at = now();
            $campaign->save();
            return false;
        }

        $campaign->save();

        return true;
    }
}
