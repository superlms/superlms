<?php

namespace App\Services\Gemini;

use App\Http\Controllers\WebsiteController;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * What the website's "Ask anything" assistant knows: the text of superlms.in
 * itself, read from the very pages visitors see, so it can never drift from
 * the site.
 *
 * Every public page of the new design (components/site) is rendered and its
 * <main> turned into plain text — the animated product mockups (.mk) are
 * dropped, since their names and numbers are illustrations, not facts. The
 * admin-managed parts are read from the database: FAQs, blog posts, open
 * jobs (on the careers page), the legal pages' sections and the live counts
 * the site shows. Built once and kept for `website.ask.knowledge_minutes`.
 */
class WebsiteKnowledge
{
    public const CACHE_KEY = 'website_ask_knowledge_v1';

    /** view (components/site/…) => [page title, path] */
    private const PAGES = [
        'index'            => ['Home', '/'],
        'about'            => ['About Us', '/web/about'],
        'features'         => ['Features (all modules)', '/web/features'],
        'pricing'          => ['Pricing', '/web/pricing'],
        'why-us'           => ['Why Us', '/web/why-us'],
        'services'         => ['Services', '/web/services'],
        'contact'          => ['Contact Us', '/web/contact'],
        'demo'             => ['Request a Free Demo', '/web/demo'],
        'careers'          => ['Careers', '/web/careers'],
        'become-executive' => ['Become an Executive (partner programme)', '/web/become-an-executive'],
        'refund-policy'    => ['Refund and Cancellation Policy', '/web/refund-policy'],
    ];

    /** Pages whose body comes from the database, with the controller method that serves it. */
    private const LEGAL = [
        'privacy-policy'   => ['Privacy Policy', '/web/privacy', 'privacyPolicy'],
        'terms-conditions' => ['Terms & Conditions', '/web/terms-conditions', 'termsConditions'],
        'terms-of-use'     => ['Terms of Use', '/web/terms-of-use', 'termsOfUse'],
    ];

