<?php

namespace App\Support;

use App\Models\SchoolWebsite;

/**
 * What a school fills in for each page of its website, as Website Data shows
 * it: a page is a list of blocks — a line of text, a paragraph, a photo,
 * photos, a file, a list of rows, the map — each naming the place in
 * SchoolWebsite::content that the site's page reads it from. A page's editor
 * is built from this list alone, so its blocks are what that page shows.
 *
 * A block: id (unique on its page), type, label, and as its type needs —
 *   path      where in the content it is kept (dot notation)
 *   help      a line under the label
 *   rows      a paragraph's height
 *   required  must not be left empty
 *   default   shown while nothing is kept (a page's own name as its heading)
 *   fallback  a path read while nothing is kept at path
 *   fields    a list's columns: field => [type, label, placeholder?]
 *   add       a list's add button
 *   bulk      a list's photo column, filled a row a file from several files
 *   seed      a list's starting rows while nothing is kept
 *   filled    a list's column that makes a row count as filled
 *   keep      a list's column a row is kept by (else: any column filled in)
 *   percent   [part, whole] columns of a list, shown as a percentage
 *   to        a link's page
 *   source    a live block's records: classes, fees or rules (LIVE)
 *   counts    false: not counted when telling a page is filled
 *   templates only on sites of these templates
 *
 * Types: text, textarea, image, images (photos, kept one link a line), file
 * (a PDF or photo, or a pasted link), list, location (the map: address, pin
 * or Google Maps embed), link (a section filled on another page), live (what
 * the site reads from the school's own records — nothing to fill in here),
 * note and heading (words only).
 */
class WebsitePages
{
    /** The details every page shows (name, logo, contact), listed above the pages. */
    public const DETAILS = 'details';

    /** Content pages that are a message from one person, their photo beside it. */
    public const DESKS = ['chairman', 'founder', 'co-founder', 'managing-director', 'executive-director', 'director', 'manager-desk', 'principal-desk'];

    /**
     * What the site's pages read live from the school's records — the Online
     * Admission pages, Transport and the Academic Calendar
     * (App\Support\SchoolSiteData): source => [label, where it is kept, the
     * admin route it is set up on].
     */
    public const LIVE = [
        'classes'   => ['Classes, sections & subjects', 'Standard', 'admin.standard'],
        'fees'      => ['Fee structure', 'Fee Structure', 'admin.fee-structure'],
        'rules'     => ['Rules & Regulations', 'Rules & Regulations', 'admin.rules-and-regulation'],
        'transport' => ['Transport routes', 'Transport', 'admin.transport'],
        'events'    => ["This session's events", 'Calendar', 'admin.calender'],
    ];

    /** The group School Details sits in, above the site's menus. */
    public const DETAILS_GROUP = 'School';

    /** A page's label, a line on what it is, and its blocks; null for no such page. */
    public static function page(string $slug, string $template): ?array
    {
        if ($slug === self::DETAILS) {
            return [
                'label'  => 'School Details',
                'group'  => self::DETAILS_GROUP,
                'intro'  => 'Shown on every page of your website — at the top, in the footer and on Contact Us.',
                'blocks' => self::forTemplate(self::details(), $template),
            ];
        }

        $type = SchoolWebsite::pageType($slug);
        if (! $type) {
            return null;
        }
        $label = SchoolWebsite::allPages()[$slug];

        [$intro, $blocks] = match ($type) {
            'content'   => self::contentPage($slug, $label),
            'documents' => self::documentPage($slug),
            'result'    => self::resultPage(),
            default     => self::viewPage($slug),
        };

        $group = null;
        foreach (SchoolWebsite::menuGroups($template) as $name => $pages) {
            if (isset($pages[$slug])) {
                $group = $name;
            }
        }

        return ['label' => $label, 'group' => $group, 'intro' => $intro, 'blocks' => self::forTemplate($blocks, $template)];
    }

