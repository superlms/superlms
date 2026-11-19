<?php

namespace App\Http\Middleware;

use App\Models\WebsiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page visit for public marketing-website pages so the super-admin
 * Analytics screen can report traffic per page. Best-effort: it never breaks
 * the request if logging fails.
 *
 * Since 2026-11-19 (user's ask: "count the visitors exactly, and only the
 * people who come to superlms.in itself, not to the login pages") a visit is
 * counted only when it is a person reading the website:
 *
 *  - on superlms.in itself (www. too) — not the server's IP or another name;
 *  - a real page load: GET, 200, HTML, from a browser (bots, link previews,
 *    monitors and prefetches are left out — a browser always says which
 *    language it reads);
 *  - nobody signed in to a panel (super admin, school admin, accounts) and a
 *    browser that has never opened a panel's login page: those are the
 *    platform's own users on their way to log in (MarkPanelBrowser marks the
 *    browser, and drops what it was counted for on its way there);
 *  - once per page per person every 30 minutes, so a reload or going back
 *    and forth is not a second visit.
 *
 * A person is a first-party cookie (VISITOR_COOKIE, two years), so the same
 * phone on a new network is still one person; a browser that keeps no cookies
 * falls back to its IP and browser.
 */
class TrackWebsiteVisit
{
    /** The browser's own visitor id (32 hex characters). */
    public const VISITOR_COOKIE = 'slms_vid';

    /** A page seen again by the same person within this many minutes is not a new visit. */
    public const REPEAT_MINUTES = 30;

    /** Friendly page labels keyed by route name. */
    private const LABELS = [
        'website.home'              => 'Home',
        'website.about'             => 'About',
        'website.features'          => 'Features',
        'website.pricing'           => 'Pricing',
        'website.contact'           => 'Contact',
        'website.privacy'           => 'Privacy Policy',
        'website.refund-policy'     => 'Refund Policy',
        'website.terms-conditions'  => 'Terms & Conditions',
        'website.terms-of-use'      => 'Terms of Use',
        'website.demo'              => 'Demo',
        'website.why-us'            => 'Why Us',
        'website.services'          => 'Services',
        'website.careers'           => 'Careers',
        'website.become-executive'  => 'Become an Executive',
        'website.blogs'             => 'Blogs',
        'website.blog.detail'       => 'Blog Article',
        'website.faqs'              => 'FAQs',
    ];

    /** Crawlers, link previews, monitors and scripts — not people. */
    private const BOTS = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|facebot|preview|monitor|uptime|pingdom|statuscake|'
        . 'lighthouse|pagespeed|gtmetrix|headless|phantom|selenium|puppeteer|playwright|curl|wget|python|httpclient|okhttp|'
        . 'go-http|java\/|axios|node-fetch|undici|scrapy|ahrefs|semrush|mj12|dotbot|petal|bytespider|gptbot|claude|'
        . 'anthropic|perplexity|amazonbot|applebot|yandex|baidu|duckduck|whatsapp|telegram|slack|discord|skype|'
        . 'linkedin|twitter|pinterest|embedly|quora|ia_archiver|archive\.org/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->shouldTrack($request, $response)) {
                [$cookieId, $isNew] = $this->visitorCookie($request);
                $visitorId = self::visitorIdFor($request, $cookieId);
                $path      = '/' . ltrim($request->path(), '/');

                if ($isNew) {
                    $response->headers->setCookie(Cookie::create(
                        self::VISITOR_COOKIE, $cookieId, now()->addYears(2), '/', null,
                        $request->isSecure(), true, false, Cookie::SAMESITE_LAX
                    ));
                }

                $seenJustNow = !$isNew && WebsiteVisit::where('visitor_id', $visitorId)
                    ->where('path', $path)
                    ->where('created_at', '>=', now()->subMinutes(self::REPEAT_MINUTES))
                    ->exists();

                if (!$seenJustNow) {
                    $routeName = optional($request->route())->getName();
                    $label = self::LABELS[$routeName]
                        ?? (Str::of($request->path())->trim('/')->headline()->value() ?: 'Home');

                    WebsiteVisit::create([
                        'path'       => $path,
                        'page'       => $label,
                        'visitor_id' => $visitorId,
                        'created_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Never let analytics logging affect the visitor.
            logger()->warning('Website visit tracking failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * The id a visit is stored under: the browser's own cookie when it has
     * one, else (a browser that keeps no cookies) its IP and browser.
     */
    public static function visitorIdFor(Request $request, ?string $cookieId = null): string
    {
        $cookieId ??= self::cookieOf($request);

        return $cookieId
            ? hash('sha256', 'c|' . $cookieId)
            : hash('sha256', $request->ip() . '|' . (string) $request->userAgent());
    }

    /** The visitor cookie the browser sent, if it is one of ours. */
    public static function cookieOf(Request $request): ?string
    {
        $id = (string) $request->cookies->get(self::VISITOR_COOKIE, '');

        return preg_match('/^[a-f0-9]{32}$/', $id) ? $id : null;
    }

    /** [cookie id, whether it is new to this browser]. */
    private function visitorCookie(Request $request): array
    {
        $id = self::cookieOf($request);

        return $id ? [$id, false] : [bin2hex(random_bytes(16)), true];
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (!$request->isMethod('GET') || $request->ajax() || $request->hasHeader('X-Livewire')) {
            return false;
        }

        // Only successful HTML page loads.
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        $type = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($type !== '' && !str_contains($type, 'text/html')) {
            return false;
        }

        // superlms.in itself — not the server's IP or any other name.
        if (!$this->isSiteHost($request->getHost())) {
            return false;
        }

        // The browser fetching a page ahead of a click (link prefetch /
        // prerender) — nobody has opened it.
        $purpose = strtolower(implode(' ', array_filter([
            $request->header('Sec-Purpose'), $request->header('Purpose'), $request->header('X-Moz'), $request->header('X-Purpose'),
        ])));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'preview') || str_contains($purpose, 'prerender')) {
            return false;
        }

        // People, not bots: a browser's name, and a browser always says which
        // language it reads (crawlers and scripts mostly do not).
        $ua = (string) $request->userAgent();
        if ($ua === '' || !str_contains($ua, 'Mozilla/') || preg_match(self::BOTS, $ua)) {
            return false;
        }
        if (trim((string) $request->header('Accept-Language')) === '') {
            return false;
        }

        // The platform's own users: signed in to any panel, or a browser that
        // has opened a panel's login page (MarkPanelBrowser).
        if ($request->cookies->has(MarkPanelBrowser::COOKIE)) {
            return false;
        }
        foreach ((array) config('auth.guards', []) as $guard => $conf) {
            if (($conf['driver'] ?? null) === 'session' && Auth::guard($guard)->check()) {
                return false;
            }
        }

        return true;
    }

    /** superlms.in (and www.) — the site's own name from APP_URL, and superlms.in itself. */
    private function isSiteHost(string $host): bool
    {
        $strip = fn (string $h) => strtolower((string) preg_replace('/^www\./i', '', $h));
        $host  = $strip($host);
        $own   = $strip((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== '' && ($host === $own || $host === 'superlms.in');
    }
}
