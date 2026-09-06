<?php

namespace App\Support;

/**
 * Built-in subject icons.
 *
 * Every subject gets a flat coloured tile with a white glyph, picked from its
 * NAME rather than an uploaded file — so "Hindi", "HINDI", " hindi " and
 * "Hindi Language" all land on the same icon, and a subject nobody has an icon
 * for still shows something sensible.
 *
 * Glyphs are stroked line art on a 24x24 grid, drawn in currentColor so the
 * tile can paint them white.
 */
class SubjectIcons
{
    /** Key used when a subject name matches nothing below. */
    public const FALLBACK = 'other';

    /**
     * Alias → icon key. Compared against the NORMALIZED name (see normalize()),
     * so only lowercase, single-spaced entries belong here.
     */
    private const ALIASES = [
        // ── Maths ──
        'mathematics' => 'mathematics', 'maths' => 'mathematics', 'math' => 'mathematics',
        'ganit' => 'mathematics', 'applied mathematics' => 'mathematics',
        'basic mathematics' => 'mathematics', 'advanced mathematics' => 'mathematics',

        // ── Sciences ──
        'physics' => 'physics',
        'chemistry' => 'chemistry',
        'biology' => 'biology', 'bio' => 'biology', 'botany' => 'biology', 'zoology' => 'biology',
        'science' => 'science', 'general science' => 'science', 'vigyan' => 'science',
        'environmental studies' => 'environment', 'environmental study' => 'environment',
        'environment studies' => 'environment', 'environmental education' => 'environment',
        'evs' => 'environment', 'environmental science' => 'environment',
        'environment science' => 'environment', 'environment' => 'environment',
        'astronomy' => 'astronomy', 'space science' => 'astronomy',

        // ── Languages ──
        'english' => 'english', 'english language' => 'english', 'english literature' => 'literature',
        'hindi' => 'hindi', 'hindi language' => 'hindi', 'hindi literature' => 'hindi',
        'sanskrit' => 'sanskrit', 'sanskrit language' => 'sanskrit',
        'urdu' => 'language', 'punjabi' => 'language', 'marathi' => 'language',
        'gujarati' => 'language', 'bengali' => 'language', 'tamil' => 'language',
        'telugu' => 'language', 'kannada' => 'language', 'malayalam' => 'language',
        'odia' => 'language', 'assamese' => 'language', 'french' => 'language',
        'german' => 'language', 'spanish' => 'language', 'arabic' => 'language',
        'regional language' => 'language', 'third language' => 'language',
        'literature' => 'literature',

        // ── Social ──
        'history' => 'history', 'itihas' => 'history',
        'geography' => 'geography', 'bhugol' => 'geography',
        'civics' => 'civics', 'political science' => 'civics', 'polity' => 'civics',
        'social science' => 'social science', 'social studies' => 'social science',
        'sst' => 'social science', 'samajik vigyan' => 'social science',
        'sociology' => 'sociology',
        'psychology' => 'psychology',
        'philosophy' => 'philosophy',
        'humanities' => 'humanities',

        // ── Commerce ──
        'economics' => 'economics', 'arthashastra' => 'economics',
        'business studies' => 'business', 'business study' => 'business',
        'business' => 'business', 'business administration' => 'business',
        'commerce' => 'business', 'entrepreneurship' => 'business',
        'accountancy' => 'accountancy', 'accounts' => 'accountancy', 'accounting' => 'accountancy',
        'book keeping' => 'accountancy', 'bookkeeping' => 'accountancy',

        // ── Computing ──
        'computer science' => 'computer', 'computer' => 'computer', 'computers' => 'computer',
        'computer application' => 'computer', 'computer applications' => 'computer',
        'informatics' => 'computer', 'informatics practices' => 'computer',
        'information technology' => 'computer', 'it' => 'computer', 'coding' => 'computer',

        // ── Arts & activity ──
        'art' => 'art', 'arts' => 'art', 'fine art' => 'art', 'fine arts' => 'art',
        'drawing' => 'art', 'drawings' => 'art', 'drawing and painting' => 'art',
        'painting' => 'art', 'craft' => 'art', 'art and craft' => 'art',
        'art craft' => 'art', 'creative art' => 'art',
        'music' => 'music', 'sangeet' => 'music', 'dance' => 'music', 'vocal music' => 'music',
        'physical education' => 'physical education', 'pe' => 'physical education',
        'pt' => 'physical education', 'sports' => 'physical education',
        'games' => 'physical education', 'yoga' => 'physical education',
        'health and physical education' => 'physical education',

        // ── Values / misc ──
        'moral science' => 'moral science', 'value education' => 'moral science',
        'moral education' => 'moral science', 'general knowledge' => 'general knowledge',
        'gk' => 'general knowledge', 'library' => 'literature',
    ];