    /**
     * The list Website Data opens on: the pages switched on for the site,
     * under the menus the site shows them in, each with what goes in it and
     * whether anything is filled in (or read from the school's records); and
     * how many are switched off.
     */
    public static function listing(SchoolWebsite $site, ?SchoolSiteData $lms = null): array
    {
        $template = $site->templateKey();
        $content  = $site->resolvedContent();
        $enabled  = $site->enabledPages();
        $lms    ??= new SchoolSiteData($site->organization_id);

        $row = function (string $slug) use ($template, $content, $lms) {
            $page    = self::page($slug, $template);
            $counted = array_filter($page['blocks'], fn ($b) => self::counts($b));
            $live    = array_filter($page['blocks'], fn ($b) => $b['type'] === 'live');
            $shown   = array_merge($live, $counted);

            return [
                'label'   => $page['label'],
                'summary' => implode(' · ', array_map(fn ($b) => $b['label'], array_slice(array_values($shown), 0, 4)))
                    . (count($shown) > 4 ? ' …' : ''),
                'status'  => ! $shown ? 'form'
                    : (array_filter($counted, fn ($b) => self::isFilled($b, $content))
                        || array_filter($live, fn ($b) => self::hasLive($lms, $b['source'])) ? 'filled' : 'empty'),
            ];
        };

        $groups = [];
        foreach (SchoolWebsite::menuGroups($template) as $group => $pages) {
            foreach (array_keys($pages) as $slug) {
                if (in_array($slug, $enabled, true)) {
                    $groups[$group][$slug] = $row($slug);
                }
            }
        }

        return [
            'details' => $row(self::DETAILS),
            'groups'  => $groups,
            'off'     => count(array_diff(array_keys(SchoolWebsite::allPages()), $enabled)),
        ];
    }

    /**
     * A page's tile on Website Data's list — Quick Links' look: [heroicon,
     * colour] (the colours are among app.css's safelisted tile colours).
     */
    public static function tile(string $slug): array
    {
        return match (true) {
            $slug === self::DETAILS              => ['building-library', 'indigo'],
            $slug === 'home'                     => ['home', 'blue'],
            $slug === 'about'                    => ['information-circle', 'indigo'],
            $slug === 'history'                  => ['clock', 'amber'],
            $slug === 'vision'                   => ['eye', 'sky'],
            $slug === 'mission'                  => ['flag', 'rose'],
            $slug === 'campus-life'              => ['sparkles', 'pink'],
            $slug === 'leadership'               => ['star', 'amber'],
            in_array($slug, ['chairman', 'founder', 'co-founder', 'managing-director', 'executive-director', 'director', 'manager-desk', 'principal-desk'], true)
                                                 => ['user', 'violet'],
            $slug === 'team'                     => ['user-group', 'teal'],
            in_array($slug, ['facilities', 'infrastructure', 'building', 'classrooms'], true)
                                                 => ['building-office-2', 'cyan'],
            $slug === 'labs'                     => ['beaker', 'lime'],
            in_array($slug, ['playground', 'sports'], true) => ['trophy', 'orange'],
            $slug === 'activities'               => ['puzzle-piece', 'fuchsia'],
            $slug === 'transport'                => ['truck', 'yellow'],
            $slug === 'gallery'                  => ['photo', 'pink'],
            $slug === 'admission'                => ['clipboard-document-list', 'green'],
            $slug === 'classes'                  => ['squares-2x2', 'blue'],
            $slug === 'fee-structure'            => ['currency-rupee', 'green'],
            $slug === 'rules'                    => ['book-open', 'gray'],
            in_array($slug, ['programs', 'curriculum'], true) => ['academic-cap', 'indigo'],
            $slug === 'academic-calendar'        => ['calendar-days', 'red'],
            $slug === 'result'                   => ['chart-bar', 'teal'],
            $slug === 'disclosures'              => ['document-text', 'gray'],
            $slug === 'contact'                  => ['map-pin', 'red'],
            $slug === 'appointment'              => ['calendar-days', 'sky'],
            $slug === 'career'                   => ['briefcase', 'orange'],
            $slug === 'query'                    => ['chat-bubble-left-right', 'purple'],
            default                              => ['globe-alt', 'gray'],
        };
    }

