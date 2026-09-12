<?php

namespace App\Services\Gemini;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The daily question allowance, counted per role inside one school.
 *
 * One bucket is one role of one organization: every admin of a school draws
 * from that school's admin allowance, every sub-admin from its sub-admin
 * allowance, and the accounts logins from theirs. That keeps a school with ten
 * sub-admins from spending ten times the budget, while still giving the roles
 * that do the most asking the bigger share. The platform super-admin is
 * unlimited.
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

    /** The day's allowance for this role. 0 means unlimited. */
    public function limit(): int
    {
        $limits = (array) config('gemini.quota.per_role_per_day', []);

        $limit = array_key_exists($this->scope->role, $limits)
            ? $limits[$this->scope->role]
            : config('gemini.quota.default_per_day', 50);

        return max(0, (int) $limit);
    }

    public function unlimited(): bool
    {
        return $this->limit() === 0;
    }

    public function used(): int
    {
        return (int) Cache::get($this->key(), 0);
    }

    public function remaining(): int
    {
        if ($this->unlimited()) {
            return PHP_INT_MAX;
        }

        return max(0, $this->limit() - $this->used());
    }

    public function exhausted(): bool
    {
        return ! $this->unlimited() && $this->remaining() <= 0;
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

        // An unlimited role is still counted — the number is worth having when
        // somebody asks what the platform side is spending — but never blocks.
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
     * Used when the failure was not the school's doing — the upstream model's
     * own quota, an outage, a dropped connection. Losing one of fifty because
     * somebody else was busy would be the wrong way round.
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
