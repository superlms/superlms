<?php

namespace App\Support\Pdf;

use Barryvdh\DomPDF\PDF;

/**
 * The PDF wrapper every Pdf::loadView / Pdf::loadHTML gets (bound over
 * 'dompdf.wrapper' in AppServiceProvider): before dompdf reads the HTML, the
 * Hindi in it is shaped (Devanagari::html), and if any was, dompdf looks
 * fonts up through FallbackFontMetrics so the shaped glyphs print in any font.
 * HTML without Devanagari goes through untouched.
 */
class ShapedPdf extends PDF
{
    public function loadHTML(string $string, ?string $encoding = null): self
    {
        $shaped = Devanagari::html($string);

        if ($shaped !== $string) {
            $dompdf  = $this->getDomPDF();
            if (!$dompdf->getFontMetrics() instanceof FallbackFontMetrics) {
                $metrics = new FallbackFontMetrics($dompdf->getCanvas(), $dompdf->getOptions());
                $dompdf->setFontMetrics($metrics);
                // The stylesheet keeps the one it was made with; @font-face registers through it.
                $dompdf->getCss()->setFontMetrics($metrics);
            }
            // Our views are UTF-8. Told nothing, dompdf guesses a page with no
            // <meta charset>, and PHP's guess marks text full of Private Use
            // characters as not UTF-8 — the shaped words came out as "?".
            $encoding ??= 'UTF-8';
        }

        return parent::loadHTML($shaped, $encoding);
    }
}
