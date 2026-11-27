<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * A date written out in words, as certificates print a date of birth:
 * Hindi "पाँच अगस्त दो हज़ार नौ", English "Fifth August Two Thousand Nine".
 */
class DateInWords
{
    private const HI = ['शून्य', 'एक', 'दो', 'तीन', 'चार', 'पाँच', 'छह', 'सात', 'आठ', 'नौ',
        'दस', 'ग्यारह', 'बारह', 'तेरह', 'चौदह', 'पन्द्रह', 'सोलह', 'सत्रह', 'अठारह', 'उन्नीस',
        'बीस', 'इक्कीस', 'बाईस', 'तेईस', 'चौबीस', 'पच्चीस', 'छब्बीस', 'सत्ताईस', 'अट्ठाईस', 'उनतीस',
        'तीस', 'इकतीस', 'बत्तीस', 'तैंतीस', 'चौंतीस', 'पैंतीस', 'छत्तीस', 'सैंतीस', 'अड़तीस', 'उनतालीस',
        'चालीस', 'इकतालीस', 'बयालीस', 'तैंतालीस', 'चौवालीस', 'पैंतालीस', 'छियालीस', 'सैंतालीस', 'अड़तालीस', 'उनचास',
        'पचास', 'इक्यावन', 'बावन', 'तिरपन', 'चौवन', 'पचपन', 'छप्पन', 'सत्तावन', 'अट्ठावन', 'उनसठ',
        'साठ', 'इकसठ', 'बासठ', 'तिरसठ', 'चौंसठ', 'पैंसठ', 'छियासठ', 'सड़सठ', 'अड़सठ', 'उनहत्तर',
        'सत्तर', 'इकहत्तर', 'बहत्तर', 'तिहत्तर', 'चौहत्तर', 'पचहत्तर', 'छिहत्तर', 'सतहत्तर', 'अठहत्तर', 'उन्यासी',
        'अस्सी', 'इक्यासी', 'बयासी', 'तिरासी', 'चौरासी', 'पचासी', 'छियासी', 'सत्तासी', 'अट्ठासी', 'नवासी',
        'नब्बे', 'इक्यानवे', 'बानवे', 'तिरानवे', 'चौरानवे', 'पचानवे', 'छियानवे', 'सत्तानवे', 'अट्ठानवे', 'निन्यानवे'];

    private const HI_MONTHS = [1 => 'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'];

    private const EN = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    private const EN_TENS = [2 => 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    private const EN_ORD = [1 => 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth',
        'Eleventh', 'Twelfth', 'Thirteenth', 'Fourteenth', 'Fifteenth', 'Sixteenth', 'Seventeenth', 'Eighteenth', 'Nineteenth',
        'Twentieth', 'Twenty-First', 'Twenty-Second', 'Twenty-Third', 'Twenty-Fourth', 'Twenty-Fifth', 'Twenty-Sixth',
        'Twenty-Seventh', 'Twenty-Eighth', 'Twenty-Ninth', 'Thirtieth', 'Thirty-First'];

    public static function hindi(CarbonInterface $date): string
    {
        return self::HI[$date->day] . ' ' . self::HI_MONTHS[$date->month] . ' ' . self::hindiYear($date->year);
    }

    public static function english(CarbonInterface $date): string
    {
        return self::EN_ORD[$date->day] . ' ' . $date->format('F') . ' ' . self::englishYear($date->year);
    }

    /** 2009 → दो हज़ार नौ; 1998 → उन्नीस सौ अट्ठानवे. */
    public static function hindiYear(int $year): string
    {
        if ($year >= 2000 && $year < 2100) {
            return trim('दो हज़ार ' . ($year % 100 ? self::HI[$year % 100] : ''));
        }

        return self::HI[intdiv($year, 100) % 100] . ' सौ' . ($year % 100 ? ' ' . self::HI[$year % 100] : '');
    }

    /** 2009 → Two Thousand Nine; 1998 → Nineteen Ninety-Eight. */
    public static function englishYear(int $year): string
    {
        if ($year >= 2000 && $year < 2100) {
            return trim('Two Thousand ' . ($year % 100 ? self::englishUnder100($year % 100) : ''));
        }

        return self::englishUnder100(intdiv($year, 100) % 100) . ' ' . ($year % 100 ? self::englishUnder100($year % 100) : 'Hundred');
    }

    private static function englishUnder100(int $n): string
    {
        if ($n < 20) {
            return self::EN[$n];
        }

        return self::EN_TENS[intdiv($n, 10)] . ($n % 10 ? '-' . self::EN[$n % 10] : '');
    }
}
