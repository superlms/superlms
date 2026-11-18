<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * Picks the look of the public marketing website.
 *
 * Two complete sets of views exist side by side:
 *   new     → resources/views/components/site/<page>
 *   classic → resources/views/components/website/<page>  (the earlier design)
 *
 * Order of precedence:
 *   1. ?design=new|classic on the URL — a preview for that browser only,
 *      remembered in a cookie for 30 days (?design=default forgets it);
 *   2. the super admin's choice (Profile → Platform → Website design,
 *      platform_settings.website_design);
 *   3. config('website.design') — env WEBSITE_DESIGN, default "new".
 */
class WebsiteDesign
{
    public const NEW = 'new';

    public const CLASSIC = 'classic';

    public const SETTING_KEY = 'website_design';

    private const COOKIE = 'site_design';

    /** The design the platform serves to everyone (ignores a browser's preview). */
    public static function platformDefault(): string
    {
        $saved = null;
        try {
            $saved = Platform::get(self::SETTING_KEY);
        } catch (\Throwable) {
            // No settings table (fresh install / tests): fall back to config.
        }

        return self::valid($saved) ?? self::valid(config('website.design')) ?? self::NEW;
    }

    /** The design this request is served in. */
    public static function current(): string
    {
        $request = request();
        $asked = $request->query('design');

        if ($asked === 'default') {
            Cookie::queue(Cookie::forget(self::COOKIE));

            return self::platformDefault();
        }

        if ($pick = self::valid($asked)) {
            Cookie::queue(self::COOKIE, $pick, 60 * 24 * 30);

            return $pick;
        }

        return self::valid($request->cookie(self::COOKIE)) ?? self::platformDefault();
    }

    /** The view for a website page ("index", "about", …) in this request's design. */
    public static function view(string $page): string
    {
        $folder = self::current() === self::CLASSIC ? 'components.website.' : 'components.site.';

        return $folder . $page;
    }

    private static function valid(mixed $value): ?string
    {
        return in_array($value, [self::NEW, self::CLASSIC], true) ? $value : null;
    }
}
