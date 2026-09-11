<?php

namespace App\Livewire\Concerns;

/**
 * How many months of the academic year a transport rider is billed for.
 *
 * `transportation_students.billable_months` is a JSON map of month keys to
 * booleans; a key that isn't there means "on", except June, which schools
 * skip by default. Shared by every screen that turns a route's monthly fee
 * into a year's transport bill.
 */
trait CountsBillableMonths
{
    /** Academic-year month keys, April first — June is off by default. */
    private const BILLABLE_MONTH_KEYS = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

    private function billableMonthsCount($raw): int
    {
        $months = is_string($raw) ? (json_decode($raw, true) ?: []) : (array) ($raw ?? []);

        $count = 0;
        foreach (self::BILLABLE_MONTH_KEYS as $key) {
            $on = array_key_exists($key, $months) ? (bool) $months[$key] : ($key !== 'jun');
            if ($on) {
                $count++;
            }
        }

        return $count;
    }
}
