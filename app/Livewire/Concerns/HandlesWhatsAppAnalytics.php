<?php

namespace App\Livewire\Concerns;

use App\Services\SchoolWhatsApp;

/**
 * The WhatsApp analytics of one school (livewire.partials.whatsapp-analytics):
 * the last 30 days or a month picked, how many of each kind went day by day,
 * what the charged ones came to, how far they got, and the messages
 * themselves, filtered by kind and status. Super Admin → Schools → a school →
 * WhatsApp (with the on / off switches) and the school admin's Analytics.
 */
trait HandlesWhatsAppAnalytics
{
    public string $waPeriod  = '';   // '' = the last 30 days; '2026-10' = a month
    public string $waFeature = '';   // '' = every kind
    public string $waStatus  = '';   // '' | sent | delivered | read | failed
    public int    $waShown   = 25;

    /** The school the analytics is for. */
    abstract protected function whatsAppOrgId(): ?int;

    public function updatedWaPeriod(): void  { $this->waShown = 25; }
    public function updatedWaFeature(): void { $this->waShown = 25; }
    public function updatedWaStatus(): void  { $this->waShown = 25; }

    public function showMoreWhatsApp(): void
    {
        $this->waShown += 50;
    }

    protected function resetWhatsAppFilters(): void
    {
        $this->waPeriod  = '';
        $this->waFeature = '';
        $this->waStatus  = '';
        $this->waShown   = 25;
    }

    /** Everything the analytics shows, or null before the tables are there. */
    public function whatsAppAnalytics(): ?array
    {
        $orgId = $this->whatsAppOrgId();
        if (!$orgId || !SchoolWhatsApp::tracked()) {
            return null;
        }

        $periods = SchoolWhatsApp::periods($orgId);
        $period  = array_key_exists($this->waPeriod, $periods) ? $this->waPeriod : '';
        [$from, $to, $label] = SchoolWhatsApp::periodRange($period);

        return [
            'periods'  => $periods,
            'period'   => $period,
            'label'    => $label,
            'usage'    => SchoolWhatsApp::usageBetween($orgId, $from, $to),
            'daily'    => SchoolWhatsApp::daily($orgId, $from, $to),
            'messages' => SchoolWhatsApp::messages($orgId, $from, $to, $this->waFeature ?: null, $this->waStatus ?: null, $this->waShown),
            'switches' => SchoolWhatsApp::switches($orgId),
            'rate'     => SchoolWhatsApp::rate(),
        ];
    }
}
