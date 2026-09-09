<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;

/**
 * Single source of truth for the two serials a student is minted with.
 *
 * Both formats used to be duplicated across the admin Livewire screen, the
 * super-admin screen and the mobile API controller, and had already drifted.
 * They live here now so a format change lands everywhere at once.
 */
class StudentNumbers
{
    /**
     * Admission number = YY + SCHOOL_CODE + DOB_YY + 4-digit serial.
     *
     *   YY          last two digits of the CURRENT calendar year (2026 → "26")
     *   SCHOOL_CODE organization.school_code (e.g. "TDS")
     *   DOB_YY      last two digits of the student's year of birth (2015 → "15")
     *   serial      4 digits, starts at 0001, counted per organization within
     *               the same prefix.
     *
     * Example: a student born in 2015 admitted to TDS in 2026 → 26TDS150001
     */
    public static function admissionNumber($org, $dob): string
    {
        $organization = $org instanceof Organization ? $org : Organization::find((int) $org);
        $orgId        = $organization?->id ?? (is_numeric($org) ? (int) $org : null);

        $yy         = substr((string) now()->year, -2);
        $schoolCode = (string) ($organization?->school_code ?? '');
        $dobYy      = self::yearSuffix($dob);

        $prefix = $yy . $schoolCode . $dobYy;

        // Highest serial already issued under this prefix for this school.
        $last = StudentDetail::where('organization_id', $orgId)
            ->where('admission_no', 'like', $prefix . '%')
            ->orderByDesc('admission_no')
            ->value('admission_no');

        $serial = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Roll number = lastDigit(class.code) + 2-digit serial starting at 01.
     *
     * The serial is scoped to the class (not the section) so every roll number
     * inside a class is unique — the prefix only carries the class, so a
     * per-section counter would hand the same roll to A-01 and B-01.
     *
     * Example: class code "03" → 301, 302, 303 …
     */
    public static function rollNumber($standardId): string
    {
        $standard   = Standard::find((int) $standardId);
        $classDigit = self::lastDigit($standard?->code ?? $standard?->id);

        // Only look at rolls already minted in this class under the same
        // prefix — legacy rolls ("001", "002") don't match and are left alone.
        $last = StudentDetail::where('standard_id', (int) $standardId)
            ->whereNotNull('roll_no')
            ->where('roll_no', 'like', $classDigit . '%')
            ->orderByRaw('CAST(SUBSTRING(roll_no, 2) AS UNSIGNED) DESC')
            ->value('roll_no');

        $serial = $last ? ((int) substr($last, 1)) + 1 : 1;

        return $classDigit . str_pad((string) $serial, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Next class code for an organization: "01", "02", "03" … in creation
     * order. Codes are never reused, so a deleted class leaves a gap rather
     * than shifting everyone below it.
     */
    public static function nextStandardCode($orgId): string
    {
        $max = (int) Standard::where('organization_id', $orgId)
            ->whereNotNull('code')
            ->selectRaw('MAX(CAST(code AS UNSIGNED)) as max_code')
            ->value('max_code');

        return str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }

    /** Last two digits of a date's year ("2015-06-10" → "15"). */
    private static function yearSuffix($dob): string
    {
        if (empty($dob)) {
            return '00';
        }

        try {
            $year = $dob instanceof \DateTimeInterface
                ? (int) $dob->format('Y')
                : (int) \Carbon\Carbon::parse((string) $dob)->format('Y');
        } catch (\Throwable $e) {
            return '00';
        }

        return substr(str_pad((string) $year, 4, '0', STR_PAD_LEFT), -2);
    }

    /** Last numeric digit of a code-like value, "0" when there isn't one. */
    private static function lastDigit($value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return ($digits === '' || $digits === null) ? '0' : substr($digits, -1);
    }
}