    public function text(): string
    {
        $minutes = max(1, (int) config('website.ask.knowledge_minutes', 360));

        return Cache::remember(self::CACHE_KEY, now()->addMinutes($minutes), fn () => $this->build());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function build(): string
    {
        $base  = rtrim((string) config('app.url', 'https://superlms.in'), '/');
        $parts = [];

        $parts[] = $this->links($base);
        $parts[] = $this->liveNumbers();

        foreach (self::PAGES as $view => [$title, $path]) {
            $parts[] = $this->section($title, $path, $this->pageText($view));
        }

        $parts[] = $this->faqs();
        $parts[] = $this->blogs();

        foreach (self::LEGAL as $view => [$title, $path, $method]) {
            $parts[] = $this->section($title, $path, $this->legalText($view, $method));
        }

        $parts[] = $this->section('Footer (company & contact)', '', $this->htmlToText(
            view('components.site.partials.footer', ['logo' => ''])->render(), false
        ));

        return trim(implode("\n\n", array_filter($parts)));
    }

    // ─── Pieces ───────────────────────────────────────────────────────────────

    private function links(string $base): string
    {
        $lines = ['# SITE MAP & USEFUL LINKS', 'Website: ' . $base];
        foreach (self::PAGES + array_map(fn ($l) => [$l[0], $l[1]], self::LEGAL) as [$title, $path]) {
            $lines[] = '- ' . $title . ': ' . $path;
        }
        $lines[] = '- Blogs: /web/blogs';
        $lines[] = '- FAQs: /web/faqs';
        $lines[] = '- SuperLMS Android app (Google Play): https://play.google.com/store/apps/details?id=com.edyoneapp';
        $lines[] = '- WhatsApp chat with the team: https://wa.me/919084748563';
        $lines[] = '- Phone / WhatsApp: +91 9084748563 · Email: support@superlms.in';

        return implode("\n", $lines);
    }

    private function liveNumbers(): string
    {
        try {
            $data = app(WebsiteController::class)->stats()->getData(true)['data'] ?? [];
        } catch (\Throwable $e) {
            return '';
        }

        return implode("\n", [
            '# LIVE NUMBERS (as shown on the site today, ' . now()->format('d M Y') . ')',
            '- Schools on SUPERLMS: ' . (int) ($data['schools'] ?? 0),
            '- Students on SUPERLMS: ' . (int) ($data['students'] ?? 0),
            '- Teachers on SUPERLMS: ' . (int) ($data['teachers'] ?? 0),
            '- Average rating: ' . ($data['rating'] ?? '4.9') . ' / 5',
        ]);
    }

    private function pageText(string $view): string
    {
        try {
            $data = [];
            if (in_array($view, ['why-us', 'services', 'careers', 'become-executive'], true)) {
                $slug = $view === 'become-executive' ? 'become-executive' : $view;
                $data['page'] = WebsitePage::where('slug', $slug)->first();
            }

            return $this->htmlToText(view('components.site.' . $view, $data)->render());
        } catch (\Throwable $e) {
            Log::warning('website ask: page not read', ['view' => $view, 'error' => $e->getMessage()]);

            return '';
        }
    }

    /** The legal page's sections from the database, or the page's built-in text when there are none. */
    private function legalText(string $view, string $method): string
    {
        try {
            $data = app(WebsiteController::class)->{$method}()->getData(true)['data'] ?? null;
        } catch (\Throwable $e) {
            $data = null;
        }

        if (!empty($data['sections'])) {
            $lines = [];
            if (!empty($data['last_updated'])) {
                $lines[] = 'Last updated: ' . $data['last_updated'];
            }
            foreach ($data['sections'] as $i => $s) {
                $lines[] = '### ' . ($i + 1) . '. ' . trim((string) ($s['head'] ?? ''));
                $lines[] = $this->clip(trim((string) ($s['desc'] ?? '')), 1500);
            }

            return implode("\n", $lines);
        }

        return $this->pageText($view);
    }

    private function faqs(): string
    {
        try {
            $faqs = Faq::orderBy('category')->orderBy('id')->get();
        } catch (\Throwable $e) {
            return '';
        }
        if ($faqs->isEmpty()) {
            return '';
        }

        $lines = ['# FAQS (page /web/faqs)'];
        foreach ($faqs as $f) {
            $lines[] = 'Q: ' . trim((string) $f->question) . ($f->category ? ' [' . $f->category . ']' : '');
            $lines[] = 'A: ' . $this->clip(trim((string) $f->answer), 1200);
        }

        return implode("\n", $lines);
    }

    private function blogs(): string
    {
        try {
            $blogs = Blog::latest()->take(25)->get();
        } catch (\Throwable $e) {
            return '';
        }
        if ($blogs->isEmpty()) {
            return '';
        }

        $lines = ['# BLOG ARTICLES (page /web/blogs)'];
        foreach ($blogs as $b) {
            $lines[] = '### ' . $b->title . ' — /web/blogs/' . $b->slug;
            if ($b->category || $b->heading) {
                $lines[] = trim(($b->category ? $b->category . '. ' : '') . (string) $b->heading);
            }
            $lines[] = $this->clip(implode(' ', array_slice((array) $b->body_paragraphs, 0, 3)), 900);
        }

        return implode("\n", $lines);
    }

    private function section(string $title, string $path, string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        return '# PAGE: ' . $title . ($path !== '' ? ' (' . $path . ')' : '') . "\n" . $body;
    }

    private function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max)) . '…' : $text;
    }

    // ─── HTML → text ──────────────────────────────────────────────────────────

    /**
     * The readable text of a page: headings as "## …", list items as "- …",
     * every other block on its own line. Mockups, scripts, icons, forms and
     * buttons are left out; repeated lines are kept once.
     */
    public function htmlToText(string $html, bool $mainOnly = true): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xp   = new \DOMXPath($dom);
        $root = $mainOnly ? $xp->query('//main')->item(0) : $dom->documentElement;
        if (!$root) {
            return '';
        }

        // Drop what is not content.
        $drop = $xp->query(
            './/script | .//style | .//svg | .//form | .//button | .//noscript | .//template'
            . ' | .//*[contains(concat(" ", normalize-space(@class), " "), " mk ")]'
            . ' | .//*[contains(concat(" ", normalize-space(@class), " "), " btn ")]'
            . ' | .//*[contains(concat(" ", normalize-space(@class), " "), " link-arrow ")]'
            . ' | .//*[contains(concat(" ", normalize-space(@class), " "), " seg ")]',
            $root
        );
        foreach (iterator_to_array($drop) as $n) {
            $n->parentNode?->removeChild($n);
        }

        $out = [];
        $this->walk($root, $out);

        $seen  = [];
        $lines = [];
        foreach ($out as $line) {
            $key = mb_strtolower($line);
            if ($line === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $lines[]    = $line;
        }

        return implode("\n", $lines);
    }

    /** @param array<int,string> $out */
    private function walk(\DOMNode $node, array &$out): void
    {
        foreach ($node->childNodes as $c) {
            if (!$c instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($c->tagName);

            if (preg_match('/^h([1-6])$/', $tag, $m)) {
                $t = $this->inline($c);
                if ($t !== '') {
                    $out[] = str_repeat('#', min(4, (int) $m[1] + 1)) . ' ' . $t;
                }
                continue;
            }

            if ($tag === 'li') {
                $t = $this->inline($c);
                if ($t !== '') {
                    $out[] = '- ' . $t;
                }
                continue;
            }

            if (in_array($tag, ['p', 'summary', 'tr', 'dt', 'dd', 'blockquote'], true) || $this->hasOwnText($c)) {
                $t = $this->inline($c);
                if ($t !== '') {
                    $out[] = $t;
                }
                continue;
            }

            $this->walk($c, $out);
        }
    }

    private function hasOwnText(\DOMElement $el): bool
    {
        foreach ($el->childNodes as $c) {
            if ($c instanceof \DOMText && trim($c->wholeText) !== '') {
                return true;
            }
        }

        return false;
    }

    private function inline(\DOMElement $el): string
    {
        $text = html_entity_decode($el->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
