<?php

namespace Tests\Feature;

use App\Support\Pdf\Devanagari;
use App\Support\Pdf\FallbackFontMetrics;
use App\Support\Pdf\ShapedPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

/**
 * Hindi in our PDFs prints as it is typed: every Devanagari word of a PDF's
 * HTML is shaped by HarfBuzz into the glyphs it is drawn with (ि before its
 * letter, conjuncts joined, ृ under its letter as a pre-placed glyph), written
 * as the Poppins faces' Private Use code points. Tags, attributes, <title> and
 * <style> are left alone; HTML without Hindi goes through untouched.
 *
 * The shaping tests need FFI and a HarfBuzz library (the Docker image has
 * both; elsewhere set HARFBUZZ_LIB to a harfbuzz .so / .dll) and are skipped
 * without them.
 */
class HindiPdfTest extends TestCase
{
    /** U+E000 + glyph id, as the shaper writes a glyph. */
    private static function g(int ...$gids): string
    {
        return implode('', array_map(fn ($g) => mb_chr(0xE000 + $g, 'UTF-8'), $gids));
    }

    private function needsHarfBuzz(): void
    {
        if (!Devanagari::available()) {
            $this->markTestSkipped('FFI or HarfBuzz is not available here (set HARFBUZZ_LIB).');
        }
    }

    public function test_html_without_hindi_is_untouched(): void
    {
        $html = '<html><head><title>Report</title></head><body><p>English only &amp; 100%</p></body></html>';

        $this->assertSame($html, Devanagari::html($html));
        $this->assertInstanceOf(ShapedPdf::class, Pdf::loadHTML($html));
    }

    public function test_a_word_is_drawn_in_the_order_and_glyphs_it_is_written_with(): void
    {
        $this->needsHarfBuzz();
        $out = Devanagari::html('<p>हिन्दी</p>');

        // ि (its variant, 636) first, then ह, the न्द conjunct as one glyph, ी.
        $this->assertStringContainsString(self::g(636, 68, 413, 23), $out);
        $this->assertStringNotContainsString('हिन्दी', $out);
    }

    public function test_a_mark_moved_under_its_letter_is_one_pre_placed_glyph(): void
    {
        $this->needsHarfBuzz();
        $out = Devanagari::html('<td>कृष्ण</td>');

        // क, ृ placed under it, the ष्ण conjunct: three plain characters, no positioned span.
        $this->assertStringNotContainsString('position:relative', $out);
        preg_match('#<span[^>]*>(.*?)</span>#u', $out, $m);
        $chars = mb_str_split($m[1]);
        $this->assertCount(3, $chars);
        $this->assertSame(self::g(36), $chars[0]);
        $this->assertGreaterThanOrEqual(0xE000 + 1060, mb_ord($chars[1]));   // a placed mark
        $this->assertSame(self::g(562), $chars[2]);
    }

    public function test_only_page_text_is_shaped_and_the_face_is_added(): void
    {
        $this->needsHarfBuzz();
        $html = '<html><head><title>हिन्दी</title><style>.a{content:"हिन्दी"}</style></head>'
            . '<body><img alt="हिन्दी" src="x.png"><p class="a" title="हिन्दी">विषय: हिन्दी, गणित</p><!-- हिन्दी --></body></html>';
        $out = Devanagari::html($html);

        $this->assertStringContainsString('<title>हिन्दी</title>', $out);
        $this->assertStringContainsString('content:"हिन्दी"', $out);
        $this->assertStringContainsString('alt="हिन्दी"', $out);
        $this->assertStringContainsString('title="हिन्दी"', $out);
        $this->assertStringContainsString('<!-- हिन्दी -->', $out);
        $this->assertStringNotContainsString('>विषय: हिन्दी, गणित<', $out);

        // A comma stuck to a word goes with it, in the same face, on its line.
        $this->assertStringContainsString(self::g(636, 68, 413, 23) . self::g(671) . '</span>', $out);

        // The fallback face, once, in the head.
        $this->assertSame(1, substr_count($out, '<style>@font-face{font-family:\'' . Devanagari::FAMILY . "'"));
        $this->assertLessThan(strpos($out, '</head>'), strpos($out, Devanagari::FAMILY));
    }

    public function test_the_pdf_draws_the_shaped_glyphs_in_any_font(): void
    {
        $this->needsHarfBuzz();
        // No <meta charset>: the shaped words are still read as UTF-8.
        $pdf = Pdf::loadHTML('<html><head><style>body{font-family:"DejaVu Sans"}</style></head>'
            . '<body><p>Subject: हिन्दी</p><p style="font-family:serif;font-weight:bold">हिन्दी</p></body></html>');

        $this->assertInstanceOf(FallbackFontMetrics::class, $pdf->getDomPDF()->getFontMetrics());

        $out = $pdf->output();
        $this->assertStringStartsWith('%PDF', $out);

        // The page draws U+E27C U+E044 U+E19D U+E017 — ि ह न्द ी — in both
        // paragraphs (dompdf writes a font's codes as UTF-16BE).
        $drawn = '';
        preg_match_all('#stream\r?\n(.*?)\r?\nendstream#s', $out, $streams);
        foreach ($streams[1] as $s) {
            $drawn .= @gzuncompress($s) ?: '';
        }
        $this->assertSame(2, substr_count($drawn, mb_convert_encoding(self::g(636, 68, 413, 23), 'UTF-16BE', 'UTF-8')));
    }
}
