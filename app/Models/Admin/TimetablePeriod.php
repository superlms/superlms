<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a school's day (Timetable → Periods): a period with its serial,
 * or the lunch break, from when to when.
 */
class TimetablePeriod extends Model
{
    public const PERIOD = 'period';
    public const LUNCH  = 'lunch';

    protected $fillable = [
        'organization_id',
        'type',
        'period_no',
        'start_time',
        'end_time',
    ];

    /** The school's periods in order, as [['no' => 1, 'start' => '09:00', 'end' => '09:45'], …]. */
    public static function periodsOf(int $orgId): array
    {
        return static::where('organization_id', $orgId)
            ->where('type', self::PERIOD)
            ->orderBy('period_no')
            ->get()
            ->map(fn ($p) => [
                'no'    => (int) $p->period_no,
                'start' => substr((string) $p->start_time, 0, 5),
                'end'   => substr((string) $p->end_time, 0, 5),
            ])
            ->values()
            ->all();
    }

    /** The school's lunch break as ['start' => '12:00', 'end' => '12:30'], or null when none is set. */
    public static function lunchOf(int $orgId): ?array
    {
        $lunch = static::where('organization_id', $orgId)->where('type', self::LUNCH)->first();

        return $lunch ? [
            'start' => substr((string) $lunch->start_time, 0, 5),
            'end'   => substr((string) $lunch->end_time, 0, 5),
        ] : null;
    }
}
