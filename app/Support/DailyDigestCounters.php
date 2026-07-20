<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Same-day running tallies for the super-admin 8pm digests.
 *
 * Some records (students, teachers) are hard-deleted, so at digest time the
 * rows are already gone and "how many were deleted today" can't be counted
 * from the table. Instead we increment a per-day cache counter whenever such a
 * row is removed; the digest command reads the count and clears it.
 *
 * Fails open: a cache hiccup must never break the delete that triggered it.
 */
class DailyDigestCounters
{
    /** How long a day's counter lives before it self-expires (safety net). */
    private const TTL_DAYS = 2;

    private static function key(string $name, ?string $date = null): string
    {
        return 'sa_digest:' . $name . ':' . ($date ?: now()->toDateString());
    }

    /** Increment today's counter for $name by 1. */
    public static function bump(string $name): void
    {
        try {
            $key = self::key($name);
            // Ensure the key exists with a TTL, then increment (no-op if present).
            Cache::add($key, 0, now()->addDays(self::TTL_DAYS));
            Cache::increment($key);
        } catch (\Throwable $e) {
            logger()->warning('DailyDigestCounters::bump failed: ' . $e->getMessage());
        }
    }

    /** Read a given day's counter (defaults to today). */
    public static function get(string $name, ?string $date = null): int
    {
        try {
            return (int) Cache::get(self::key($name, $date), 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Read and clear a given day's counter in one step. */
    public static function pull(string $name, ?string $date = null): int
    {
        $value = self::get($name, $date);
        try {
            Cache::forget(self::key($name, $date));
        } catch (\Throwable $e) {
            // leave it to expire via TTL
        }

        return $value;
    }
}
