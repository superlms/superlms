<?php

namespace App\Http\Controllers;

use App\Models\SchoolWebsite;
use Illuminate\Http\Response;

/**
 * Renders a school's public website from its SchoolWebsite record, in the
 * template the Super Admin picked for it (SchoolWebsite::templates — each a
 * folder of blades under school-site/). Invoked by the ServeSchoolSite
 * middleware for requests arriving on a school's custom domain.
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
        $tpl  = $site->templateKey();
        $view = fn (string $name) => "school-site.{$tpl}.{$name}";

        // One uploaded disclosure document on a page of its own (doc/3, as the
        // Classic menu's Documents links them), when the template has such a
        // page and the school has Mandatory Disclosures on.
        if (preg_match('#^doc/(\d+)$#', $slug, $m)
            && view()->exists($view('document'))
            && $site->isPageEnabled('disclosures')) {
            $doc = $site->pageDocuments('disclosures')[(int) $m[1] - 1] ?? null;
            if ($doc && trim((string) ($doc['file'] ?? '')) !== '') {
                return response()->view($view('document'), $this->data($site, $slug) + ['document' => $doc]);
            }
        }

        $type = SchoolWebsite::pageType($slug);

        // Unknown page or page turned off → 404.
        if (! $type || ! $site->isPageEnabled($slug)) {
            return response()->view($view('not-found'), $this->data($site, '404'), 404);
        }

        $data = $this->data($site, $slug);

        return match ($type) {
            'content' => response()->view($view('content-page'), $data + [
                'page' => $site->pageContent($slug),
            ]),
            'documents' => response()->view($view('documents'), $data + [
                'heading'   => SchoolWebsite::allPages()[$slug],
                'documents' => $site->pageDocuments($slug),
            ]),
            'result' => response()->view($view('result'), $data + [
                'results' => $site->resolvedContent()['results'] ?? [],
            ]),
            default => response()->view($view($slug), $data),
        };
    }

    /** Shared view data (content, theme, nav, current page). */
    private function data(SchoolWebsite $site, string $current): array
    {
        $enabled = $site->enabledPages();

        $nav = $site->templateKey() === 'classic'
            ? $this->classicNav($site, $enabled)
            : $this->groupedNav($enabled);

        return [
            'site'    => $site,
            'c'       => $site->resolvedContent(),
            'theme'   => $site->resolvedTheme(),
            'nav'     => $nav,
            'current' => $current,
        ];
    }

    /**
     * The nav keeps pageGroups' order and drops anything switched off; a
     * group with nothing left in it disappears entirely.
     */
    private function groupedNav(array $enabled): array
    {
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

        return $nav;
    }

    /**
     * The Classic menu: classicNavGroups' groups, then a Documents group of
     * the disclosure documents the school has uploaded (each its own page,
     * doc/N) straight after Mandatory Disclosure. Pages switched on that no
     * group names go under More.
     */
    private function classicNav(SchoolWebsite $site, array $enabled): array
    {
        $labels = SchoolWebsite::allPages();
        $groups = SchoolWebsite::classicNavGroups();
        $placed = array_merge(...array_values($groups));

        $nav = [];
        foreach ($groups as $group => $slugs) {
            if ($group === 'More') {
                $slugs = array_merge($slugs, array_diff(array_keys($labels), $placed));
            }

            $items = [];
            foreach ($slugs as $slug) {
                if (in_array($slug, $enabled, true)) {
                    $items[$slug] = $labels[$slug];
                }
            }
            if ($items) {
                $nav[$group] = $items;
            }

            if ($group === 'Mandatory Disclosure' && in_array('disclosures', $enabled, true)) {
                $docs = [];
                foreach ($site->pageDocuments('disclosures') as $i => $doc) {
                    if (trim((string) ($doc['file'] ?? '')) !== '' && trim((string) ($doc['title'] ?? '')) !== '') {
                        $docs['doc/' . ($i + 1)] = $doc['title'];
                    }
                }
                if ($docs) {
                    $nav['Documents'] = $docs;
                }
            }
        }

        return $nav;
    }
}
