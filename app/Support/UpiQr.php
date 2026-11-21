<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * A UPI payment QR (More → Messaging → Buy Messages): any UPI app scanning it
 * opens SuperLMS's UPI id with the amount filled in. Drawn here as an SVG of
 * squares (bacon/bacon-qr-code's encoder, no GD and no outside service).
 */
class UpiQr
{
    /** The upi://pay link for an amount (₹, two decimals) and a note. */
    public static function link(string $upiId, string $name, float $amount, string $note = ''): string
    {
        $q = [
            'pa' => $upiId,
            'pn' => $name,
            'am' => number_format(max(0, $amount), 2, '.', ''),
            'cu' => 'INR',
        ];
        if ($note !== '') {
            $q['tn'] = mb_substr($note, 0, 60);
        }

        return 'upi://pay?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
    }

    /** The QR as an <svg>, $size px across, or null when it cannot be drawn. */
    public static function svg(string $text, int $size = 196): ?string
    {
        try {
            $matrix = Encoder::encode($text, ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        $n     = $matrix->getWidth();
        $quiet = 2;
        $full  = $n + $quiet * 2;
        $path  = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $path .= 'M' . ($x + $quiet) . ' ' . ($y + $quiet) . 'h1v1h-1z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $full . ' ' . $full . '" width="' . $size . '" height="' . $size
            . '" shape-rendering="crispEdges" role="img" aria-label="UPI QR code"><rect width="100%" height="100%" fill="#fff"/>'
            . '<path fill="#0c2620" d="' . $path . '"/></svg>';
    }
}
