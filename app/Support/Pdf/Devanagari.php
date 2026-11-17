<?php

namespace App\Support\Pdf;

use App\Support\PdfFonts;
use FFI;

/**
 * Hindi (Devanagari) in our PDFs, printed as it is typed.
 *
 * dompdf lays text out one character after another with no OpenType shaping,
 * so "हिन्दी" printed as "हन्िदी" and "सामाजिक विज्ञान" as "सामाजकि वज्ञिान": the
 * ि after its letter instead of before it, a conjunct as two letters with a
 * halant, ृ / ु beside a letter instead of under it.
 *
 * Here every Devanagari word in a PDF's HTML is shaped by HarfBuzz — the
 * shaper browsers and phones use, loaded through FFI — against the bundled
 * Poppins, into the glyphs it is drawn with, in the order they are drawn.
 * Those glyphs are written as Private Use code points (U+E000 + glyph id),
 * which resources/fonts/add-pua-cmap.py maps in the Poppins faces. A mark
 * HarfBuzz moves (a matra under or over its letter) is written as the glyph
 * the faces carry for it at that very place (devanagari-marks.json), so a
 * word is plain text and dompdf keeps it whole; a place not in that list is
 * nudged there with position:relative instead.
 *
 * Text in another font finds those code points through
 * FallbackFontMetrics and the FAMILY face. Where FFI or HarfBuzz is missing,
 * or the font lacks a letter of a word, the HTML is left as it was.
 *
 * Every PDF goes through it: ShapedPdf, the PDF wrapper, shapes whatever HTML
 * it is given.
 */
final class Devanagari
{
    public const PUA_BASE = 0xE000;

    /** The face a shaped word falls back to in non-Poppins text (PdfFonts::devanagariCss). */
    public const FAMILY = 'LMS Devanagari';

    /** One word: Devanagari, its extensions, and the joiners that sit inside a word. */
    private const WORD = '/[\x{0900}-\x{097F}\x{A8E0}-\x{A8FF}\x{1CD0}-\x{1CFF}\x{200C}\x{200D}]+/u';

    private const ANY = '/[\x{0900}-\x{097F}\x{A8E0}-\x{A8FF}\x{1CD0}-\x{1CFF}]/u';

    /** Text inside these is not printed as text on the page. */
    private const SKIP = ['style', 'script', 'title', 'textarea'];

    private const CDEF = <<<'C'
        typedef struct hb_blob_t hb_blob_t;
        typedef struct hb_face_t hb_face_t;
        typedef struct hb_font_t hb_font_t;
        typedef struct hb_buffer_t hb_buffer_t;
        typedef union { uint32_t u32; int32_t i32; uint16_t u16[2]; int16_t i16[2]; uint8_t u8[4]; int8_t i8[4]; } hb_var_int_t;
        typedef struct { uint32_t codepoint; uint32_t mask; uint32_t cluster; hb_var_int_t var1; hb_var_int_t var2; } hb_glyph_info_t;
        typedef struct { int32_t x_advance; int32_t y_advance; int32_t x_offset; int32_t y_offset; hb_var_int_t var; } hb_glyph_position_t;
        typedef struct { uint32_t tag; uint32_t value; unsigned int start; unsigned int end; } hb_feature_t;
        hb_blob_t* hb_blob_create_from_file(const char* file_name);
        void hb_blob_destroy(hb_blob_t* blob);
        hb_face_t* hb_face_create(hb_blob_t* blob, unsigned int index);
        void hb_face_destroy(hb_face_t* face);
        unsigned int hb_face_get_upem(const hb_face_t* face);
        unsigned int hb_face_get_glyph_count(const hb_face_t* face);
        hb_font_t* hb_font_create(hb_face_t* face);
        void hb_font_destroy(hb_font_t* font);
        int32_t hb_font_get_glyph_h_advance(hb_font_t* font, uint32_t glyph);
        int hb_font_get_nominal_glyph(hb_font_t* font, uint32_t unicode, uint32_t* glyph);
        hb_buffer_t* hb_buffer_create(void);
        void hb_buffer_destroy(hb_buffer_t* buffer);
        void hb_buffer_add_utf8(hb_buffer_t* buffer, const char* text, int text_length, unsigned int item_offset, int item_length);
        void hb_buffer_guess_segment_properties(hb_buffer_t* buffer);
        void hb_shape(hb_font_t* font, hb_buffer_t* buffer, const hb_feature_t* features, unsigned int num_features);
        hb_glyph_info_t* hb_buffer_get_glyph_infos(hb_buffer_t* buffer, unsigned int* length);
        hb_glyph_position_t* hb_buffer_get_glyph_positions(hb_buffer_t* buffer, unsigned int* length);
        C;

