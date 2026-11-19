<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Schema;

/**
 * The name and logo a class's documents carry.
 *
 * A school can run under two names — say a CBSE Public School for Nursery–5
 * and a UP Board Inter College for 6–12. The Super Admin gives it a second
 * name and logo (Schools → the school → Custom Fee tab), and each class is
 * set to one of the two (Admin → Class → Wing). An ID card, admit card,
 * report card, fee / transport / custom-fee receipt, TC or certificate of a
 * student in a second-wing class then shows the second name and logo; the
 * address, phone, board numbers, bank and receipt numbers stay the school's.
 *
 * Documents that keep their own class (an admit card, report card or fee
 * payment saves standard_id) are named by that class, so a student promoted
 * from 5 to 6 keeps the old name on the old papers.
 *
 * A school with no second name, or a class left on wing 1, is unchanged.
 */
class SchoolBrand
{
    public const MAIN   = 1;
    public const SECOND = 2;

    /** Whether the school has a second name to give its classes. */
    public static function hasSecond(?Organization $org): bool
    {
        return $org !== null && trim((string) ($org->second_name ?? '')) !== '';
    }

    /**
     * The school as this class's documents name it: a copy with the second
     * name and logo when the class is on the second wing, else the school
     * itself. The copy is for printing only — never save it.
     *
     * @param  Standard|int|string|null  $standard  the class, or its id
     */
    public static function org(?Organization $org, $standard): ?Organization
    {
        if (!self::hasSecond($org) || self::wingOf($standard) !== self::SECOND) {
            return $org;
        }

        $copy = clone $org;
        $copy->setAttribute('name', trim((string) $org->second_name));
        if (trim((string) ($org->second_logo ?? '')) !== '') {
            $copy->setAttribute('logo', $org->second_logo);
        }

        return $copy;
    }

    /** The school name for this class's documents. */
    public static function name(?Organization $org, $standard): ?string
    {
        return self::org($org, $standard)?->name;
    }

    /** The school logo (as stored: a URL or a storage path) for this class's documents. */
    public static function logo(?Organization $org, $standard): ?string
    {
        return self::org($org, $standard)?->logo;
    }

    /** The two names a class can take, for the class form: [1 => own, 2 => second]. */
    public static function options(?Organization $org): array
    {
        if (!self::hasSecond($org)) {
            return [];
        }

        return [
            self::MAIN   => (string) $org->name,
            self::SECOND => trim((string) $org->second_name),
        ];
    }

    /** A class's wing: 1 (the school's own name) unless it is set to 2. */
    public static function wingOf($standard): int
    {
        if ($standard instanceof Standard) {
            if (array_key_exists('wing', $standard->getAttributes())) {
                return (int) $standard->wing === self::SECOND ? self::SECOND : self::MAIN;
            }
            $standard = $standard->getKey();
        }

        $id = (int) $standard;
        if ($id <= 0 || !self::column()) {
            return self::MAIN;
        }

        $cache = self::cache();
        if (!isset($cache[$id])) {
            $cache[$id] = (int) Standard::whereKey($id)->value('wing') === self::SECOND ? self::SECOND : self::MAIN;
        }

        return $cache[$id];
    }

    /** Whether standards.wing exists yet (a deploy migrates before it serves). */
    private static function column(): bool
    {
        $cache = self::cache();
        if (!isset($cache['column'])) {
            try {
                $cache['column'] = Schema::hasColumn('standards', 'wing');
            } catch (\Throwable) {
                $cache['column'] = false;
            }
        }

        return $cache['column'];
    }

    /**
     * Class id => wing, kept for one request or one queued job only (a scoped
     * binding is forgotten between jobs), so a bulk print asks once per class
     * and a class moved to the other wing is seen at once.
     */
    private static function cache(): \ArrayObject
    {
        $key = self::class . '.wings';
        if (!app()->bound($key)) {
            app()->scoped($key, fn () => new \ArrayObject());
        }

        return app($key);
    }
}
