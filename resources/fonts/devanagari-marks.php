<?php

/*
 * Lists every place HarfBuzz moves a Devanagari mark to in Poppins (a matra
 * under or over its letter, an anusvara over a reph…), so add-pua-cmap.py can
 * give each its own pre-placed glyph. App\Support\Pdf\Devanagari then writes
 * that glyph instead of nudging the mark with a positioned span — dompdf could
 * break a line inside such a word, and keeps it whole as plain text.
 *
 * Shapes every consonant (and nukta form) and independent vowel with every
 * matra and sign, every two-consonant conjunct, reph and rakar forms and the
 * three-consonant conjuncts, against Poppins Regular (the faces share one
 * glyph order and one set of layout tables).
 *
 * Needs PHP with FFI and a HarfBuzz library:
 *   php -d extension=ffi resources/fonts/devanagari-marks.php <harfbuzz .so/.dll> > resources/fonts/devanagari-marks.json
 * then run add-pua-cmap.py on the original Poppins faces.
 */

$lib  = $argv[1] ?? 'libharfbuzz.so.0';
$font = $argv[2] ?? __DIR__ . '/Poppins-Regular.ttf';

$ffi = FFI::cdef(<<<'C'
    typedef struct hb_blob_t hb_blob_t;
    typedef struct hb_face_t hb_face_t;
    typedef struct hb_font_t hb_font_t;
    typedef struct hb_buffer_t hb_buffer_t;
    typedef union { uint32_t u32; int32_t i32; uint16_t u16[2]; int16_t i16[2]; uint8_t u8[4]; int8_t i8[4]; } hb_var_int_t;
    typedef struct { uint32_t codepoint; uint32_t mask; uint32_t cluster; hb_var_int_t var1; hb_var_int_t var2; } hb_glyph_info_t;
    typedef struct { int32_t x_advance; int32_t y_advance; int32_t x_offset; int32_t y_offset; hb_var_int_t var; } hb_glyph_position_t;
    typedef struct { uint32_t tag; uint32_t value; unsigned int start; unsigned int end; } hb_feature_t;
    hb_blob_t* hb_blob_create_from_file(const char* file_name);
    hb_face_t* hb_face_create(hb_blob_t* blob, unsigned int index);
    unsigned int hb_face_get_glyph_count(const hb_face_t* face);
    hb_font_t* hb_font_create(hb_face_t* face);
    int32_t hb_font_get_glyph_h_advance(hb_font_t* font, uint32_t glyph);
    hb_buffer_t* hb_buffer_create(void);
    void hb_buffer_destroy(hb_buffer_t* buffer);
    void hb_buffer_add_utf8(hb_buffer_t* buffer, const char* text, int text_length, unsigned int item_offset, int item_length);
    void hb_buffer_guess_segment_properties(hb_buffer_t* buffer);
    void hb_shape(hb_font_t* font, hb_buffer_t* buffer, const hb_feature_t* features, unsigned int num_features);
    hb_glyph_info_t* hb_buffer_get_glyph_infos(hb_buffer_t* buffer, unsigned int* length);
    hb_glyph_position_t* hb_buffer_get_glyph_positions(hb_buffer_t* buffer, unsigned int* length);
    C, $lib);

$face = $ffi->hb_face_create($ffi->hb_blob_create_from_file($font), 0);
$hb   = $ffi->hb_font_create($face);

$seen = [];
$shape = function (string $text) use ($ffi, $hb, &$seen) {
    $buf = $ffi->hb_buffer_create();
    $ffi->hb_buffer_add_utf8($buf, $text, strlen($text), 0, -1);
    $ffi->hb_buffer_guess_segment_properties($buf);
    $ffi->hb_shape($hb, $buf, null, 0);
    $n = $ffi->new('unsigned int');
    $info = $ffi->hb_buffer_get_glyph_infos($buf, FFI::addr($n));
    $pos = $ffi->hb_buffer_get_glyph_positions($buf, FFI::addr($n));
    for ($i = 0; $i < $n->cdata; $i++) {
        $g = (int) $info[$i]->codepoint;
        $p = $pos[$i];
        // Only a move: a changed advance is left to the positioned span.
        if (($p->x_offset || $p->y_offset) && $p->x_advance === $ffi->hb_font_get_glyph_h_advance($hb, $g)) {
            $seen["{$g}:{$p->x_offset}:{$p->y_offset}"] = [$g, $p->x_offset, $p->y_offset];
        }
    }
    $ffi->hb_buffer_destroy($buf);
};

$chr = fn (int $cp) => mb_chr($cp, 'UTF-8');
$consonants = array_map($chr, range(0x0915, 0x0939));
foreach ([0x0958, 0x0959, 0x095A, 0x095B, 0x095C, 0x095D, 0x095E, 0x095F, 0x0979, 0x097A, 0x097B, 0x097C, 0x097E, 0x097F] as $cp) {
    $consonants[] = $chr($cp);
}
// Consonants with a separate nukta too, as many keyboards type them.
foreach (['क', 'ख', 'ग', 'ज', 'ड', 'ढ', 'फ', 'य', 'र', 'न'] as $c) {
    $consonants[] = $c . '़';
}
$vowels  = array_merge(array_map($chr, range(0x0904, 0x0914)), array_map($chr, [0x0960, 0x0961, 0x0972, 0x0973, 0x0974, 0x0975, 0x0976, 0x0977]));
$matras  = array_merge([''], array_map($chr, array_merge(range(0x093A, 0x093B), range(0x093E, 0x094C), [0x094E, 0x094F], range(0x0955, 0x0957), [0x0962, 0x0963])));
$signs   = ['', 'ं', 'ँ', 'ः', '़', 'ॕ'];
$h = '्';

foreach ($consonants as $c) {
    foreach ($matras as $m) {
        foreach ($signs as $s) {
            $shape($c . $m . $s);
            $shape('र' . $h . $c . $m . $s);   // reph
            $shape($c . $h . 'र' . $m . $s);   // rakar
        }
    }
    $shape($c . $h);
}
foreach ($vowels as $v) {
    foreach ($signs as $s) {
        $shape($v . $s);
    }
}
foreach ($consonants as $a) {
    foreach ($consonants as $b) {
        foreach ($matras as $m) {
            foreach (['', 'ं', 'ँ'] as $s) {
                $shape($a . $h . $b . $m . $s);
            }
        }
        $shape('र' . $h . $a . $h . $b . 'ि');
        $shape('र' . $h . $a . $h . $b . 'ं');
        $shape($a . $h . $b . $h . 'र' . 'ि');
        $shape($a . $h . $b . $h . 'र' . 'ु');
        $shape($a . $h . $b . $h);
    }
}
$plain = array_map($chr, range(0x0915, 0x0939));
foreach ($plain as $a) {
    foreach ($plain as $b) {
        foreach ($plain as $c) {
            foreach (['', 'ि', 'ु', 'ू', 'ृ', 'े', 'ं'] as $m) {
                $shape($a . $h . $b . $h . $c . $m);
            }
        }
    }
}

$marks = array_values($seen);
usort($marks, fn ($x, $y) => $x <=> $y);

echo json_encode([
    'about'  => 'Pre-placed Devanagari marks for the Poppins faces: resources/fonts/devanagari-marks.php lists them, add-pua-cmap.py adds them as glyphs from "glyphs" on, in this order.',
    'glyphs' => (int) $ffi->hb_face_get_glyph_count($face),
    'marks'  => $marks,
], JSON_UNESCAPED_UNICODE) . "\n";
