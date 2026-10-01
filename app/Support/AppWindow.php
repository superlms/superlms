<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;

/**
 * The window a request comes from: a window of an installed panel app, or the
 * browser.
 *
 * An installed app and the browser share one cookie jar, so on one sign-in
 * cookie whoever signed in to the app was signed in to every browser tab too,
 * and the other way round. They are kept apart by the address instead. Inside
 * an installed app every page lives under the app's own corner of the site —
 * its "mount", the app's scope followed by "~":
 *
 *     /9/~/9/student          the School Admin app of school 9
 *     /accounts/~/accounts/…  the Accounts app
 *     /~/super-admin/…        the Super Admin app (and the admin app installed
 *                             from the login screen)
 *
 * What follows the mount is the page's ordinary address, and that is all the
 * rest of the application ever sees: the mount is taken off before the request
 * is routed (App\Http\Middleware\UseAppWindow). A request under a mount — or
 * made from a page under one — uses the app's own session cookie
 * (App\Http\Middleware\StartSession), so the app and the browser each have
 * their own sign-in, and so does each installed app.
 *
 * And since every page of the app now sits inside the app's scope — its login
 * screen and another school's pages too — the app's window never shows the
 * browser's address bar over a page of the site.
 *
 * A request is the app's when, in this order:
 *   - its address is under a mount;
 *   - it says so in the X-SuperLMS-Window header (the page's own fetches);
 *   - it is for one of the apps' opening pages (their start_url);
 *   - it was made from a page of the app (the Referer) — a link, a redirect, a
 *     form, an image, a PDF in a frame.
 * Anything else is the browser's.
 */
class AppWindow
{
    public const HEADER = 'X-SuperLMS-Window';

    /** A mount: the app's scope, then "~". */
    private const MOUNT = '(?:/(?:\d+|accounts))?/~';

    /** The browser's session cookie name — config is changed per request. */
    private static ?string $baseCookie = null;

    /** The mount of the request being served — null in the browser. */
    private static ?string $mount = null;

    /** How it was told: 'path' | 'header' | 'launch' | 'referer' | null. */
    private static ?string $how = null;

    /** The request is the app's (and whose), or the browser's. */
    public static function enter(?string $mount, ?string $how = null): void
    {
        self::$mount = $mount !== null && self::isMount($mount) ? $mount : null;
        self::$how   = self::$mount ? ($how ?? 'path') : null;
    }

    public static function mount(): ?string
    {
        return self::$mount;
    }

    public static function isApp(): bool
    {
        return self::$mount !== null;
    }

    /** The request is for an app's opening page itself. */
    public static function atLaunch(): bool
    {
        return self::$how === 'launch';
    }

    public static function isMount(string $mount): bool
    {
        return (bool) preg_match('#^' . self::MOUNT . '$#', $mount);
    }

    /** [mount, the address under it] of a path under a mount, else null. */
    public static function split(string $path): ?array
    {
        if (!preg_match('#^(' . self::MOUNT . ')(/.*)?$#', $path, $m)) {
            return null;
        }

        return [$m[1], ($m[2] ?? '') !== '' ? $m[2] : '/'];
    }

    /**
     * The mount of the app an opening page belongs to. The paths are those of
     * admin.launch, accounts.launch and the pwa.* routes.
     */
    public static function launchMount(string $panel, $organization = null): string
    {
        return match (true) {
            $panel === 'accounts' => '/accounts/~',
            $panel === 'admin' && ctype_digit((string) $organization) && (int) $organization > 0
                => '/' . (int) $organization . '/~',
            default => '/~',
        };
    }

    /** The mount an address belongs to: under one, or an app's opening page. */
    private static function of(string $path): ?array
    {
        if ($split = self::split($path)) {
            return [$split[0], 'path'];
        }

        $path = rtrim($path, '/');

        return match (true) {
            (bool) preg_match('#^/(\d+)/launch$#', $path, $m) => [self::launchMount('admin', $m[1]), 'launch'],
            $path === '/accounts/launch', $path === '/app/accounts' => [self::launchMount('accounts'), 'launch'],
            $path === '/app/admin', $path === '/app/superadmin' => [self::launchMount('superadmin'), 'launch'],
            default => null,
        };
    }

    /** [mount, how it was told] for a request — [null, null] in the browser. */
    public static function detect(Request $request): array
    {
        $own = self::of($request->getPathInfo());
        if ($own && $own[1] === 'path') {
            return $own;
        }

        $header = (string) $request->headers->get(self::HEADER, '');
        if ($header !== '' && self::isMount($header)) {
            return [$header, 'header'];
        }

        if ($own) {
            return $own;
        }

        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            $from = parse_url($referer);
            if (is_array($from)
                && strcasecmp((string) ($from['host'] ?? ''), $request->getHost()) === 0
                && ($of = self::of((string) ($from['path'] ?? '/')))) {
                return [$of[0], 'referer'];
            }
        }

        return [null, null];
    }

    /** An address of the site, put under a mount (this request's, by default). */
    public static function mounted(string $url, ?string $mount = null): string
    {
        $mount ??= self::$mount;
        if (!$mount || !self::isMount($mount)) {
            return $url;
        }

        if (!preg_match('#^(https?://[^/?\#]+)?(/[^?\#]*)?(.*)$#i', $url, $m)) {
            return $url;
        }

        $path = ($m[2] ?? '') !== '' ? $m[2] : '/';

        return self::split($path) ? $url : $m[1] . $mount . $path . $m[3];
    }

    /** The browser's session cookie, or the app's own. */
    public static function sessionCookie(?string $mount = null): string
    {
        $name  = self::$baseCookie ??= (string) config('session.cookie');
        $mount = func_num_args() ? $mount : self::$mount;

        if (!$mount) {
            return $name;
        }

        $app = trim(substr($mount, 0, -2), '/');

        return $name . '_app' . ($app !== '' ? '_' . $app : '');
    }

    /**
     * What every signed-in page of an app's window holds on to in the browser
     * for as long as it is open (partials/panel-app-open): how the app's
     * opening page tells "still open" from "closed and opened again".
     */
    public static function lockName(string $mount): string
    {
        return 'superlms-app:' . $mount;
    }

    /**
     * Who is signed in to a panel on this device outside this window's own
     * session: in the browser, or in the Super Admin app. Read, never changed
     * — it only says who a code may be mailed to.
     */
    public static function signedInElsewhere(string $guard): ?User
    {
        $key  = Auth::guard($guard)->getName();
        $mine = self::sessionCookie();

        foreach ([self::sessionCookie(null), self::sessionCookie('/~')] as $cookie) {
            $id = $cookie !== $mine ? request()->cookies->get($cookie) : null;
            if (!is_string($id) || $id === '') {
                continue;
            }

            try {
                $store = session()->driver();
                if (!$store instanceof Store) {
                    return null;
                }

                // A copy with a handler of its own: the database handler
                // remembers whether the row it last read exists, and this
                // request's own session must not inherit that.
                $peek = clone $store;
                $peek->setHandler(clone $store->getHandler());
                $peek->flush();
                $peek->setId($id);
                if ($peek->getId() !== $id) {
                    continue;
                }
                $peek->start();

                $user = ($userId = $peek->get($key)) ? User::find($userId) : null;
                if ($user) {
                    return $user;
                }
            } catch (\Throwable $e) {
                // Nobody, then.
            }
        }

        return null;
    }
}
