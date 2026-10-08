<?php

namespace App\Support;

use App\Models\User;

/**
 * The circle a photo shows in the lists — set with Profile on the large photo
 * (the web panel's Students and Teachers lists, the class teacher's app). The
 * photo is not cut: users.photo_circle keeps where the circle sits on it and
 * how big — x, y, w, h, fractions (0–1) of the upright photo's width and
 * height, a square in pixels — with a mark of the photo it was set on. A new or
 * cropped photo has another mark, so its lists show the top of it again (as
 * with none set) until a circle is set on it.
 */
class PhotoCircle
{
    /** The photo's mark: the start of the SHA-1 of its address. */
    public static function mark(?string $image): string
    {
        return substr(sha1((string) $image), 0, 12);
    }

    /** The user's circle as [x, y, w, h] — or null: none set, or set on an earlier photo. */
    public static function of(?User $user): ?array
    {
        if (!$user?->image) {
            return null;
        }
        $saved = json_decode((string) $user->getAttribute('photo_circle'), true);
        if (!is_array($saved) || ($saved['v'] ?? null) !== self::mark($user->image)) {
            return null;
        }

        return self::clean([$saved['x'] ?? null, $saved['y'] ?? null, $saved['w'] ?? null, $saved['h'] ?? null]);
    }

    /** The same, keyed — for the app and the page's scripts. */
    public static function keyed(?array $rect): ?array
    {
        return $rect ? array_combine(['x', 'y', 'w', 'h'], $rect) : null;
    }

    /** The circle sent as x, y, w, h (or circle_x … circle_h), kept inside the photo — or null. */
    public static function fromInput(array $in): ?array
    {
        $get = fn ($k) => $in[$k] ?? $in['circle_' . $k] ?? null;

        return self::clean([$get('x'), $get('y'), $get('w'), $get('h')]);
    }

    /** The circle saved for the user's photo as it is now. */
    public static function store(User $user, array $rect): void
    {
        [$x, $y, $w, $h] = $rect;
        $user->setAttribute('photo_circle', json_encode([
            'v' => self::mark($user->image),
            'x' => round($x, 6), 'y' => round($y, 6), 'w' => round($w, 6), 'h' => round($h, 6),
        ]));
        $user->save();
    }

    /**
     * The photo laid in a round box so the circle fills it (the Teachers list,
     * which draws the photo itself): the <img>'s style, the box being square.
     */
    public static function imgStyle(array $rect): string
    {
        [$x, $y, $w, $h] = $rect;
        $pct = fn (float $v) => rtrim(rtrim(number_format($v * 100, 4, '.', ''), '0'), '.') . '%';

        return 'position:absolute;max-width:none;width:' . $pct(1 / $w) . ';height:' . $pct(1 / $h)
            . ';left:-' . $pct($x / $w) . ';top:-' . $pct($y / $h);
    }

    private static function clean(array $v): ?array
    {
        foreach ($v as $n) {
            if (!is_numeric($n)) {
                return null;
            }
        }
        [$x, $y, $w, $h] = array_map('floatval', $v);
        $x = max(0.0, min(1.0, $x));
        $y = max(0.0, min(1.0, $y));
        $w = min($w, 1.0 - $x);
        $h = min($h, 1.0 - $y);

        return ($w >= 0.01 && $h >= 0.01) ? [$x, $y, $w, $h] : null;
    }
}
