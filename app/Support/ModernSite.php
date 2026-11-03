<?php

namespace App\Support;

use App\Models\SchoolWebsite;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * What the two modern templates (Vibrant and Breeze, user's ask of
 * 2026-11-03) work out from a site before laying it out. They read exactly
 * the content, pages and menu Classic reads — nothing of their own — so a
 * school can move between the three at any time: the home slider's slides,
 * the photos a home page can use, the facility cards, the messages from the
 * desks, the figures, the calendar month and the like.
 */
class ModernSite
{
    /** Vibrant's accent colours, rung through its cards, icons and tags after the school's own. */
    public const PALETTE = ['#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#F97316', '#3B82F6'];

    /** Breeze's soft tints, each with the ink drawn on it: [tint, ink]. */
    public const TINTS = [
        ['#EEF2FF', '#4F46E5'], ['#FDF2F8', '#DB2777'], ['#ECFDF5', '#059669'],
        ['#FFF7ED', '#EA580C'], ['#F0F9FF', '#0284C7'], ['#F5F3FF', '#7C3AED'],
    ];

    /**
     * The colours of the $i-th card, badge or tag as CSS variables: --c (its
     * colour) and --t (its tint). Vibrant rings bright colours, Breeze soft
     * tints; the first is always the school's own.
     */
    public static function swatch(string $template, int $i, string $primary): string
    {
        if ($template === 'breeze') {
            [$tint, $ink] = $i % (count(self::TINTS) + 1) === 0
                ? ["color-mix(in srgb, {$primary} 10%, #fff)", $primary]
                : self::TINTS[($i % (count(self::TINTS) + 1)) - 1];

            return "--c: {$ink}; --t: {$tint};";
        }
        $colour = $i % (count(self::PALETTE) + 1) === 0 ? $primary : self::PALETTE[($i % (count(self::PALETTE) + 1)) - 1];

        return "--c: {$colour}; --t: color-mix(in srgb, {$colour} 12%, #fff);";
    }

    /** The desks, as Website Data lists them: a message from one person each. */
    private const DESKS = ['principal-desk', 'chairman', 'director', 'founder', 'co-founder', 'managing-director', 'executive-director', 'manager-desk'];

    /**
     * The home slider, as Classic builds it: the school's slides; else up to
     * three gallery photos under the hero and admission captions; else the
     * captions alone (each on the logo watermark). [{image, title, subtitle}].
     */
    public static function slides(array $c): array
    {
        $captions = array_values(array_filter([
            ['title' => $c['hero_title'] ?? '', 'subtitle' => ($c['address'] ?? '') ?: ($c['hero_subtitle'] ?? '')],
            ['title' => $c['cta_heading'] ?? '', 'subtitle' => $c['cta_text'] ?? ''],
        ], fn ($s) => trim((string) $s['title']) !== ''));

        $slides = array_values(array_filter($c['hero_slides'] ?? [], fn ($s) =>
            trim(($s['image'] ?? '') . ($s['title'] ?? '') . ($s['subtitle'] ?? '')) !== ''));
        if (! $slides) {
            foreach (array_slice(self::gallery($c), 0, 3) as $i => $g) {
                $slides[] = ['image' => $g['image']] + ($captions[$i % max(1, count($captions))] ?? []);
            }
            if (! $slides) {
                $slides = $captions ?: [['title' => $c['school_name'] ?? '', 'subtitle' => '']];
            }
        }

        return array_map(fn ($s) => $s + ['image' => '', 'title' => '', 'subtitle' => ''], $slides);
    }

    /** The gallery's photos that have a photo: [{image, caption}]. */
    public static function gallery(array $c): array
    {
        return array_values(array_filter(array_map(fn ($g) => [
            'image'   => trim((string) ($g['image'] ?? '')),
            'caption' => trim((string) ($g['caption'] ?? '')),
        ], $c['gallery'] ?? []), fn ($g) => $g['image'] !== ''));
    }

