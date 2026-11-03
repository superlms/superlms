<?php

namespace App\Support;

/**
 * Small charts for the Super Admin's Home and Analytics cards, drawn as inline
 * SVG on the server — a KPI card's sparkline, a donut's rings — so they need
 * no script and survive every Livewire re-render as they are.
 */
class DashboardChart
{
    /**
     * A smooth line through the values, and the area under it, in a
     * $w × $h box (SVG path data). Flat or empty series sit on the middle.
     *
     * @param  array<int|float>  $values
     * @return array{line: string, area: string, last: array{0: float, 1: float}}
     */
    public static function sparkline(array $values, int $w = 120, int $h = 40): array
    {
        $values = array_values(array_map('floatval', $values));
        if (count($values) < 2) {
            $values = array_pad($values, 2, $values[0] ?? 0.0);
        }

        $min  = min($values);
        $max  = max($values);
        $span = $max - $min;
        $pad  = 3;
        $n    = count($values);

        $points = [];
        foreach ($values as $i => $v) {
            $x = $n > 1 ? $i * $w / ($n - 1) : 0;
            $y = $span > 0 ? $pad + (1 - ($v - $min) / $span) * ($h - 2 * $pad) : $h / 2;
            $points[] = [round($x, 2), round($y, 2)];
        }

        // Catmull-Rom through the points, as cubic Béziers: smooth, and it
        // passes through every value.
        $d = 'M' . $points[0][0] . ',' . $points[0][1];
        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $points[max(0, $i - 1)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($n - 1, $i + 2)];

            $c1 = [round($p1[0] + ($p2[0] - $p0[0]) / 6, 2), round(self::clamp($p1[1] + ($p2[1] - $p0[1]) / 6, 0, $h), 2)];
            $c2 = [round($p2[0] - ($p3[0] - $p1[0]) / 6, 2), round(self::clamp($p2[1] - ($p3[1] - $p1[1]) / 6, 0, $h), 2)];
            $d .= " C{$c1[0]},{$c1[1]} {$c2[0]},{$c2[1]} {$p2[0]},{$p2[1]}";
        }

        return [
            'line' => $d,
            'area' => $d . " L{$w},{$h} L0,{$h} Z",
            'last' => end($points),
        ];
    }

    /**
     * Change from the previous period to this one, in percent (one decimal):
     * ['pct' => 12.5, 'up' => true]. From nothing to something is +100%;
     * nothing to nothing is null (no trend shown).
     *
     * @return array{pct: float, up: bool}|null
     */
    public static function trend(float $now, float $before): ?array
    {
        if ($before == 0.0) {
            return $now > 0 ? ['pct' => 100.0, 'up' => true] : null;
        }

        $pct = round(($now - $before) / abs($before) * 100, 1);

        return ['pct' => abs($pct), 'up' => $pct >= 0];
    }

    /**
     * A donut's rings: for each value its stroke-dasharray / -dashoffset on a
     * circle whose circumference is 100 (r = 15.9155), and its share.
     *
     * @param  array<int|float>  $values
     * @return list<array{dash: string, offset: float, pct: float}>
     */
    public static function donut(array $values, float $gap = 0.6): array
    {
        $values = array_values(array_map(fn ($v) => max(0.0, (float) $v), $values));
        $total  = array_sum($values);
        $out    = [];
        $start  = 0.0;
        $shown  = count(array_filter($values, fn ($v) => $v > 0));

        foreach ($values as $v) {
            $share = $total > 0 ? $v / $total * 100 : 0.0;
            $len   = $shown > 1 ? max(0.0, $share - $gap) : $share;
            $out[] = [
                'dash'   => round($len, 3) . ' ' . round(100 - $len, 3),
                'offset' => round(25 - $start, 3), // start at 12 o'clock
                'pct'    => round($share, 1),
            ];
            $start += $share;
        }

        return $out;
    }

    /** "₹38.2L", "₹1.2Cr", "₹45,000" — money short enough for a card. */
    public static function inrShort(float $value): string
    {
        $abs = abs($value);

        return '₹' . match (true) {
            $abs >= 1e7 => rtrim(rtrim(number_format($value / 1e7, 2), '0'), '.') . 'Cr',
            $abs >= 1e5 => rtrim(rtrim(number_format($value / 1e5, 2), '0'), '.') . 'L',
            default     => number_format($value, 0),
        };
    }

    private static function clamp(float $v, float $lo, float $hi): float
    {
        return max($lo, min($hi, $v));
    }
}
