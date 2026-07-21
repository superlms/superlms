<?php

namespace App\Support;

/**
 * Bundles our display/body fonts (Poppins + PT Serif) into dompdf as
 * base64 @font-face rules so generated PDFs render with proper typography
 * instead of dompdf's default DejaVu Sans.
 *
 * dompdf collapses font-weight down to normal/bold per family, so each weight
 * is registered under its OWN family name (e.g. "Poppins SemiBold") and the
 * PDF stylesheet references those names directly rather than font-weight.
 */
class PdfFonts
{
    /** @var array<string,string> family name => font file (in resources/fonts) */
    private const FACES = [
        'Poppins'          => 'Poppins-Regular.ttf',
        'Poppins SemiBold' => 'Poppins-SemiBold.ttf',
        'Poppins Bold'     => 'Poppins-Bold.ttf',
        'PT Serif'         => 'PT_Serif-Web-Regular.ttf',
        'PT Serif Bold'    => 'PT_Serif-Web-Bold.ttf',
    ];

    private static ?string $css = null;

    /**
     * @font-face CSS (data-URI embedded) for the bundled fonts. Memoised for
     * the request; missing files are skipped so a render never hard-fails.
     */
    public static function faceCss(): string
    {
        if (self::$css !== null) {
            return self::$css;
        }

        $css = '';
        foreach (self::FACES as $family => $file) {
            $path = resource_path('fonts/' . $file);
            if (!is_file($path)) {
                continue;
            }
            $b64 = base64_encode((string) file_get_contents($path));
            $css .= "@font-face{font-family:'{$family}';font-weight:normal;font-style:normal;"
                . "src:url(data:font/truetype;charset=utf-8;base64,{$b64}) format('truetype');}\n";
        }

        return self::$css = $css;
    }
}