    /** The values the editor starts with: block id => value, read from the site. */
    public static function values(array $blocks, SchoolWebsite $site): array
    {
        $c   = $site->resolvedContent();
        $out = [];

        foreach ($blocks as $b) {
            $v = isset($b['path']) ? data_get($c, $b['path']) : null;
            if (self::blank($v) && isset($b['fallback'])) {
                $v = data_get($c, $b['fallback']);
            }

            switch ($b['type']) {
                case 'text':
                case 'textarea':
                case 'image':
                case 'file':
                    $out[$b['id']] = self::blank($v) ? (string) ($b['default'] ?? '') : (string) $v;
                    break;
                case 'images':
                    $out[$b['id']] = self::lines($v);
                    break;
                case 'list':
                    $rows = is_array($v) && $v ? $v : ($b['seed'] ?? []);
                    $out[$b['id']] = array_values(array_map(fn ($r) => self::row($b, $r), $rows));
                    break;
                case 'location':
                    $out[$b['id']] = [
                        'address' => (string) ($c['address'] ?? ''),
                        'lat'     => (string) ($c['map_lat'] ?? ''),
                        'lng'     => (string) ($c['map_lng'] ?? ''),
                        'embed'   => (string) ($c['map_embed'] ?? ''),
                        'link'    => '',
                    ];
                    break;
            }
        }

        return $out;
    }

    /**
     * The site's stored content with the page's values written in. Rows left
     * empty are dropped, as is a content page with no text and no photo (the
     * site then says it is being updated) and a detail cleared to nothing.
     */
    public static function apply(array $blocks, array $data, array $content): array
    {
        foreach ($blocks as $b) {
            if (! array_key_exists($b['id'], $data)) {
                continue;
            }
            $v = $data[$b['id']];

            switch ($b['type']) {
                case 'text':
                case 'textarea':
                case 'image':
                case 'file':
                    data_set($content, $b['path'], trim((string) $v));
                    break;
                case 'images':
                    data_set($content, $b['path'], implode("\n", self::lines($v)));
                    break;
                case 'list':
                    $keep = $b['keep'] ?? null;
                    $rows = array_map(fn ($r) => array_map(fn ($x) => trim((string) $x), self::row($b, $r)), is_array($v) ? $v : []);
                    $rows = array_filter($rows, fn ($r) => $keep ? $r[$keep] !== '' : implode('', $r) !== '');
                    data_set($content, $b['path'], array_values($rows));
                    break;
                case 'location':
                    $content['address']   = trim((string) ($v['address'] ?? ''));
                    $content['map_lat']   = trim((string) ($v['lat'] ?? ''));
                    $content['map_lng']   = trim((string) ($v['lng'] ?? ''));
                    $content['map_embed'] = trim((string) ($v['embed'] ?? ''));
                    break;
            }
        }

        foreach ($content['pages'] ?? [] as $slug => $p) {
            if (trim(($p['body'] ?? '') . ($p['image'] ?? '') . ($p['photos'] ?? '')) === '') {
                unset($content['pages'][$slug]);
            }
        }
        foreach ($content as $key => $value) {
            if ($value === '') {
                unset($content[$key]);
            }
        }

        return $content;
    }

    /** Whether a block has something on the site. */
    public static function isFilled(array $b, array $content): bool
    {
        if ($b['type'] === 'location') {
            return trim(($content['map_lat'] ?? '') . ($content['map_embed'] ?? '') . ($content['address'] ?? '')) !== '';
        }

        $v = data_get($content, $b['path'] ?? '');
        if (self::blank($v) && isset($b['fallback'])) {
            $v = data_get($content, $b['fallback']);
        }

        if ($b['type'] === 'list') {
            $col = $b['filled'] ?? null;
            foreach (is_array($v) ? $v : [] as $r) {
                if ($col ? trim((string) ($r[$col] ?? '')) !== '' : trim(implode('', array_map('strval', array_filter((array) $r, 'is_scalar')))) !== '') {
                    return true;
                }
            }

            return false;
        }

        return ! self::blank(is_string($v) ? trim($v) : $v);
    }

    /** Whether a block is counted when telling a page is filled. */
    public static function counts(array $b): bool
    {
        return ! in_array($b['type'], ['link', 'live', 'note', 'heading'], true) && ($b['counts'] ?? true);
    }

    /** Whether the school's records hold anything for a live block's source. */
    public static function hasLive(SchoolSiteData $lms, string $source): bool
    {
        return match ($source) {
            'classes' => (bool) $lms->classes(),
            'fees'    => (bool) $lms->fees(),
            'rules'     => $lms->hasRules(),
            'transport' => (bool) $lms->transport(),
            'events'    => (bool) $lms->events(),
            default     => false,
        };
    }

    /** A block showing what the site reads live from the school's records (LIVE). */
    private static function live(string $source): array
    {
        return ['id' => "live_{$source}", 'type' => 'live', 'source' => $source, 'label' => self::LIVE[$source][0]];
    }

