<?php

namespace App\Support;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;

/**
 * A school's figures for the KPI cards and charts of the school admin's Home
 * and Analytics (the Super Admin's dashboard look, 2026-11-04): its last
 * twelve months month by month — students and teachers added, fee collected —
 * and its students split by class for a donut.
 */
class SchoolMonthly
{
    /**
     * The last $months months, the oldest first and this month last: students
     * and teachers added (by the day their record was made) and fee collected
     * up to today (academic and transport from both payment tables, or what a
     * Custom Fee school took through it).
     *
     * @return array{labels: list<string>, students: list<int>, teachers: list<int>, fee: list<float>}
     */
    public static function forSchool(int $orgId, int $months = 12): array
    {
        $start  = now()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $from   = $start->toDateString();
        $keys   = [];
        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $month    = $start->copy()->addMonthsNoOverflow($i);
            $keys[]   = $month->format('Y-m');
            $labels[] = $month->format('M y');
        }

        $added = fn (string $model) => array_map('intval', self::byMonth(
            $model::where('organization_id', $orgId)
                ->where('created_at', '>=', $from)
                ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->groupBy('d')
                ->pluck('c', 'd')
                ->all(),
            $keys
        ));

        return [
            'labels'   => $labels,
            'students' => $added(StudentDetail::class),
            'teachers' => $added(TeacherDetail::class),
            'fee'      => array_map(fn ($v) => round($v, 2), self::byMonth(self::feeByDay($orgId, $from), $keys)),
        ];
    }

    /**
     * [Y-m-d => amount] collected from $from up to today: academic and
     * transport fee from both payment tables — or, for a school on Custom Fee,
     * what came in through it.
     */
    public static function feeByDay(int $orgId, string $from): array
    {
        $today = now()->toDateString();

        if (CustomFee::enabled($orgId)) {
            return CustomFee::collectedByDay($orgId, $from, $today);
        }

        $byDay = [];
        foreach ([
            FeePayment::where('organization_id', $orgId)->whereIn('fee_type', ['academic', 'transport']),
            TransportFeePayment::where('organization_id', $orgId),
        ] as $query) {
            $rows = $query->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $today)
                ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
                ->groupBy('d')
                ->pluck('total', 'd');

            foreach ($rows as $day => $total) {
                $byDay[(string) $day] = ($byDay[(string) $day] ?? 0) + (float) $total;
            }
        }

        return $byDay;
    }

    /**
     * The school's students by class for a donut: the four biggest classes,
     * then everyone else (smaller classes, and students not in a class yet)
     * as "Others". [['label', 'value', 'color'], …]
     */
    public static function studentsByClass(int $orgId): array
    {
        $colors = ['#7c3aed', '#111827', '#a78bfa', '#38bdf8'];

        $byClass = StudentDetail::where('organization_id', $orgId)
            ->whereNotNull('standard_id')
            ->selectRaw('standard_id, COUNT(*) as c')
            ->groupBy('standard_id')
            ->pluck('c', 'standard_id')
            ->map(fn ($c) => (int) $c)
            ->sortDesc();

        $names = Standard::where('organization_id', $orgId)->pluck('name', 'id');
        $total = StudentDetail::where('organization_id', $orgId)->count();

        $parts = [];
        foreach ($byClass->take(4) as $standardId => $count) {
            $parts[] = [
                'label' => (string) ($names[$standardId] ?? 'Class #' . $standardId),
                'value' => $count,
                'color' => $colors[count($parts)],
            ];
        }

        $rest = $total - array_sum(array_column($parts, 'value'));
        if ($rest > 0) {
            $parts[] = ['label' => 'Others', 'value' => $rest, 'color' => '#e5e7eb'];
        }

        return $parts;
    }

    /** A [Y-m-d => number] map added up into the given months (Y-m keys), in their order. */
    private static function byMonth(array $byDay, array $keys): array
    {
        $out = array_fill_keys($keys, 0.0);
        foreach ($byDay as $day => $value) {
            $key = substr((string) $day, 0, 7);
            if (array_key_exists($key, $out)) {
                $out[$key] += (float) $value;
            }
        }

        return array_values($out);
    }
}
