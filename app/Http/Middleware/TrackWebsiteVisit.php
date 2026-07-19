<?php

namespace App\Http\Middleware;

use App\Models\WebsiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page visit for public marketing-website pages so the super-admin
 * Analytics screen can report traffic per page. Best-effort: it never breaks
 * the request if logging fails, and it ignores bots, assets and non-GET/HTML
 * responses.
 */
class TrackWebsiteVisit
{
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

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->shouldTrack($request, $response)) {
                $routeName = optional($request->route())->getName();
                $label = self::LABELS[$routeName]
                    ?? (Str::of($request->path())->trim('/')->headline()->value() ?: 'Home');

                WebsiteVisit::create([
                    'path'       => '/' . ltrim($request->path(), '/'),
                    'page'       => $label,
                    'visitor_id' => hash('sha256', $request->ip() . '|' . (string) $request->userAgent()),
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Never let analytics logging affect the visitor.
            logger()->warning('Website visit tracking failed: ' . $e->getMessage());
        }

        return $response;
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

        // Skip obvious bots/crawlers so counts reflect real people.
        $ua = strtolower((string) $request->userAgent());
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|preview|monitor|curl|wget|python-requests|headless/i', $ua)) {
            return false;
        }

        return true;
    }
}
