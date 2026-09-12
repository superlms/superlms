<?php

namespace App\Support;

use App\Models\Admin\Transportation;
use Illuminate\Support\Facades\DB;

/**
 * What a student is charged for the bus in one academic year.
 *
 * The rule lives here rather than in a controller because both receipts need
 * it — the academic one prints transport as a line of the overall standing,
 * the transport one prints it as the whole story — and two copies of a billing
 * rule is how they end up disagreeing.
 */
class TransportBilling
{
    /** Academic year, April first. June is the free month unless the school says otherwise. */
    public const MONTHS = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

    /**
     * The student's route and what the year costs on it.
     *
     * @return array{0:?Transportation,1:float,2:int}  route, year total, billed months
     */
    public static function forStudent(int $orgId, int $studentId): array
    {
        $route = Transportation::where('organization_id', $orgId)
            ->whereHas('students', fn ($q) => $q->where('student_details.id', $studentId))
            ->orderByDesc('is_active')
            ->first();

        if (! $route) {
            return [null, 0.0, 0];
        }

        $billed = self::billedMonths($orgId, $route->id, $studentId);

        return [$route, round((float) $route->monthly_fee * $billed, 2), $billed];
    }

    /** How many months this student is billed for on this route. */
    public static function billedMonths(int $orgId, int $routeId, int $studentId): int
    {
        $pivot = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->where('transportation_id', $routeId)
            ->where('student_detail_id', $studentId)
            ->first();

        $raw = $pivot->billable_months ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $raw = (array) $raw;

        $billed = 0;
        foreach (self::MONTHS as $key) {
            // Unset means "billed", except June, which is free by default.
            $on = array_key_exists($key, $raw) ? (bool) $raw[$key] : ($key !== 'jun');
            if ($on) {
                $billed++;
            }
        }

        return $billed;
    }
}