    /** Every photo the school has given its site, the slider's first: a list of stored values. */
    public static function photos(array $c): array
    {
        $pool = [];
        foreach ($c['hero_slides'] ?? [] as $s) { $pool[] = $s['image'] ?? ''; }
        foreach ($c['gallery'] ?? [] as $g) { $pool[] = $g['image'] ?? ''; }
        foreach ($c['pages'] ?? [] as $p) { $pool[] = $p['image'] ?? ''; }
        foreach ($c['facilities'] ?? [] as $f) { $pool[] = $f['image'] ?? ''; }

        return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $pool))));
    }

    /**
     * The facility cards: the school's own, else the starter set Classic
     * shows (its home page's six, or its Facilities page's nine with $all).
     */
    public static function facilities(array $c, bool $all = false): array
    {
        if (! empty($c['facilities'])) {
            return array_values($c['facilities']);
        }
        if (! $all) {
            return [
                ['icon' => '🏫', 'title' => 'Classroom',    'desc' => 'Spacious, well-ventilated classrooms with ergonomic furniture and smart learning tools.'],
                ['icon' => '📚', 'title' => 'Library',      'desc' => 'Books, magazines and digital resources that build reading and research habits.'],
                ['icon' => '💻', 'title' => 'Computer Lab', 'desc' => 'Modern systems with internet access to build every student’s technology skills.'],
                ['icon' => '🔬', 'title' => 'Science Labs', 'desc' => 'Well-equipped Physics, Chemistry and Biology labs for hands-on learning.'],
                ['icon' => '🚌', 'title' => 'Transport',    'desc' => 'Safe and comfortable buses covering the major routes.'],
                ['icon' => '⚽', 'title' => 'Playground',   'desc' => 'A large playground for sports, games and outdoor activities.'],
            ];
        }

        return [
            ['icon' => '🏫', 'title' => 'Classrooms',   'desc' => 'Spacious, hygienic classrooms with ergonomic furniture, green boards, digital classes and plenty of light and air.'],
            ['icon' => '📚', 'title' => 'Library',      'desc' => 'A wide collection of books, magazines and digital resources to build reading and research habits.'],
            ['icon' => '💻', 'title' => 'Computer Lab', 'desc' => 'Modern systems with internet access to build every student’s technology skills.'],
            ['icon' => '🔬', 'title' => 'Science Labs', 'desc' => 'Physics, Chemistry and Biology labs for experiments and hands-on learning.'],
            ['icon' => '📐', 'title' => 'Math Lab',     'desc' => 'Concepts learnt through practical activities and innovative tools.'],
            ['icon' => '🎨', 'title' => 'Art & Craft',  'desc' => 'Painting, drawing and craft that encourage creativity and imagination.'],
            ['icon' => '🚌', 'title' => 'Transport',    'desc' => 'Safe and comfortable buses covering the major routes.'],
            ['icon' => '⚽', 'title' => 'Playground',   'desc' => 'A large playground for sports and outdoor activities that build fitness and teamwork.'],
            ['icon' => '🌳', 'title' => 'Park',         'desc' => 'A green, well-kept park where students relax and connect with nature.'],
        ];
    }

    /**
     * Messages for the home page: the leaders' own (Leadership), else the
     * desks switched on that have one, each linking to its page.
     * [{name, role, photo, message, url}].
     */
    public static function messages(SchoolWebsite $site, array $c): array
    {
        $out = [];
        foreach ($c['leadership'] ?? [] as $m) {
            if (trim((string) ($m['message'] ?? '')) === '' && trim((string) ($m['name'] ?? '')) === '') {
                continue;
            }
            $out[] = [
                'name' => trim((string) ($m['name'] ?? '')), 'role' => trim((string) ($m['role'] ?? '')),
                'photo' => trim((string) ($m['photo'] ?? '')), 'message' => trim((string) ($m['message'] ?? '')),
                'url' => $site->isPageEnabled('leadership') ? 'leadership' : null,
            ];
        }
        if ($out) {
            return $out;
        }

        $labels = SchoolWebsite::allPages();
        foreach (self::DESKS as $slug) {
            if (! $site->isPageEnabled($slug)) {
                continue;
            }
            $page = $site->pageContent($slug);
            if (trim($page['body']) === '') {
                continue;
            }
            $out[] = ['name' => $page['heading'] !== $labels[$slug] ? $page['heading'] : '', 'role' => $labels[$slug],
                'photo' => trim((string) $page['image']), 'message' => trim($page['body']), 'url' => $slug];
        }

        return $out;
    }

    /** The people with a photo for an avatar row: the team's, the leaders', then the desks'. */
    public static function faces(array $c, int $n = 3): array
    {
        $faces = [];
        foreach (array_merge($c['team'] ?? [], $c['leadership'] ?? []) as $m) {
            $photo = trim((string) ($m['photo'] ?? ''));
            if ($photo !== '') {
                $faces[$photo] = ['photo' => $photo, 'name' => trim((string) ($m['name'] ?? ''))];
            }
        }

        return array_slice(array_values($faces), 0, $n);
    }

    /**
     * Figures for a row of counters: the stats kept in Website Data, else
     * what the school's own records say — classes, the latest pass
     * percentage, facilities, staff on the team. Two or more, or none.
     * [{value, label}].
     */
    public static function stats(array $c, SchoolSiteData $lms): array
    {
        $kept = array_values(array_filter($c['stats'] ?? [], fn ($s) => trim((string) ($s['value'] ?? '')) !== '' && trim((string) ($s['label'] ?? '')) !== ''));
        if (count($kept) >= 2) {
            return array_slice($kept, 0, 4);
        }

        $out = [];
        $classes = count($lms->classes()) ?: count($c['classes'] ?? []);
        if ($classes) {
            $out[] = ['value' => (string) $classes, 'label' => Str::plural('Class', $classes) . ' running'];
        }
        if ($latest = self::results($c['results'] ?? [])['latest']) {
            $out[] = ['value' => rtrim(rtrim(number_format($latest['pct'], 1), '0'), '.') . '%', 'label' => 'Board result ' . $latest['year']];
        }
        $facilities = count($c['facilities'] ?? []);
        if ($facilities) {
            $out[] = ['value' => $facilities . '+', 'label' => 'Facilities on campus'];
        }
        $team = count($c['team'] ?? []);
        if ($team) {
            $out[] = ['value' => $team . '+', 'label' => 'Dedicated staff'];
        }
        if (! empty($c['board'])) {
            $out[] = ['value' => $c['board'], 'label' => 'Affiliated' . (! empty($c['affiliation_no']) ? ' · ' . $c['affiliation_no'] : '')];
        }

        return count($out) >= 2 ? array_slice($out, 0, 4) : [];
    }

    /**
     * Board results by year with the pass percentage worked out (as the
     * Result page does), oldest first, and the latest year's.
     * ['rows' => [{year, appeared, passed, pct}], 'latest' => row|null].
     */
    public static function results(array $results): array
    {
        $rows = collect($results)
            ->map(function ($r) {
                $appeared = (int) ($r['appeared'] ?? 0);
                $passed   = (int) ($r['passed'] ?? 0);

                return ['year' => trim((string) ($r['year'] ?? '')) ?: '—', 'appeared' => $appeared, 'passed' => $passed,
                    'pct' => $appeared > 0 ? round($passed / $appeared * 100, 1) : 0];
            })
            ->filter(fn ($r) => $r['appeared'] > 0)
            ->sortBy('year')->values()->all();

        return ['rows' => $rows, 'latest' => $rows ? end($rows) : null];
    }

    /**
     * The school's points for a "what we stand for" list, only those it has
     * written: [{title, text, url}].
     */
    public static function points(SchoolWebsite $site, array $c): array
    {
        $on   = fn (string $slug) => $site->isPageEnabled($slug) ? $slug : null;
        $camp = $site->pageContent('campus-life');

        $points = [
            ['title' => 'Our Philosophy', 'text' => $c['philosophy'] ?? '', 'url' => $on('about')],
            ['title' => 'Our Vision', 'text' => $c['vision'] ?? '', 'url' => $on('vision') ?? $on('about')],
            ['title' => 'Our Mission', 'text' => $c['mission'] ?? '', 'url' => $on('mission') ?? $on('about')],
            ['title' => 'Our Values', 'text' => $c['values'] ?? '', 'url' => $on('values-goals')],
            ['title' => 'Our Goals', 'text' => $c['goals'] ?? '', 'url' => $on('values-goals')],
            ['title' => $camp['heading'], 'text' => $site->isPageEnabled('campus-life') ? $camp['body'] : '', 'url' => $on('campus-life')],
            ['title' => 'Our History', 'text' => $c['history_text'] ?? '', 'url' => $on('about')],
        ];

        return array_values(array_filter($points, fn ($p) => trim((string) $p['text']) !== ''));
    }

    /** "Nursery – Class XII": the first and the last of the school's classes, '' with none. */
    public static function classRange(SchoolSiteData $lms, array $c): string
    {
        $names = $lms->classNames()
            ?: array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])));
        if (! $names) {
            return '';
        }

        return count($names) > 1 ? reset($names) . ' – ' . end($names) : $names[0];
    }

    /** The next events from today (else the session's last ones), at most $n. */
    public static function upcoming(array $events, int $n = 3): array
    {
        $today = now()->toDateString();
        $next  = array_values(array_filter($events, fn ($e) => $e['date'] >= $today));

        return array_slice($next ?: array_slice($events, -$n), 0, $n);
    }

    /**
     * A month of the calendar for a card: the month of the next event (this
     * month when one is still to come in it), its weeks Sunday first, and
     * which days have events. ['label', 'weeks' => [[day|null ×7]], 'events' => [day => [titles]], 'today' => day|null].
     */
    public static function month(array $events): array
    {
        $next  = self::upcoming($events, 1)[0] ?? null;
        $first = ($next ? Carbon::parse($next['date']) : now())->startOfMonth();

        $days = [];
        foreach ($events as $e) {
            $d = Carbon::parse($e['date']);
            if ($d->isSameMonth($first)) {
                $days[$d->day][] = $e['title'];
            }
        }

        $weeks = [];
        $week  = array_fill(0, $first->dayOfWeek, null);
        for ($day = 1; $day <= $first->daysInMonth; $day++) {
            $week[] = $day;
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }
        if ($week) {
            $weeks[] = array_pad($week, 7, null);
        }

        return [
            'label'  => $first->format('F Y'),
            'weeks'  => $weeks,
            'events' => $days,
            'today'  => now()->isSameMonth($first) ? now()->day : null,
        ];
    }

    /**
     * A title with the school's name (else its last two words) picked out:
     * escaped HTML, the picked-out part in <span class="hl">.
     */
    public static function highlight(string $title, string $name = ''): string
    {
        $title = trim($title);
        $name  = trim($name);
        if ($name !== '' && ($at = mb_stripos($title, $name)) !== false) {
            return e(mb_substr($title, 0, $at)) . '<span class="hl">' . e(mb_substr($title, $at, mb_strlen($name))) . '</span>'
                . e(mb_substr($title, $at + mb_strlen($name)));
        }
        $words = preg_split('/\s+/u', $title);
        if (count($words) < 3) {
            return '<span class="hl">' . e($title) . '</span>';
        }
        $tail = array_splice($words, -2);

        return e(implode(' ', $words)) . ' <span class="hl">' . e(implode(' ', $tail)) . '</span>';
    }

    /** Text cut to about $n characters at a word, tags taken out. */
    public static function excerpt(?string $text, int $n): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text))), $n);
    }

    /** The first phone number, as a tel: target ('' with none). */
    public static function tel(array $c): string
    {
        return preg_replace('/[^0-9+]/', '', explode(',', (string) ($c['phone'] ?? ''))[0]);
    }

    /** The social links set: label => [url, icon]. */
    public static function socials(array $c): array
    {
        $out = [];
        foreach (['Facebook' => 'facebook', 'Instagram' => 'instagram', 'YouTube' => 'youtube', 'Twitter' => 'twitter', 'Telegram' => 'telegram'] as $label => $key) {
            $url = trim((string) ($c[$key] ?? ''));
            if ($url !== '') {
                $out[$label] = [$url, $key];
            }
        }

        return $out;
    }
}
