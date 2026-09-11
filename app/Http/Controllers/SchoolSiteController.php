<?php

namespace App\Http\Controllers;

use App\Models\SchoolWebsite;
use Illuminate\Http\Response;

/**
 * Renders a school's public website (Kider template) from its SchoolWebsite
 * record. Invoked by the ServeSchoolSite middleware for requests arriving on
 * a school's custom domain.
 *
 * Pages come in four shapes (see SchoolWebsite::pageGroups): 'view' pages
 * have a blade of their own, while 'content', 'documents' and 'result' pages
 * all share one generic blade each and differ only in the data they carry —
 * that is what lets a school publish the whole CBSE page set from Website
 * Data without a new template file per page.
 */
class SchoolSiteController extends Controller
{
    /** Map a request path slug to a template view, honouring enabled pages. */
    public function render(SchoolWebsite $site, string $slug): Response
    {
        $slug = $slug ?: 'home';
        $type = SchoolWebsite::pageType($slug);

        // Unknown page or page turned off → 404.
        if (! $type || ! $site->isPageEnabled($slug)) {
            return response()->view('school-site.kider.not-found', $this->data($site, '404'), 404);
        }

        $data = $this->data($site, $slug);

        return match ($type) {
            'content' => response()->view('school-site.kider.content-page', $data + [
                'page' => $site->pageContent($slug),
            ]),
            'documents' => response()->view('school-site.kider.documents', $data + [
                'heading'   => SchoolWebsite::allPages()[$slug],
                'documents' => $site->pageDocuments($slug),
            ]),
            'result' => response()->view('school-site.kider.result', $data + [
                'results' => $site->resolvedContent()['results'] ?? [],
            ]),
            default => response()->view("school-site.kider.{$slug}", $data),
        };
    }

    /** Shared view data (content, theme, nav, current page). */
    private function data(SchoolWebsite $site, string $current): array
    {
        $enabled = $site->enabledPages();

        // The nav keeps pageGroups' order and drops anything switched off; a
        // group with nothing left in it disappears entirely.
        $nav = [];
        foreach (SchoolWebsite::pageGroups() as $group => $pages) {
            $items = [];
            foreach ($pages as $slug => [$label, $type]) {
                if (in_array($slug, $enabled, true)) {
                    $items[$slug] = $label;
                }
            }
            if ($items) {
                $nav[$group] = $items;
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
