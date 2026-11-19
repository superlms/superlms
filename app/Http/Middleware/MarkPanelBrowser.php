<?php

namespace App\Http\Middleware;

use App\Models\WebsiteVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * A browser that opens a panel — the Super Admin's, a school admin's or the
 * accounts' login page, or any page of those panels — belongs to one of the
 * platform's own users, not to someone reading the website. It is marked with
 * a long-lived cookie, and TrackWebsiteVisit leaves it out of the website's
 * visitors from then on (user's ask, 2026-11-19: count only the people who
 * come to superlms.in, not those going to the login pages).
 *
 * The first time, what that browser was counted for on its way here (the
 * home page and the like, in the last couple of hours) is taken back too.
 */
class MarkPanelBrowser
{
    public const COOKIE = 'slms_panel';

    /** Visits this recent, by a browser that then opens a login page, were the way there. */
    public const LOOKBACK_HOURS = 2;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($request->isMethod('GET')
                && !$request->cookies->has(self::COOKIE)
                && $response->getStatusCode() < 400
                && $this->isPanelPage($request)) {
                $response->headers->setCookie(Cookie::create(
                    self::COOKIE, '1', now()->addYears(5), '/', null,
                    $request->isSecure(), true, false, Cookie::SAMESITE_LAX
                ));

                // Only a browser we know by its own cookie: one that keeps no
                // cookies shares its IP-and-browser id with other people.
                if (TrackWebsiteVisit::cookieOf($request)) {
                    WebsiteVisit::where('visitor_id', TrackWebsiteVisit::visitorIdFor($request))
                        ->where('created_at', '>=', now()->subHours(self::LOOKBACK_HOURS))
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            logger()->warning('Marking a panel browser failed: ' . $e->getMessage());
        }

        return $response;
    }

    /** A panel's login screen or one of its pages (the installed apps' too). */
    private function isPanelPage(Request $request): bool
    {
        return $request->routeIs('super-admin.*', 'admin.*', 'accounts.*', 'pwa.*', 'reset.password');
    }
}