    /** The loaded library (false once it is known not to load). */
    private static FFI|false|null $ffi = null;

    /** word => its glyphs' HTML (false when it cannot be shaped), for this process. */
    private static array $memo = [];

    /** Whether the text holds any Devanagari. */
    public static function in(string $text): bool
    {
        return (bool) preg_match(self::ANY, $text);
    }

    /** Whether HarfBuzz can be used here (FFI on, the library found). */
    public static function available(): bool
    {
        return self::lib() !== null;
    }

    /**
     * The HTML with every Devanagari word of its text shaped, and the
     * fallback face added when anything was; as it was when there is no
     * Devanagari or no shaper.
     */
    public static function html(string $html): string
    {
        if (!self::in($html) || !($ffi = self::lib())) {
            return $html;
        }

        $font = self::open($ffi);
        if ($font === null) {
            return $html;
        }

        try {
            $parts = preg_split('/(<!--.*?-->|<[^>]*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
            $skip = null;
            $shaped = false;
            foreach ($parts as $i => $part) {
                if ($i % 2 === 1) { // a tag or a comment
                    if ($skip === null && preg_match('#^<(' . implode('|', self::SKIP) . ')\b#i', $part, $m)) {
                        $skip = strtolower($m[1]);
                    } elseif ($skip !== null && preg_match('#^</' . $skip . '\s*>#i', $part)) {
                        $skip = null;
                    }
                    continue;
                }
                if ($skip !== null || !self::in($part)) {
                    continue;
                }
                // Token by token (what lies between spaces), so a word keeps
                // the comma or bracket stuck to it on its line.
                $parts[$i] = preg_replace_callback('/\S+/u', function ($t) use ($ffi, $font, &$shaped) {
                    if (!self::in($t[0])) {
                        return $t[0];
                    }
                    $done = false;
                    // The Devanagari shaped; an entity kept; any other
                    // character (a comma, a bracket) as the same face's glyph,
                    // so the token is one run of one font that dompdf does
                    // not split, nor break, at the change of font.
                    $inner = preg_replace_callback('/(&#?\w+;)|(' . substr(self::WORD, 1, -2) . ')|(.)/su', function ($w) use ($ffi, $font, &$done) {
                        if (($w[2] ?? '') !== '') {
                            $glyphs = self::$memo[$w[2]] ??= (self::shape($ffi, $font, $w[2]) ?? false);
                            if ($glyphs === false) {
                                return $w[2];
                            }
                            $done = true;

                            return $glyphs;
                        }
                        if (($w[3] ?? '') !== '') {
                            $gid = $ffi->new('uint32_t');
                            if ($ffi->hb_font_get_nominal_glyph($font['font'], mb_ord($w[3], 'UTF-8'), FFI::addr($gid))
                                && $gid->cdata > 0 && self::PUA_BASE + $gid->cdata <= 0xF8FF) {
                                return mb_chr(self::PUA_BASE + $gid->cdata, 'UTF-8');
                            }
                        }

                        return $w[0];
                    }, $t[0]);
                    if (!$done) {
                        return $t[0];
                    }
                    $shaped = true;

                    // Whole on its line, and no letter-spacing: it would pull a matra off its letter.
                    return '<span style="white-space:nowrap;letter-spacing:0;word-spacing:0">' . $inner . '</span>';
                }, $part);
            }
        } finally {
            self::close($ffi, $font);
        }

        if (!$shaped) {
            return $html;
        }

        $out = implode('', $parts);
        $css = '<style>' . PdfFonts::devanagariCss() . '</style>';
        $at = stripos($out, '</head>');

        return $at === false ? $css . $out : substr_replace($out, $css, $at, 0);
    }

    /**
     * One word as the glyphs HarfBuzz draws it with, or null when the font
     * lacks one of its letters (then dompdf prints it as it always did).
     */
    private static function shape(FFI $ffi, array $font, string $word): ?string
    {
        $buffer = $ffi->hb_buffer_create();
        try {
            $ffi->hb_buffer_add_utf8($buffer, $word, strlen($word), 0, -1);
            $ffi->hb_buffer_guess_segment_properties($buffer);
            $ffi->hb_shape($font['font'], $buffer, null, 0);

            $count = $ffi->new('unsigned int');
            $infos = $ffi->hb_buffer_get_glyph_infos($buffer, FFI::addr($count));
            $positions = $ffi->hb_buffer_get_glyph_positions($buffer, FFI::addr($count));

            $html = '';
            for ($i = 0; $i < $count->cdata; $i++) {
                $gid = (int) $infos[$i]->codepoint;
                if ($gid === 0 || $gid >= $font['glyphs'] || self::PUA_BASE + $gid > 0xF8FF) {
                    return null;
                }
                $glyph = mb_chr(self::PUA_BASE + $gid, 'UTF-8');
                $p = $positions[$i];
                // dompdf moves on by the glyph's own advance; HarfBuzz may
                // give another, and may move the glyph off its spot (a mark).
                $dx = $p->x_advance - $ffi->hb_font_get_glyph_h_advance($font['font'], $gid);
                if ($p->x_offset === 0 && $p->y_offset === 0 && $dx === 0) {
                    $html .= $glyph;
                    continue;
                }
                // A mark at a place the faces carry a glyph for: that glyph.
                $placed = $dx === 0 ? (self::placed($font)["{$gid}:{$p->x_offset}:{$p->y_offset}"] ?? null) : null;
                if ($placed !== null) {
                    $html .= mb_chr(self::PUA_BASE + $placed, 'UTF-8');
                    continue;
                }
                $style = 'position:relative;left:' . self::em($p->x_offset, $font) . ';top:' . self::em(-$p->y_offset, $font);
                if ($dx !== 0) {
                    $style .= ';margin-right:' . self::em($dx, $font);
                }
                $html .= '<span style="' . $style . '">' . $glyph . '</span>';
            }
        } finally {
            $ffi->hb_buffer_destroy($buffer);
        }

        return $html;
    }

    /** "glyph:x:y" => the glyph id of that mark placed there (resources/fonts/devanagari-marks.json). */
    private static ?array $placed = null;

    /**
     * The placed marks the faces carry, read from the list they were built
     * from — none when the font does not hold them (then marks are nudged).
     */
    private static function placed(array $font): array
    {
        if (self::$placed === null) {
            self::$placed = [];
            $list = json_decode((string) @file_get_contents(resource_path('fonts/devanagari-marks.json')), true);
            $marks = (array) ($list['marks'] ?? []);
            $base = (int) ($list['glyphs'] ?? 0);
            if ($base > 0 && $font['glyphs'] === $base + count($marks)) {
                foreach (array_values($marks) as $i => [$gid, $x, $y]) {
                    self::$placed["{$gid}:{$x}:{$y}"] = $base + $i;
                }
            }
        }

        return self::$placed;
    }

    private static function em(int $units, array $font): string
    {
        return rtrim(rtrim(number_format($units / $font['upem'], 4, '.', ''), '0'), '.') . 'em';
    }

    /** The shaping font: Poppins Regular, whose glyph ids the other faces share. */
    private static function open(FFI $ffi): ?array
    {
        $path = resource_path('fonts/Poppins-Regular.ttf');
        if (!is_file($path)) {
            return null;
        }
        $blob = $ffi->hb_blob_create_from_file($path);
        $face = $ffi->hb_face_create($blob, 0);
        $glyphs = (int) $ffi->hb_face_get_glyph_count($face);
        $font = ['blob' => $blob, 'face' => $face, 'font' => $ffi->hb_font_create($face),
                 'upem' => max(1, (int) $ffi->hb_face_get_upem($face)), 'glyphs' => $glyphs];
        if ($glyphs === 0) {
            self::close($ffi, $font);

            return null;
        }

        return $font;
    }

    private static function close(FFI $ffi, array $font): void
    {
        $ffi->hb_font_destroy($font['font']);
        $ffi->hb_face_destroy($font['face']);
        $ffi->hb_blob_destroy($font['blob']);
    }

    private static function lib(): ?FFI
    {
        if (self::$ffi === null) {
            try {
                self::$ffi = extension_loaded('ffi')
                    ? FFI::cdef(self::CDEF, (string) config('services.harfbuzz.lib', 'libharfbuzz.so.0'))
                    : false;
            } catch (\Throwable $e) {
                self::$ffi = false;
                logger()->warning('Hindi in PDFs is printed unshaped: HarfBuzz did not load (' . $e->getMessage() . ')');
            }
        }

        return self::$ffi ?: null;
    }
}