    /**
     * Icon definitions: colour of the tile plus the glyph path markup.
     * The glyph is drawn with stroke="currentColor" on a 24x24 viewBox.
     */
    private const ICONS = [
        'mathematics' => ['#0f9b8e', '<path d="M4 7h16"/><path d="M9 7v11"/><path d="M16 7v9a2 2 0 0 0 2 2"/>'],
        'physics'     => ['#5a4a9f', '<circle cx="12" cy="12" r="2.2"/><ellipse cx="12" cy="12" rx="9.5" ry="4"/><ellipse cx="12" cy="12" rx="9.5" ry="4" transform="rotate(60 12 12)"/><ellipse cx="12" cy="12" rx="9.5" ry="4" transform="rotate(120 12 12)"/>'],
        'chemistry'   => ['#7b4fa0', '<path d="M9 3h6"/><path d="M10 3v6.2L4.9 18.6A1.6 1.6 0 0 0 6.3 21h11.4a1.6 1.6 0 0 0 1.4-2.4L14 9.2V3"/><path d="M7.2 15h9.6"/>'],
        'biology'     => ['#e05263', '<path d="M7 3c0 4.5 10 5.5 10 10S7 18.5 7 21"/><path d="M17 3c0 4.5-10 5.5-10 10s10 5.5 10 8"/><path d="M8.5 7h7"/><path d="M8.5 17h7"/>'],
        'literature'  => ['#3f8f5b', '<path d="M12 6.5C10.5 5.2 8.6 4.5 6 4.5H4v13h2c2.6 0 4.5.7 6 2"/><path d="M12 6.5c1.5-1.3 3.4-2 6-2h2v13h-2c-2.6 0-4.5.7-6 2"/><path d="M12 6.5v13"/>'],
        'history'     => ['#c9a227', '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.2 2"/>'],
        'geography'   => ['#2f6f8f', '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><ellipse cx="12" cy="12" rx="4" ry="8.5"/>'],
        'english'     => ['#2f7fbf', '<path d="M6.5 19 12 5l5.5 14"/><path d="M8.7 14.2h6.6"/>'],
        'hindi'       => ['#e07a3f', '<text x="12" y="17.5" text-anchor="middle" font-size="15" font-family="Nirmala UI, Noto Sans Devanagari, system-ui, sans-serif" fill="#ffffff" stroke="none">अ</text>'],
        'sanskrit'    => ['#b4553a', '<text x="12" y="17.5" text-anchor="middle" font-size="15" font-family="Nirmala UI, Noto Sans Devanagari, system-ui, sans-serif" fill="#ffffff" stroke="none">ॐ</text>'],
        'language'    => ['#4a8fb0', '<path d="M3.5 6h9"/><path d="M8 4v2c0 4-2 6.5-4.5 8"/><path d="M6 11c1.5 2 3.4 3.2 5.5 4"/><path d="M12.5 21l4.2-10 4.3 10"/><path d="M14.2 17.2h5"/>'],
        'science'     => ['#3f8f7a', '<path d="M6 21h9"/><path d="M10 21V9"/><path d="m8.5 9 4-5.5 3.5 2.5-4 5.5z"/><path d="M13 12.5 16.5 15"/><path d="M4 17.5c2-2.5 4-2.5 6-1"/>'],
        'environment' => ['#4f9e4f', '<path d="M20 4c0 9-5.5 13-11 13"/><path d="M20 4C10 4 5 8 5 13.5 5 16 6.5 17 9 17"/><path d="M4.5 20c1.5-3 3.5-5 6.5-6.5"/>'],
        'astronomy'   => ['#3a5a8c', '<path d="m3.5 16.5 12-9.5 3.5 4.5-13 8z"/><path d="m7 14 3 4"/><path d="M15.5 7 18 4"/><path d="M12.5 20.5 15 17"/>'],
        'social science' => ['#7a5aa8', '<circle cx="12" cy="6" r="2.5"/><circle cx="5.5" cy="17" r="2.5"/><circle cx="18.5" cy="17" r="2.5"/><path d="M10.3 7.8 7.2 14.8"/><path d="m13.7 7.8 3.1 7"/><path d="M8 17h8"/>'],
        'civics'      => ['#8c6b3f', '<path d="M3.5 9.5 12 4.5l8.5 5"/><path d="M5.5 9.5v8"/><path d="M10 9.5v8"/><path d="M14 9.5v8"/><path d="M18.5 9.5v8"/><path d="M3 20.5h18"/>'],
        'sociology'   => ['#4f9a92', '<path d="M3.5 7.5A2.5 2.5 0 0 1 6 5h7a2.5 2.5 0 0 1 2.5 2.5v3A2.5 2.5 0 0 1 13 13H8l-4.5 3z"/><path d="M18 10.5h.5a2.5 2.5 0 0 1 2.5 2.5v3a2.5 2.5 0 0 1-2.5 2.5H14l-2.5 2v-2"/>'],
        'psychology'  => ['#5f7fbf', '<path d="M15.5 20.5v-3c3-1 5-3.6 5-6.8C20.5 6.4 16.7 3 12 3S3.5 6.4 3.5 10.7c0 2.2 1 4.2 2.6 5.6v4.2"/><path d="M13.5 8.2a2.3 2.3 0 1 0-2.6 3.6 2 2 0 0 1 .3 3.2"/>'],
        'philosophy'  => ['#8f5f8f', '<path d="M14.5 21v-2.6c3.4-1 5.8-3.9 5.8-7.3C20.3 6.6 16.6 3 12 3S3.7 6.6 3.7 11.1c0 2.3 1 4.4 2.6 5.8V21"/><circle cx="12" cy="10.5" r="2.6"/><path d="M12 13.1V16"/>'],
        'humanities'  => ['#c96f4a', '<circle cx="12" cy="5.5" r="2.5"/><path d="M12 8v7"/><path d="m4.5 9.5 7.5 2 7.5-2"/><path d="m8.5 21 3.5-6 3.5 6"/>'],
        'economics'   => ['#2f8f6f', '<path d="M3.5 20.5h17"/><path d="M4 16.5 9.5 11l4 3.5 6.5-7"/><path d="M20 7.5h-4"/><path d="M20 7.5v4"/>'],
        'business'    => ['#2f6f9f', '<path d="M3.5 20.5h17"/><rect x="4.5" y="12" width="4" height="8.5" rx="1"/><rect x="10" y="8" width="4" height="12.5" rx="1"/><rect x="15.5" y="4.5" width="4" height="16" rx="1"/>'],
        'accountancy' => ['#6f6f9f', '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M8.5 7.5h7"/><path d="M8.5 12h.01"/><path d="M12 12h.01"/><path d="M15.5 12h.01"/><path d="M8.5 16.5h.01"/><path d="M12 16.5h.01"/><path d="M15.5 16.5h.01"/>'],
        'computer'    => ['#3a6ea5', '<rect x="2.5" y="4" width="19" height="12.5" rx="2"/><path d="M8 20.5h8"/><path d="M12 16.5v4"/><path d="m9 8-2.2 2.2L9 12.5"/><path d="m15 8 2.2 2.2L15 12.5"/>'],
        'art'         => ['#d4645f', '<path d="M12 3.2c-4.9 0-8.8 3.7-8.8 8.3 0 4.6 3.9 8.3 8.8 8.3 1.5 0 2.4-.9 2.4-2 0-.6-.2-1-.6-1.4-.4-.4-.6-.8-.6-1.4 0-1.1.9-2 2-2h1.4c2.3 0 4.2-1.8 4.2-4 0-3.2-3.9-5.8-8.8-5.8z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15" cy="8" r="1"/>'],
        'music'       => ['#6f5fa8', '<path d="M9 18V5.5l10-2V16"/><ellipse cx="6.5" cy="18" rx="2.5" ry="2"/><ellipse cx="16.5" cy="16" rx="2.5" ry="2"/><path d="M9 9.5l10-2"/>'],
        'physical education' => ['#e08a3c', '<circle cx="12" cy="12" r="8.5"/><path d="M3.6 12h16.8"/><path d="M12 3.5c3 2.5 4.5 5.5 4.5 8.5S15 18 12 20.5"/><path d="M12 3.5C9 6 7.5 9 7.5 12S9 18 12 20.5"/>'],
        'moral science' => ['#c95f7a', '<path d="M12 20.5S3.5 15.5 3.5 9.6A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 8.5 2.6c0 5.9-8.5 10.9-8.5 10.9z"/>'],
        'general knowledge' => ['#d1a03c', '<path d="M9.5 18h5"/><path d="M10 21h4"/><path d="M8 13.5a5 5 0 1 1 8 0c-.8 1-1.2 1.7-1.3 3h-5.4c-.1-1.3-.5-2-1.3-3z"/>'],
        self::FALLBACK => ['#7c8794', '<path d="M5 4.5A1.5 1.5 0 0 1 6.5 3h11A1.5 1.5 0 0 1 19 4.5v16l-7-3.5-7 3.5z"/>'],
    ];

