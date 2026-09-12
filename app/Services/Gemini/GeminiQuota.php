<?php

namespace App\Services\Gemini;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The daily question allowance, counted per organization rather than per user.
 *
 * One school gets one allowance for the whole day, shared by every panel user
 * it has — the admin, every sub-admin and the accounts login all draw from the
 * same 50. The platform side (super-admin) has its own separate bucket.
 *
 * The window is a calendar day in the app timezone, not a rolling 24 hours, so
 * "resets at midnight" is a real answer we can show rather than a guess. The
 * date is baked into the cache key and the entry is given exactly the seconds
 * left until midnight, so the counter disappears on its own at the turn of the
 * day and nothing has to be swept.
 */
class GeminiQuota
{
    public function __construct(private readonly LmsScope $scope) {}

    public function limit(): int
    {
        return (int) ($this->scope->isSchool() || $this->scope->forcedOrganizationId()
            ? config('gemini.quota.per_organization_per_day', 50)
            : config('gemini.quota.platform_per_day', 200));
    }

    public function used(): int
    {
        return (int) Cache::get($this->key(), 0);
    }

    public function remaining(): int
    {
        return max(0, $this->limit() - $this->used());
    }

    public function exhausted(): bool
    {
        return $this->remaining() <= 0;
    }

    /** Midnight tonight, in the app timezone — when the allowance comes back. */
    public function resetsAt(): Carbon
    {
        return Carbon::now()->addDay()->startOfDay();
    }

    /** "tomorrow at 12:00 AM (in 5 hours 12 minutes)" — for the refusal line. */
    public function resetDescription(): string
    {
        $at = $this->resetsAt();

        return $at->format('D, d M') . ' at 12:00 AM (in ' . Carbon::now()->diffForHumans($at, [
            'syntax' => Carbon::DIFF_ABSOLUTE,
            'parts'  => 2,
        ]) . ')';
    }

    /**
     * Count one question. Returns false when the allowance is already spent,
     * so the caller can refuse before spending an upstream request.
     */
    public function consume(): bool
    {
        if ($this->exhausted()) {
            return false;
        }

        $key = $this->key();

        // add() then increment(): the first caller of the day creates the entry
        // with a TTL that lands exactly on midnight, and every later caller
        // increments it without touching that expiry.
        Cache::add($key, 0, $this->secondsLeftToday());
        $used = Cache::increment($key);

        // Some stores drop the TTL on a plain increment of a missing key; if the
        // counter came back as the very first hit, make sure it still expires.
        if ($used === 1) {
            Cache::put($key, 1, $this->secondsLeftToday());
        }

        return true;
    }

    /**
     * Give a question back.
     *
     * Used when the failure was not the school's doing — Gemini's own quota,
     * an outage, a dropped connection. Losing one of fifty because Google was
     * busy would be the wrong way round.
     */
    public function refund(): void
    {
        if ($this->used() > 0) {
            Cache::decrement($this->key());
        }
    }

    private function key(): string
    {
        return 'gemini:quota:' . $this->scope->quotaKey() . ':' . Carbon::now()->toDateString();
    }

    private function secondsLeftToday(): int
    {
        // Carbon 3 returns a float here; the cache store wants whole seconds.
        return max(60, (int) ceil(Carbon::now()->diffInSeconds($this->resetsAt())));
    }
}
