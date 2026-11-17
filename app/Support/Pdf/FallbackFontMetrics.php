<?php

namespace App\Support\Pdf;

use Dompdf\FontMetrics;

/**
 * dompdf's font lookup with one more face at the end of every font list:
 * Devanagari::FAMILY. A shaped Hindi word is written as Private Use code
 * points only the Poppins faces map, so in text set in another font (the
 * school's name in Merriweather, a table in DejaVu Sans) its glyphs come from
 * that face instead of printing as empty boxes. Text the element's own fonts
 * can print is unchanged.
 */
class FallbackFontMetrics extends FontMetrics
{
    public function mapTextToFonts(string $text, array $fontFamilies, string $subtype = "normal", int $count = -1, bool $returnSubstring = false): array
    {
        $fontFamilies[] = strtolower(Devanagari::FAMILY);

        return parent::mapTextToFonts($text, $fontFamilies, $subtype, $count, $returnSubstring);
    }
}
