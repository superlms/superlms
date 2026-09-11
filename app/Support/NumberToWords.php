<?php

namespace App\Support;

/**
 * Rupee amounts spelled out the way a printed receipt states them — Indian
 * numbering (lakh, crore), "Rupees … Only" for a whole amount, "and … Paise"
 * tacked on when there's a fractional part.
 */
class NumberToWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function rupees(float $amount): string
    {
        $amount = round(max(0, $amount), 2);
        $whole  = (int) floor($amount);
        $paise  = (int) round(($amount - $whole) * 100);

        $out = 'Rupees ' . (self::whole($whole) ?: 'Zero');
        if ($paise > 0) {
            $out .= ' and ' . self::whole($paise) . ' Paise';
        }

        return $out . ' Only';
    }

    /** An integer, Indian-grouped: crore / lakh / thousand / hundred. */
    private static function whole(int $n): string
    {
        if ($n === 0) {
            return '';
        }

        $parts = [];

        $crore = intdiv($n, 10000000);
        $n %= 10000000;
        $lakh = intdiv($n, 100000);
        $n %= 100000;
        $thousand = intdiv($n, 1000);
        $n %= 1000;
        $hundred = intdiv($n, 100);
        $n %= 100;

        if ($crore > 0) {
            $parts[] = self::underThousand($crore) . ' Crore';
        }
        if ($lakh > 0) {
            $parts[] = self::underThousand($lakh) . ' Lakh';
        }
        if ($thousand > 0) {
            $parts[] = self::underThousand($thousand) . ' Thousand';
        }
        if ($hundred > 0) {
            $parts[] = self::ONES[$hundred] . ' Hundred';
        }
        if ($n > 0) {
            $parts[] = self::underHundred($n);
        }

        return implode(' ', $parts);
    }

    /** 1–999, used for the crore/lakh/thousand groups (each capped at 3 digits). */
    private static function underThousand(int $n): string
    {
        $out = '';
        if ($n >= 100) {
            $out .= self::ONES[intdiv($n, 100)] . ' Hundred ';
            $n %= 100;
        }
        return trim($out . self::underHundred($n));
    }

    /** 0–99. */
    private static function underHundred(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }
        $tens = self::TENS[intdiv($n, 10)];
        $ones = self::ONES[$n % 10];
        return trim($tens . ' ' . $ones);
    }
}
