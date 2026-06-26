<?php

namespace App\Support;

use App\Models\Organization;

/**
 * Helper for per-school module (feature) gating.
 *
 * A "module" is a toggleable feature defined in config/modules.php. Route
 * names that do not belong to any module are CORE features and are always
 * allowed.
 */
class ModuleAccess
{
    /** Cached reverse map: route-name => module-key. */
    protected static ?array $linkMap = null;

    /** Build (and cache) the route-name => module-key lookup. */
    public static function linkMap(): array
    {
        if (self::$linkMap !== null) {
            return self::$linkMap;
        }

        $map = [];
        foreach (config('modules', []) as $key => $def) {
            foreach (($def['links'] ?? []) as $link) {
                $map[$link] = $key;
            }
        }

        return self::$linkMap = $map;
    }

    /** Module key a route/link belongs to, or null for core (always-on) routes. */
    public static function moduleForLink(?string $link): ?string
    {
        if (!$link) {
            return null;
        }

        return self::linkMap()[$link] ?? null;
    }

    /**
     * Whether a menu link is allowed for the given organization.
     * Core links (not mapped to a module) and missing tenant context
     * always return true so nothing is hidden unintentionally.
     */
    public static function allows(?Organization $org, ?string $link): bool
    {
        $key = self::moduleForLink($link);

        if ($key === null) {
            return true;
        }

        if (!$org) {
            return true;
        }

        return $org->hasModule($key);
    }

    /**
     * Filter a config/menu.php item array down to the modules the
     * organization is allowed to access.
     */
    public static function filterMenu(array $items, ?Organization $org): array
    {
        return array_values(array_filter(
            $items,
            fn($item) => self::allows($org, $item['link'] ?? null)
        ));
    }
}
