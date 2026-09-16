<?php

namespace App\Support;

use App\Models\Student\Standard;

/**
 * A class's display order is unique within its school, for the admin panel and
 * the mobile API alike. Saving a class at an order another class already holds
 * puts it there and moves that class — and the run of classes right behind it —
 * one place down, up to the first gap.
 */
class StandardOrder
{
    /**
     * What an order may be typed as: a whole number, leading zeros allowed —
     * "012" is order 12, the same as "12".
     */
    public const RULE = 'regex:/^\s*0*\d{1,6}\s*$/';

    /** A typed order as a number, or null when it was left blank. */
    public static function parse($value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    /** The order a class gets when none is given: after the last one. */
    public static function next(int $orgId): int
    {
        return (int) Standard::where('organization_id', $orgId)->max('order') + 1;
    }

    /**
     * Free $order for the class being saved ($exceptId when it already
     * exists): the class holding it moves to $order + 1, the one holding that
     * to $order + 2, and so on until a gap.
     */
    public static function makeRoom(int $orgId, int $order, ?int $exceptId = null): void
    {
        $behind = Standard::where('organization_id', $orgId)
            ->where('order', '>=', $order)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'order']);

        $taken = $order;
        foreach ($behind as $standard) {
            if ((int) $standard->order > $taken) {
                break;
            }
            $taken++;
            Standard::whereKey($standard->id)->update(['order' => $taken]);
        }
    }
}
