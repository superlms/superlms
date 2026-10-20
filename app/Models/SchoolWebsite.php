<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A school's public marketing website (in one of templates()), served on its own
 * custom domain. Content is auto-fetched from the Organization / SchoolInfo
 * where available, and any field can be overridden from the super-admin
 * builder (stored in the `content` JSON column).
 */
class SchoolWebsite extends Model
{
    protected $fillable = [
        'organization_id',
        'domain',
        'template',
        'theme',
        'pages',
        'content',
        'status',
    ];

    protected $casts = [
        'theme'   => 'array',
        'pages'   => 'array',
        'content' => 'array',
        'status'  => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // ─── Catalogue: templates, pages & themes ──────────────────────────────

    /**
     * The site designs a school can be given, each a folder of blades under
     * resources/views/school-site/<key>. Classic CBSE is the only one: the
     * Playful (kider) template was taken out on the user's ask of 2026-10-20,
     * and every site moved to Classic.
     */
    public const DEFAULT_TEMPLATE = 'classic';

    public static function templates(): array
    {
        return [
            'classic' => ['label' => 'Classic CBSE', 'desc' => 'Tricolour masthead with the logo, school name and board, a photo slider and a menu of every page.'],
        ];
    }

    /** This site's template, or Classic when it names one that does not exist. */
    public function templateKey(): string
    {
        return array_key_exists((string) $this->template, static::templates()) ? $this->template : self::DEFAULT_TEMPLATE;
    }

    /**
     * How the Classic template groups its menu, after the CBSE school sites it
     * is modelled on: group => slugs. A slug left out of every group but
     * switched on goes under More; the Documents menu is built from the
     * disclosure documents the school has uploaded, not listed here.
     */
    public static function classicNavGroups(): array
    {
        return [
            'Home'                 => ['home'],
            // About School, Mission, Vision and Values & Goals read the School
            // Profile; the Manager's and Principal's desks sit with the other
            // desks under Messaging (user's ask of 2026-10-21).
            'About Us'             => ['about', 'mission', 'vision', 'values-goals', 'campus-life'],
            'Messaging'            => ['chairman', 'founder', 'co-founder', 'managing-director', 'executive-director', 'director', 'manager-desk', 'principal-desk', 'leadership'],
            'Features'             => ['facilities'],
            'Photogallery'         => ['gallery', 'sports', 'building', 'labs', 'classrooms', 'playground', 'activities', 'programs'],
            'Online Admission'     => ['admission', 'fee-structure', 'rules', 'classes'],
            'Curriculum'           => ['curriculum', 'academic-calendar'],
            'Mandatory Disclosure' => ['disclosures'],
            'Contact'              => ['contact', 'career', 'query', 'appointment'],
            'More'                 => ['result', 'infrastructure', 'team', 'transport'],
        ];
    }

    /**
     * Every page under the menu group it sits in on a site of this template,
     * in the menu's order: group => [slug => label]. The site's menu is this
     * with the pages switched off taken out; the Super Admin's Pages tab lists
     * it whole, so a page is ticked where the school's visitors will find it.
     */
    public static function menuGroups(string $template = self::DEFAULT_TEMPLATE): array
    {
        $labels = static::allPages();

        $groups = static::classicNavGroups();
        $groups['More'] = array_merge($groups['More'], array_values(array_diff(array_keys($labels), array_merge(...array_values($groups)))));

        return array_map(fn ($slugs) => array_combine($slugs, array_map(fn ($slug) => $labels[$slug], $slugs)), $groups);
    }

    /**
     * The template's page catalogue, grouped the way the navigation shows it.
     *
     * Most entries are "content" pages — a heading, a body and an optional
     * image, all edited from Website Data — so a school can publish the whole
     * CBSE page set without a blade per page. Only the pages with real layout
     * of their own ('view') or a table of their own ('documents', 'result')
     * get bespoke handling.
     *
     * Shape: group label => [slug => [label, type]].
     */
    public static function pageGroups(): array
    {
        return [
            'Home' => [
                'home' => ['Home', 'view'],
            ],
            'About Us' => [
                'about'        => ['About School', 'view'],
                'values-goals' => ['Values & Goals', 'view'],   // was History (/history still opens it)
                'vision'       => ['Vision', 'content'],
                'mission'      => ['Mission', 'content'],
                'campus-life'  => ['Campus & School Life', 'content'],
            ],
            'Management' => [
                'leadership'         => ['Leadership', 'view'],
                'chairman'           => ["Chairman's Desk", 'content'],
                'founder'            => ["Founder's Desk", 'content'],
                'co-founder'         => ["Co-Founder's Desk", 'content'],
                'managing-director'  => ['Managing Director', 'content'],
                'executive-director' => ['Executive Director', 'content'],
                'director'           => ["Director's Desk", 'content'],
                'manager-desk'       => ["Manager's Desk", 'content'],
                'principal-desk'     => ["Principal's Desk", 'content'],
                'team'               => ['Our Team', 'view'],
            ],
            'Facilities' => [
                'facilities'  => ['Features & Facilities', 'view'],
                'labs'        => ['Laboratories', 'content'],
                'classrooms'  => ['Classrooms', 'content'],
                'playground'  => ['Playground', 'content'],
                'sports'      => ['Sports & Activities', 'content'],
                'activities'  => ['Activities', 'content'],
                'transport'   => ['Transport', 'content'],
                'infrastructure' => ['Infrastructure Details', 'content'],
            ],
            'Gallery' => [
                'gallery'  => ['Photo Gallery', 'view'],
                'building' => ['Building Photos', 'content'],
            ],
            'Admission' => [
                'admission'     => ['Admission', 'view'],
                'classes'       => ['Classes', 'view'],
                'fee-structure' => ['Fee Structure', 'documents'],
                'rules'         => ['Rules & Regulations', 'content'],
                'programs'      => ['Programs', 'content'],
            ],
            'Academics' => [
                'curriculum'        => ['Curriculum', 'content'],
                'academic-calendar' => ['Academic Calendar', 'documents'],
                'result'            => ['Result', 'result'],
            ],
            'Disclosures' => [
                'disclosures' => ['Mandatory Disclosures', 'documents'],
            ],
            'Contact' => [
                'contact'     => ['Contact Us', 'view'],
                'appointment' => ['Appointment', 'view'],
                'career'      => ['Career', 'content'],
                'query'       => ['Query', 'view'],
            ],
        ];
    }

    /** Flat slug => label, for the builder's page toggles and the nav. */
    public static function allPages(): array
    {
        $out = [];
        foreach (static::pageGroups() as $pages) {
            foreach ($pages as $slug => [$label, $type]) {
                $out[$slug] = $label;
            }
        }
        return $out;
    }

    /** How a page is rendered: 'view' | 'content' | 'documents' | 'result'. */
    public static function pageType(string $slug): ?string
    {
        foreach (static::pageGroups() as $pages) {
            if (isset($pages[$slug])) {
                return $pages[$slug][1];
            }
        }
        return null;
    }

    /**
     * The CBSE disclosure rows every affiliated school has to publish. Used
     * as the starting list when a school has not uploaded its own documents
     * yet, so the page is never blank — each row simply reads "not uploaded".
     */
    public static function defaultDisclosures(): array
    {
        $titles = [
            'Copy of Affiliation / Upgradation Letter',
            'Copy of Societies / Trust / Company Registration',
            'Copy of No Objection Certificate (NOC) issued by the State Govt.',
            'Copy of Recognition Certificate under RTE Act, 2009',
            'Copy of Valid Building Safety Certificate',
            'Copy of Valid Fire Safety Certificate',
            'Copy of the DEO Certificate submitted for Affiliation / Upgradation',
            'Copy of Valid Water, Health and Sanitation Certificate',
            'Copy of Self Certification / Declaration',
            'Copy of Land Certificate',
            'Annual Academic Calendar',
            'List of School Management Committee (SMC)',
            'List of Parents Teachers Association (PTA) Members',
            'Last Three-Year Result of the Board Examination',
            'Fee Structure of the School',
            'Staff Statement (Teaching & Non-Teaching)',
            'Transfer Certificate Sample',
            'Transport Certificate',
        ];

        return array_map(fn ($t) => ['title' => $t, 'file' => '', 'date' => ''], $titles);
    }

    /** Built-in colour presets (primary / light tint / dark). */
    public static function themePresets(): array
    {
        return [
            'sunset' => ['label' => 'Sunset (default)', 'primary' => '#FE5D37', 'light' => '#FFF5F3', 'dark' => '#103741'],
            'ocean'  => ['label' => 'Ocean Blue',       'primary' => '#2C7BE5', 'light' => '#EDF4FF', 'dark' => '#0B2545'],
            'forest' => ['label' => 'Forest Green',     'primary' => '#1E9E62', 'light' => '#ECFBF3', 'dark' => '#10362A'],
            'royal'  => ['label' => 'Royal Purple',     'primary' => '#6F56FE', 'light' => '#F2EFFF', 'dark' => '#1A0F2E'],
            'rose'   => ['label' => 'Rose Pink',        'primary' => '#E6477F', 'light' => '#FDEEF4', 'dark' => '#2E1020'],
            'amber'  => ['label' => 'Amber Gold',       'primary' => '#E8A009', 'light' => '#FFF8E8', 'dark' => '#3A2A05'],
            'tricolour' => ['label' => 'Tricolour (Red & Green)', 'primary' => '#E8000B', 'light' => '#FFF6EA', 'dark' => '#2D6944'],
        ];
    }

    /** The resolved theme (preset values, with optional primary override). */
    public function resolvedTheme(): array
    {
        $presets = static::themePresets();
        $theme   = $this->theme ?? [];
        $preset  = $presets[$theme['preset'] ?? 'sunset'] ?? $presets['sunset'];

        return [
            'primary' => ($theme['primary'] ?? null) ?: $preset['primary'],
            'light'   => ($theme['light']   ?? null) ?: $preset['light'],
            'dark'    => ($theme['dark']    ?? null) ?: $preset['dark'],
        ];
    }

    /** Enabled page slugs (home always included). */
    public function enabledPages(): array
    {
        $pages = $this->pages ?: array_keys(static::allPages());
        return array_values(array_unique(array_merge(['home'], $pages)));
    }

    public function isPageEnabled(string $slug): bool
    {
        return in_array($slug, $this->enabledPages(), true);
    }

    // ─── Content auto-fetch + resolution ───────────────────────────────────

    /**
     * Default content auto-fetched from the Organization / SchoolInfo.
     * These act as fallbacks when the builder has not overridden a field.
     */
    public function autoContent(): array
    {
        $org    = $this->organization;
        $info   = $org?->schoolInfo;
        $name   = $org?->name ?: 'Our School';

        $team = [];
        if ($info && $info->relationLoaded('managementTeam') === false) {
            $info->loadMissing('managementTeam');
        }
        foreach (($info?->managementTeam ?? []) as $m) {
            $team[] = [
                'name'  => $m->name ?? '',
                'role'  => $m->designation ?? '',
                'photo' => $m->photo_path ?? '',
            ];
        }

        return [
            'school_name'   => $name,
            'tagline'       => $org?->education_board ? ($org->education_board . ' School') : 'Quality Education',
            'motto'         => '',
            'medium'        => '',
            'board'         => $org?->education_board ?? '',
            'affiliation_no'=> '',
            'school_code'   => '',
            'logo'          => $org?->logo ?? '',
            'board_logo'    => '',   // the board's seal, beside the name in the Classic masthead
            'page_banner'   => '',   // photo behind every inner page's title (Classic)
            'map_embed'     => '',   // Google Maps embed link; blank → the pin, else a map of the address
            'map_lat'       => '',   // the school's pin, set in Website Data (a pasted
            'map_lng'       => '',   // Google Maps link, or the phone's own location)

            'hero_title'    => 'Welcome to ' . $name,
            'hero_subtitle' => 'Nurturing young minds with care, creativity and quality education for a brighter future.',
            // Home slider (Classic): [{image, title, subtitle}]. A slide with
            // no image shows the school's logo as a watermark instead.
            'hero_slides'   => [],

            'about_heading' => 'Learn More About ' . $name,
            'about_text'    => $info?->about_school ?: 'We are committed to providing a safe, joyful and inspiring environment where every child can learn, grow and thrive.',
            'about_text2'   => '',
            'history_text'  => '',
            'philosophy'    => '',
            'vision'        => $info?->usm_vision ?? '',
            'mission'       => $info?->usm_mission ?? '',
            'values'        => $info?->usm_values ?? '',
            'goals'         => $info?->usm_goals ?? '',

            // Admissions
            'admission_intro'   => 'Admissions are open. Join our school family — enquire today and give your child the best start.',
            'admission_session' => '',
            'fee_note'          => '',
            'curriculum_text'   => '',

            'cta_heading'   => 'Admissions Open',
            'cta_text'      => 'Give your child the best start. Enquire today and become part of the ' . $name . ' family.',

            'phone'         => $org?->mobile_number ?: ($info?->school_mobile ?? ''),
            'email'         => $org?->email ?: ($info?->school_email ?? ''),
            'address'       => $org?->address ?: ($info?->school_address ?? ''),

            'facebook'      => '',
            'instagram'     => '',
            'youtube'       => '',
            'twitter'       => '',
            'telegram'      => '',

            // Repeatable lists (empty → template shows sensible samples)
            'classes'           => [],
            'team'              => $team,
            'leadership'        => [],   // [{name, role, photo, message}]
            'facilities'        => [],   // [{icon, title, desc}]
            'why_us'            => [],   // [{icon, title, desc}]
            'stats'             => [],   // [{value, label}]
            'admission_steps'   => [],   // [{title, desc}]
            'documents_required'=> [],   // [{text}]
            'admission_rules'   => [],   // [{text}]
            'gallery'           => [],   // [{image, caption}]

            // Generic content pages: slug => {heading, body, image}. Anything
            // pageGroups() marks as 'content' reads its copy from here.
            'pages'     => [],
            // Document tables: slug => [{title, file, date}].
            'documents' => [],
            // Board results: [{year, appeared, passed}].
            'results'   => [],
        ];
    }

    /** Heading / body / image for one generic content page. */
    public function pageContent(string $slug): array
    {
        $content = $this->resolvedContent();
        $row     = $content['pages'][$slug] ?? [];

        // More photos, one URL a line — what makes Labs, Sports, Building and
        // the like a photo page.
        $photos = preg_split('/\s*[\r\n]+\s*/', trim((string) ($row['photos'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);

        // Vision and Mission are the School Profile's; the page's own text (or
        // the About School page's) shows only while the profile has none.
        $body = (string) ($row['body'] ?? '');
        if (in_array($slug, ['vision', 'mission'], true)) {
            $profile = $this->profileTexts()[$slug];
            if ($profile !== '') {
                $body = $profile;
            } elseif (trim($body) === '') {
                $body = (string) ($content[$slug] ?? '');
            }
        }

        return [
            'heading' => ($row['heading'] ?? '') !== '' ? $row['heading'] : (static::allPages()[$slug] ?? 'Page'),
            'body'    => $body,
            'image'   => $row['image'] ?? '',
            'photos'  => $photos,
        ];
    }

    /**
     * The Google Maps embed for the school's map, from resolved content: the
     * embed link it saved (a pasted <iframe> works too), else its pin, else
     * a search for its name and address; '' with none of them. Only Google
     * Maps links are embedded.
     */
    public static function mapSrc(array $c): string
    {
        $embed = trim((string) ($c['map_embed'] ?? ''));
        if (preg_match('/src=["\']([^"\']+)["\']/i', $embed, $m)) {
            $embed = html_entity_decode($m[1]);
        }
        if (preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed#i', $embed) || preg_match('#^https://maps\.google\.[a-z.]+/#i', $embed)) {
            return $embed;
        }

        $lat = trim((string) ($c['map_lat'] ?? ''));
        $lng = trim((string) ($c['map_lng'] ?? ''));
        if (is_numeric($lat) && is_numeric($lng) && abs((float) $lat) <= 90 && abs((float) $lng) <= 180) {
            return "https://maps.google.com/maps?q={$lat},{$lng}&z=16&output=embed";
        }

        $address = trim((string) ($c['address'] ?? ''));
        if ($address !== '') {
            return 'https://maps.google.com/maps?q=' . urlencode(($c['school_name'] ?? '') . ', ' . $address) . '&z=14&output=embed';
        }

        return '';
    }

    /** The rows for a document page, falling back to the CBSE checklist. */
    public function pageDocuments(string $slug): array
    {
        $rows = $this->resolvedContent()['documents'][$slug] ?? [];

        if (empty($rows) && $slug === 'disclosures') {
            return static::defaultDisclosures();
        }

        return $rows;
    }

    /**
     * What the About Us pages read from the School Profile (Profile → School
     * Info): its about text, vision, mission, values and goals — content key
     * => text, '' where the profile has none. A text the profile has wins
     * over anything Website Data holds (user's ask of 2026-10-21), so the
     * site follows the profile as the school changes it.
     */
    public const PROFILE_TEXTS = ['about_text' => 'about_school', 'vision' => 'usm_vision', 'mission' => 'usm_mission',
        'values' => 'usm_values', 'goals' => 'usm_goals'];

    public function profileTexts(): array
    {
        $info = $this->organization?->schoolInfo;

        return array_map(fn ($field) => trim((string) ($info?->{$field} ?? '')), self::PROFILE_TEXTS);
    }

    /** Stored overrides merged over the auto-fetched content; the profile's own texts win. */
    public function resolvedContent(): array
    {
        $auto    = $this->autoContent();
        $stored  = $this->content ?? [];

        // Scalars: stored value wins when non-empty; lists: stored wins when non-empty.
        $out = $auto;
        foreach ($stored as $key => $val) {
            if (is_array($val)) {
                if (!empty($val)) {
                    $out[$key] = $val;
                }
            } elseif ($val !== null && $val !== '') {
                $out[$key] = $val;
            }
        }
        foreach ($this->profileTexts() as $key => $text) {
            if ($text !== '') {
                $out[$key] = $text;
            }
        }
        return $out;
    }

    /**
     * Content to store, less what only repeats the school's own record. Website
     * Data opens with every field filled from the profile, so a save used to
     * keep a copy of each — and the site then showed that copy for good: a
     * logo replaced (or moved to new storage) stayed the old, dead link.
     * A value equal to the one fetched from the profile is dropped, and so is
     * a logo pointing at the school-logo uploads (the profile's own logo, an
     * old one included); the site then follows the profile.
     */
    public function overridesOnly(array $content): array
    {
        $auto = $this->autoContent();

        foreach ($content as $key => $value) {
            if (! array_key_exists($key, $auto)) {
                continue;
            }
            $same = is_array($value) || is_array($auto[$key])
                ? $value == $auto[$key]
                : trim((string) $value) === trim((string) $auto[$key]);
            if ($same) {
                unset($content[$key]);
            }
        }

        $logoPath = (string) parse_url((string) ($content['logo'] ?? ''), PHP_URL_PATH);
        if (preg_match('#/(superadmin/schools/logos|admin/profile/photos)/#', $logoPath)) {
            unset($content['logo']);
        }

        return $content;
    }

    /**
     * Resolve a stored media value to a usable URL.
     * Absolute URLs are returned as-is; relative paths go through asset();
     * an empty value is null.
     */
    public static function media(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return asset(ltrim($value, '/'));
    }

    /** URL of the school's admin panel login on the main app. */
    public function adminLoginUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/login';
    }

    /** Resolve a published website for an incoming host, or null. */
    public static function forHost(?string $host): ?self
    {
        if (! $host) {
            return null;
        }
        $host = preg_replace('/:\d+$/', '', strtolower($host)); // strip port

        return static::with('organization.schoolInfo')
            ->where('status', true)
            ->where(function ($q) use ($host) {
                $q->where('domain', $host)
                  ->orWhere('domain', 'www.' . $host)
                  ->orWhere('domain', preg_replace('/^www\./', '', $host));
            })
            ->first();
    }
}
