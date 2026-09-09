<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * The school session runs April → March. Exports label their columns by it,
 * so the boundary lives in one place.
 */
class AcademicYear
{
    /** 1 April of the session that contains $on (defaults to today). */
    public static function start(?Carbon $on = null): Carbon
    {
        $on   = ($on ?: now())->copy();
        $year = $on->month >= 4 ? $on->year : $on->year - 1;

        return Carbon::create($year, 4, 1)->startOfDay();
    }

    /** 31 March that closes the same session. */
    public static function end(?Carbon $on = null): Carbon
    {
        return self::start($on)->addYear()->subDay()->endOfDay();
    }

    /** "2026-27" */
    public static function label(?Carbon $on = null): string
    {
        $start = self::start($on);

        return $start->year . '-' . substr((string) ($start->year + 1), -2);
    }

    /**
     * The twelve months of the session in order, as
     * ['key' => '2026-04', 'label' => 'Apr'] — key matches the
     * DATE_FORMAT(date, '%Y-%m') the attendance aggregates group by.
     */
    public static function months(?Carbon $on = null): array
    {
        $cursor = self::start($on);
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'key'   => $cursor->format('Y-m'),
                'label' => $cursor->format('M'),
            ];
            $cursor = $cursor->copy()->addMonth();
        }

        return $months;
    }
}
