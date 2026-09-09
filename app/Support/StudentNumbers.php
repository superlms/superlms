<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\DB;

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
     *   serial      4 digits, counted across the WHOLE school and handed out one
     *               by one: 0001, 0002, 0003 … Once a serial belongs to a
     *               student it is never issued again, whatever year they were
     *               born in or admitted in.
     *
     * Example: a student born in 2015 admitted to TDS in 2026 → 26TDS150001,
     * and the next admission — born any year — takes 0002.
     */
    public static function admissionNumber($org, $dob): string
    {
        $organization = $org instanceof Organization ? $org : Organization::find((int) $org);
        $orgId        = $organization?->id ?? (is_numeric($org) ? (int) $org : null);

        $yy         = substr((string) now()->year, -2);
        $schoolCode = (string) ($organization?->school_code ?? '');
        $dobYy      = self::yearSuffix($dob);

        $prefix = $yy . $schoolCode . $dobYy;
        $serial = self::nextAdmissionSerial($orgId);

        return $prefix . str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * The next unused serial for a school.
     *
     * The count is over every admission number the school has issued, not just
     * the ones sharing this student's prefix — that is what keeps 0001 from
     * being handed to a second child. Callers that create students should hold
     * withCreationLock() so two simultaneous saves can't read the same maximum.
     */
    private static function nextAdmissionSerial(?int $orgId): int
    {
        $max = (int) StudentDetail::where('organization_id', $orgId)
            ->whereNotNull('admission_no')
            ->where('admission_no', '<>', '')
            ->selectRaw('MAX(CAST(RIGHT(admission_no, 4) AS UNSIGNED)) as max_serial')
            ->value('max_serial');

        $serial = $max + 1;

        // Belt and braces for rows the MAX can't speak for — an imported
        // admission number shorter than four characters, say. Bounded so a
        // pathological table can never spin here.
        $ceiling = $serial + 1000;
        while ($serial < $ceiling && self::serialTaken($orgId, $serial)) {
            $serial++;
        }

        return $serial;
    }

    /** Is any admission number in this school already ending in this serial? */
    private static function serialTaken(?int $orgId, int $serial): bool
    {
        return StudentDetail::where('organization_id', $orgId)
            ->where('admission_no', 'like', '%' . str_pad((string) $serial, 4, '0', STR_PAD_LEFT))
            ->exists();
    }

    /**
     * Serialise student creation per school with a MySQL application lock.
     *
     * Admission and roll numbers are minted by reading the current maximum — a
     * read-then-write race. Two admins saving at the same instant would
     * otherwise read the same maximum and mint the same serial. The second
     * caller waits here, then reads the freshly committed maximum.
     *
     * Degrades gracefully: a lock failure (non-MySQL driver, timeout) is logged
     * and the work proceeds, so we never block a legitimate save.
     */
    public static function withCreationLock($orgId, \Closure $work)
    {
        self::acquireCreationLock($orgId);

        try {
            return $work();
        } finally {
            self::releaseCreationLock($orgId);
        }
    }

    public static function lockName($orgId): string
    {
        return 'student_create_' . (int) $orgId;
    }

    public static function acquireCreationLock($orgId): void
    {
        if (!$orgId) return;

        try {
            DB::selectOne('SELECT GET_LOCK(?, 15) AS got', [self::lockName($orgId)]);
        } catch (\Throwable $e) {
            logger()->warning('acquireCreationLock failed: ' . $e->getMessage());
        }
    }

    public static function releaseCreationLock($orgId): void
    {
        if (!$orgId) return;

        try {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [self::lockName($orgId)]);
        } catch (\Throwable $e) {
            logger()->warning('releaseCreationLock failed: ' . $e->getMessage());
        }
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