    /** One block of a page by its id. */
    public static function block(array $page, string $id): ?array
    {
        foreach ($page['blocks'] as $b) {
            if ($b['id'] === $id) {
                return $b;
            }
        }

        return null;
    }

    /** A list row with exactly the list's columns, each a string. */
    public static function row(array $b, mixed $r): array
    {
        $r = is_array($r) ? $r : [];
        $out = [];
        foreach (array_keys($b['fields']) as $f) {
            $out[$f] = is_scalar($r[$f] ?? null) ? (string) $r[$f] : '';
        }

        return $out;
    }

    /** Photos kept one link a line (or as a list) → the links. */
    public static function lines(mixed $v): array
    {
        $items = is_array($v) ? $v : preg_split('/[\r\n]+/', (string) $v);

        return array_values(array_filter(array_map(fn ($x) => trim((string) $x), $items), fn ($x) => $x !== ''));
    }

    private static function blank(mixed $v): bool
    {
        return $v === null || $v === '' || $v === [];
    }

    private static function forTemplate(array $blocks, string $template): array
    {
        return array_values(array_filter($blocks, fn ($b) => ! isset($b['templates']) || in_array($template, $b['templates'], true)));
    }

    // ─── The pages ──────────────────────────────────────────────────────

    private static function details(): array
    {
        return [
            ['id' => 'h-school', 'type' => 'heading', 'label' => 'School'],
            ['id' => 'school_name', 'type' => 'text', 'path' => 'school_name', 'label' => 'School name', 'required' => true],
            ['id' => 'motto', 'type' => 'text', 'path' => 'motto', 'label' => 'Motto', 'placeholder' => 'e.g. Serving Nation Through Education'],
            ['id' => 'medium', 'type' => 'text', 'path' => 'medium', 'label' => 'Medium', 'placeholder' => 'e.g. An English Medium Co-Educational School'],
            ['id' => 'board', 'type' => 'text', 'path' => 'board', 'label' => 'Board', 'placeholder' => 'e.g. CBSE'],
            ['id' => 'affiliation_no', 'type' => 'text', 'path' => 'affiliation_no', 'label' => 'Affiliation No.'],
            ['id' => 'school_code', 'type' => 'text', 'path' => 'school_code', 'label' => 'School Code'],

            ['id' => 'h-photos', 'type' => 'heading', 'label' => 'Logo & photos'],
            ['id' => 'logo', 'type' => 'image', 'path' => 'logo', 'label' => 'Logo', 'help' => 'Leave empty to use the logo on your school profile.'],
            ['id' => 'board_logo', 'type' => 'image', 'path' => 'board_logo', 'label' => "Board's logo", 'help' => "Beside your name at the top. Empty: a seal with the board's name.", 'templates' => ['classic']],
            ['id' => 'page_banner', 'type' => 'image', 'path' => 'page_banner', 'label' => 'Page banner photo', 'help' => 'Behind the title of every inner page.', 'templates' => ['classic']],

            ['id' => 'h-contact', 'type' => 'heading', 'label' => 'Contact'],
            ['id' => 'phone', 'type' => 'text', 'path' => 'phone', 'label' => 'Phone', 'placeholder' => 'e.g. 9876543210, 9876543211'],
            ['id' => 'email', 'type' => 'text', 'path' => 'email', 'label' => 'Email'],
            ['id' => 'address', 'type' => 'textarea', 'rows' => 2, 'path' => 'address', 'label' => 'Address', 'help' => 'Set the map pin on the Home or Contact Us page.'],

            ['id' => 'h-social', 'type' => 'heading', 'label' => 'Social links'],
            ['id' => 'facebook', 'type' => 'text', 'path' => 'facebook', 'label' => 'Facebook', 'placeholder' => 'https://facebook.com/…'],
            ['id' => 'instagram', 'type' => 'text', 'path' => 'instagram', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/…'],
            ['id' => 'youtube', 'type' => 'text', 'path' => 'youtube', 'label' => 'YouTube', 'placeholder' => 'https://youtube.com/@…'],
            ['id' => 'twitter', 'type' => 'text', 'path' => 'twitter', 'label' => 'X (Twitter)', 'placeholder' => 'https://x.com/…'],
            ['id' => 'telegram', 'type' => 'text', 'path' => 'telegram', 'label' => 'Telegram', 'placeholder' => 'https://t.me/…'],
        ];
    }

    /** A page of text with a photo beside it and more photos under it. */
    private static function contentPage(string $slug, string $label): array
    {
        $desk = in_array($slug, self::DESKS, true);
        // History, Vision and Mission read the About School page's own while empty.
        $fallback = ['history' => 'history_text', 'vision' => 'vision', 'mission' => 'mission'][$slug] ?? null;

        $blocks = [
            ['id' => 'heading', 'type' => 'text', 'path' => "pages.{$slug}.heading", 'label' => 'Heading', 'default' => $label, 'counts' => false],
            ['id' => 'body', 'type' => 'textarea', 'rows' => 10, 'path' => "pages.{$slug}.body", 'label' => $desk ? 'Message' : 'Text',
                'help' => 'Leave an empty line between paragraphs.'] + ($fallback ? ['fallback' => $fallback] : []),
            ['id' => 'image', 'type' => 'image', 'path' => "pages.{$slug}.image", 'label' => $desk ? 'Photo' : 'Photo beside the text',
                'help' => $desk ? 'Their photo, shown beside the message.' : 'Optional.'],
            ['id' => 'photos', 'type' => 'images', 'path' => "pages.{$slug}.photos", 'label' => 'More photos',
                'help' => 'Shown as a gallery under the text; a click opens each one large.'],
        ];
        if ($slug === 'career') {
            $blocks[] = ['id' => 'form', 'type' => 'note', 'label' => 'The job application form is on the page already — applications arrive in Enquiries → Website Enquiries as Career.'];
        }
        if ($slug === 'rules') {
            $blocks[] = self::live('rules');
            $blocks[] = ['id' => 'admission_rules', 'type' => 'list', 'path' => 'admission_rules', 'label' => 'Admission rules',
                'help' => 'Optional — rules for admission only, shown under your Rules & Regulations.',
                'fields' => ['text' => ['text', 'Rule', 'e.g. The session begins on 1 April']],
                'add' => 'Add rule'];

            return ['Your Rules & Regulations, with any text and photos of your own above them.', $blocks];
        }
        if ($slug === 'transport') {
            $blocks[] = self::live('transport');

            return ['Your transport routes — pickup and drop times, the monthly and the annual fare — with any text and photos of your own above them.', $blocks];
        }

        return [$desk ? 'A message from the desk, with their photo beside it.' : 'Text, with a photo beside it and more photos under it.', $blocks];
    }

    /** A table of documents, each a PDF (or photo, or link) to open or download. */
    private static function documentPage(string $slug): array
    {
        $list = ['id' => 'documents', 'type' => 'list', 'path' => "documents.{$slug}", 'label' => 'Documents',
            'fields' => [
                'title' => ['text', 'Document', 'e.g. Fee Structure 2026-27'],
                'date'  => ['text', 'Updated', 'e.g. Apr 2026'],
                'file'  => ['file', 'File'],
            ],
            'add' => 'Add document', 'filled' => 'file', 'keep' => 'title',
            'help' => 'Upload a PDF or a photo for each — or paste a link (a YouTube link plays on the page).'];

        if ($slug === 'disclosures') {
            $list['seed'] = SchoolWebsite::defaultDisclosures();
            $list['help'] = "CBSE's list is already here — upload each one's PDF. A row without a file shows \"Not uploaded\"; "
                . 'every uploaded one also gets its own page in the Documents menu.';
        }
        if ($slug === 'fee-structure') {
            $list['label'] = 'Fee documents';
            $list['help']  = 'Optional — a fee PDF or circular, shown under the fee structure.';

            return ['The fees from your Fee Structure, with a note and any documents under them.', [
                self::live('fees'),
                ['id' => 'fee_note', 'type' => 'text', 'path' => 'fee_note', 'label' => 'Fee note',
                    'placeholder' => 'e.g. Fees are payable quarterly by the 10th', 'help' => 'Optional — a line under the fee structure heading.'],
                $list,
            ]];
        }

        if ($slug === 'academic-calendar') {
            $list['label'] = 'Calendar documents';
            $list['help']  = 'Optional — the calendar as a PDF or photo, shown under the events.';

            return ['Every event on your calendar this session, with any documents under them.', [self::live('events'), $list]];
        }

        return ['A table of documents to open or download.', [$list]];
    }

    private static function resultPage(): array
    {
        return ['Board results, one row a year; the pass percentage is worked out for you.', [
            ['id' => 'results', 'type' => 'list', 'path' => 'results', 'label' => 'Results',
                'fields' => [
                    'year'     => ['text', 'Year', 'e.g. 2026'],
                    'appeared' => ['number', 'Appeared'],
                    'passed'   => ['number', 'Passed'],
                ],
                'percent' => ['passed', 'appeared'], 'add' => 'Add year'],
        ]];
    }

    /** The pages with a layout of their own. */
    private static function viewPage(string $slug): array
    {
        $form = fn (string $what) => ['id' => 'form', 'type' => 'note', 'label' => "{$what} — filled forms arrive in Enquiries → Website Enquiries."];

        return match ($slug) {
            'home' => ['The first page visitors see, from top to bottom.', [
                ['id' => 'hero_slides', 'type' => 'list', 'path' => 'hero_slides', 'label' => 'Slider', 'templates' => ['classic'],
                    'help' => 'Photos that slide at the top, each with an optional title. A slide without a photo shows your logo.',
                    'fields' => [
                        'image'    => ['image', 'Photo'],
                        'title'    => ['text', 'Title', 'e.g. Admissions open for 2026-27'],
                        'subtitle' => ['text', 'Subtitle', 'e.g. Nursery to Class XII'],
                    ],
                    'add' => 'Add slide', 'bulk' => 'image'],
                ['id' => 'welcome', 'type' => 'link', 'to' => 'about', 'label' => 'Welcome', 'help' => 'The about text, with your logo.'],
                ['id' => 'features', 'type' => 'link', 'to' => 'facilities', 'label' => 'Features & Facilities', 'help' => 'The facility cards.'],
                ['id' => 'philosophy', 'type' => 'textarea', 'rows' => 3, 'path' => 'philosophy', 'label' => 'Our Philosophy', 'templates' => ['classic'],
                    'help' => 'Shown in a band with your vision and mission (from About School).'],
                ['id' => 'campus', 'type' => 'link', 'to' => 'campus-life', 'label' => 'Campus & School Life', 'help' => 'The band with a photo.', 'templates' => ['classic']],
                ['id' => 'cta_heading', 'type' => 'text', 'path' => 'cta_heading', 'label' => 'Admission band heading', 'placeholder' => 'e.g. Admissions Open'],
                ['id' => 'cta_text', 'type' => 'textarea', 'rows' => 2, 'path' => 'cta_text', 'label' => 'Admission band text'],
                ['id' => 'admission', 'type' => 'link', 'to' => 'admission', 'label' => 'Admission schedule, steps and documents', 'help' => 'Under the admission band.', 'templates' => ['classic']],
                ['id' => 'location', 'type' => 'location', 'label' => 'Our Location', 'templates' => ['classic'],
                    'help' => 'The map at the foot of the home page, and on Contact Us.'],
            ]],
            'about' => ['About the school, its history, philosophy, vision and mission.', [
                ['id' => 'about_heading', 'type' => 'text', 'path' => 'about_heading', 'label' => 'Heading'],
                ['id' => 'about_text', 'type' => 'textarea', 'rows' => 6, 'path' => 'about_text', 'label' => 'About the school', 'help' => 'Also the welcome text on the home page.'],
                ['id' => 'about_text2', 'type' => 'textarea', 'rows' => 4, 'path' => 'about_text2', 'label' => 'More about the school', 'help' => 'A second paragraph, optional.'],
                ['id' => 'history_text', 'type' => 'textarea', 'rows' => 4, 'path' => 'history_text', 'label' => 'Our History', 'help' => 'Also on the History page while that is empty.'],
                ['id' => 'philosophy', 'type' => 'textarea', 'rows' => 3, 'path' => 'philosophy', 'label' => 'Our Philosophy'],
                ['id' => 'vision', 'type' => 'textarea', 'rows' => 3, 'path' => 'vision', 'label' => 'Our Vision'],
                ['id' => 'mission', 'type' => 'textarea', 'rows' => 3, 'path' => 'mission', 'label' => 'Our Mission'],
            ]],
            'leadership' => ['A card for each leader, with their photo and message.', [
                ['id' => 'leadership', 'type' => 'list', 'path' => 'leadership', 'label' => 'People',
                    'fields' => [
                        'photo'   => ['image', 'Photo'],
                        'name'    => ['text', 'Name'],
                        'role'    => ['text', 'Designation', 'e.g. Principal'],
                        'message' => ['textarea', 'Message'],
                    ],
                    'add' => 'Add person'],
            ]],
            'team' => ['Your staff, each with a photo.', [
                ['id' => 'team', 'type' => 'list', 'path' => 'team', 'label' => 'Members', 'help' => 'Empty: the management team on your school profile.',
                    'fields' => ['photo' => ['image', 'Photo'], 'name' => ['text', 'Name'], 'role' => ['text', 'Designation', 'e.g. PGT Physics']],
                    'add' => 'Add member', 'bulk' => 'photo'],
            ]],
            'facilities' => ['A card for each facility — also the cards on the home page.', [
                ['id' => 'facilities', 'type' => 'list', 'path' => 'facilities', 'label' => 'Facilities', 'help' => 'Empty: a starter set is shown.',
                    'fields' => [
                        'image' => ['image', 'Photo'],
                        'title' => ['text', 'Title', 'e.g. Science Labs'],
                        'icon'  => ['text', 'Icon', 'e.g. 🔬'],
                        'desc'  => ['textarea', 'Description'],
                    ],
                    'add' => 'Add facility'],
            ]],
            'gallery' => ['Your photos, in a grid.', [
                ['id' => 'gallery', 'type' => 'list', 'path' => 'gallery', 'label' => 'Photos',
                    'fields' => ['image' => ['image', 'Photo'], 'caption' => ['text', 'Caption', 'e.g. Annual Day 2026']],
                    'add' => 'Add photo', 'bulk' => 'image'],
            ]],
            // The page is the online admission form alone; the classes, fees and
            // rules have pages of their own. What is written here shows on
            // Classic's home page, under its admission band.
            'admission' => ['The online admission form — classes, fees and rules have pages of their own.', [
                $form('This page is the online admission form; its Class list is your classes'),
                ['id' => 'h-home', 'type' => 'heading', 'label' => 'On your home page, under the admission band', 'templates' => ['classic']],
                ['id' => 'admission_intro', 'type' => 'textarea', 'rows' => 3, 'path' => 'admission_intro', 'label' => 'Introduction', 'templates' => ['classic']],
                ['id' => 'admission_session', 'type' => 'text', 'path' => 'admission_session', 'label' => 'Admission schedule', 'placeholder' => 'e.g. 1 April – 31 July 2026', 'templates' => ['classic']],
                ['id' => 'admission_steps', 'type' => 'list', 'path' => 'admission_steps', 'label' => 'Steps to apply', 'templates' => ['classic'],
                    'fields' => ['title' => ['text', 'Step', 'e.g. Fill the form'], 'desc' => ['text', 'Details']],
                    'add' => 'Add step'],
                ['id' => 'documents_required', 'type' => 'list', 'path' => 'documents_required', 'label' => 'Documents required', 'templates' => ['classic'],
                    'fields' => ['text' => ['text', 'Document', 'e.g. Birth Certificate']],
                    'add' => 'Add document'],
            ]],
            'classes' => ['Your classes with their sections and subjects, and a card for each class.', [
                self::live('classes'),
                ['id' => 'classes', 'type' => 'list', 'path' => 'classes', 'label' => 'Class cards', 'help' => 'Optional — a photo, age, timing and seats for a class.',
                    'fields' => [
                        'image'    => ['image', 'Photo'],
                        'title'    => ['text', 'Class', 'e.g. Nursery'],
                        'age'      => ['text', 'Age', 'e.g. 3-4 years'],
                        'time'     => ['text', 'Timing', 'e.g. 8 AM – 12 PM'],
                        'capacity' => ['text', 'Seats', 'e.g. 30'],
                    ],
                    'add' => 'Add class'],
            ]],
            'contact' => ['How to reach the school, the map, and the enquiry form.', [
                ['id' => 'phone', 'type' => 'text', 'path' => 'phone', 'label' => 'Phone'],
                ['id' => 'email', 'type' => 'text', 'path' => 'email', 'label' => 'Email'],
                ['id' => 'location', 'type' => 'location', 'label' => 'Address & map', 'help' => 'The map on this page (and on the home page).'],
                $form('The enquiry form is on the page already'),
            ]],
            'appointment' => ['A form to book a visit.', [$form('This page is a form, nothing to fill in')]],
            'query'       => ['A form for questions.', [$form('This page is a form, nothing to fill in')]],
            default       => ['', []],
        };
    }
}
