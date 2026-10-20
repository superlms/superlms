<?php

namespace App\Http\Controllers;

use App\Models\SchoolWebsite;
use App\Support\SchoolSiteData;
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
    /** Old page addresses that now open another page: History is Values & Goals. */
    private const MOVED = ['history' => 'values-goals'];

    /** Map a request path slug to a template view, honouring enabled pages. */
    public function render(SchoolWebsite $site, string $slug): Response
    {
        $slug = self::MOVED[$slug] ?? ($slug ?: 'home');
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
        // A page the template lays out itself (Fee Structure, Rules & Regulations)
        // has a blade named after it; the rest of its kind share one.
        $own = fn (string $shared) => view()->exists($view($slug)) ? $view($slug) : $view($shared);

        return match ($type) {
            'content' => response()->view($own('content-page'), $data + [
                'page' => $site->pageContent($slug),
            ]),
            'documents' => response()->view($own('documents'), $data + [
                'heading'   => SchoolWebsite::allPages()[$slug],
                'documents' => $site->pageDocuments($slug),
            ]),
            'result' => response()->view($view('result'), $data + [
                'results' => $site->resolvedContent()['results'] ?? [],
            ]),
            default => response()->view($view($slug), $data),
        };
    }

    /**
     * Shared view data (content, theme, nav, current page), and the school's
     * own records the Online Admission pages read — classes, fees, rules —
     * read only by a page that shows them.
     */
    private function data(SchoolWebsite $site, string $current): array
    {
        return [
            'site'    => $site,
            'c'       => $site->resolvedContent(),
            'theme'   => $site->resolvedTheme(),
            'nav'     => $this->nav($site),
            'current' => $current,
            'lms'     => new SchoolSiteData($site->organization_id),
        ];
    }

    /**
     * The menu: the template's groups (SchoolWebsite::menuGroups, the same
     * list the Super Admin ticks pages in) with the pages switched off taken
     * out — a group with nothing left disappears. Classic adds a Documents
     * group of the disclosures the school has uploaded (each its own page,
     * doc/N) straight after Mandatory Disclosure.
     */
    private function nav(SchoolWebsite $site): array
    {
        $enabled = $site->enabledPages();
        $classic = $site->templateKey() === 'classic';

        $nav = [];
        foreach (SchoolWebsite::menuGroups($site->templateKey()) as $group => $pages) {
            $items = array_intersect_key($pages, array_flip($enabled));
            if ($items) {
                $nav[$group] = $items;
            }

            if ($classic && $group === 'Mandatory Disclosure' && in_array('disclosures', $enabled, true)) {
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