    /**
     * Fold a subject name to its comparison form: lower case, accents left
     * alone, punctuation dropped, whitespace collapsed. "  HINDI-Language "
     * and "hindi language" both come out as "hindi language".
     */
    public static function normalize(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        // Punctuation, digits and separators are noise for matching.
        $name = preg_replace('/[^\p{L}\s]+/u', ' ', $name) ?? '';
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';

        return trim($name);
    }

    /** Icon key for a subject name, falling back to the generic book. */
    public static function keyFor(?string $name): string
    {
        $normalized = self::normalize($name);

        if ($normalized === '') {
            return self::FALLBACK;
        }

        if (isset(self::ALIASES[$normalized])) {
            return self::ALIASES[$normalized];
        }

        // Dotted or spaced initialisms — "E.V.S.", "S.S.T.", "P. T." — normalize
        // to single letters separated by spaces. Join them back up and retry.
        if (preg_match('/^(?:\p{L}\s)+\p{L}$/u', $normalized)) {
            $joined = str_replace(' ', '', $normalized);

            if (isset(self::ALIASES[$joined])) {
                return self::ALIASES[$joined];
            }

            if (isset(self::ICONS[$joined])) {
                return $joined;
            }
        }

        // A named icon used verbatim ("Physics" → 'physics').
        if (isset(self::ICONS[$normalized])) {
            return $normalized;
        }

        // Longest alias contained in the name, so "Applied Physics Theory" and
        // "Hindi (Second Language)" still resolve. Longest first keeps
        // "social science" from being beaten by "science".
        $aliases = array_keys(self::ALIASES);
        usort($aliases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($aliases as $alias) {
            if (preg_match('/\b' . preg_quote($alias, '/') . '\b/u', $normalized)) {
                return self::ALIASES[$alias];
            }
        }

        return self::FALLBACK;
    }

    /** Tile colour for a subject name. */
    public static function color(?string $name): string
    {
        return self::ICONS[self::keyFor($name)][0];
    }

    /** Glyph markup (paths only) for a subject name. */
    public static function glyph(?string $name): string
    {
        return self::ICONS[self::keyFor($name)][1];
    }

    /** True when this key is one we actually draw. */
    public static function hasKey(string $key): bool
    {
        return isset(self::ICONS[$key]);
    }

    /**
     * A complete, standalone SVG document for one icon key — the form served
     * over HTTP so the mobile app can use the same artwork as the web panel.
     */
    public static function svgDocument(string $key, int $size = 96): string
    {
        $key   = self::hasKey($key) ? $key : self::FALLBACK;
        [$color, $glyph] = self::ICONS[$key];

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32">'
            . '<rect width="32" height="32" rx="6" fill="' . $color . '"/>'
            . '<g transform="translate(4 4)" fill="none" stroke="#ffffff" stroke-width="1.7" '
            . 'stroke-linecap="round" stroke-linejoin="round">' . $glyph . '</g>'
            . '</svg>';
    }

    /** Every drawable key, for tooling and tests. */
    public static function keys(): array
    {
        return array_keys(self::ICONS);
    }
}
