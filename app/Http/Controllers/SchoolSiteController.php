<?php

namespace App\Http\Controllers;

use App\Models\SchoolWebsite;
use Illuminate\Http\Response;

/**
 * Renders a school's public website (Kider template) from its SchoolWebsite
 * record. Invoked by the ServeSchoolSite middleware for requests arriving on
 * a school's custom domain.
 */
class SchoolSiteController extends Controller
{
    /** Map a request path slug to a template view, honouring enabled pages. */
    public function render(SchoolWebsite $site, string $slug): Response
    {
        $slug  = $slug ?: 'home';
        $pages = SchoolWebsite::allPages();

        // Unknown page or page turned off → 404.
        if (! array_key_exists($slug, $pages) || ! $site->isPageEnabled($slug)) {
            return response()->view('school-site.kider.not-found', $this->data($site, '404'), 404);
        }

        return response()->view("school-site.kider.{$slug}", $this->data($site, $slug));
    }

    /** Shared view data (content, theme, nav, current page). */
    private function data(SchoolWebsite $site, string $current): array
    {
        $allPages = SchoolWebsite::allPages();

        $nav = [];
        foreach ($site->enabledPages() as $slug) {
            if (isset($allPages[$slug])) {
                $nav[$slug] = $allPages[$slug];
            }
        }

        return [
            'site'    => $site,
            'c'       => $site->resolvedContent(),
            'theme'   => $site->resolvedTheme(),
            'nav'     => $nav,
            'current' => $current,
        ];
    }
}
