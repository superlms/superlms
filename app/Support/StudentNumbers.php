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
     * Roll number = the class's whole code + a 3-digit serial from 001.
     *
     *   class code "05" → 05001, 05002, 05003 … (05999, then 051000)
     *   class code "12" → 12001, 12002 …
     *
     * The serial is counted over the whole class, every section together, so
     * no two students of a class share a roll; it is the highest serial the
     * class already has + 1. The code is the class's own (no two classes of a
     * school share one), so neither do two classes' rolls. A changed code
     * changes every roll of the class with it (recodeClass, from Standard),
     * and a student moved to another class takes a roll of that class
     * (StudentDetail). Until 2026-10-24 a roll was the code's last digit + a
     * 2-digit serial (501, 5118); the migration of that day numbered every
     * class again this way.
     */
    public static function rollNumber($standardId): string
    {
        $standard = Standard::find((int) $standardId);
        $prefix   = self::rollPrefix($standard?->code, (int) $standardId);

        $max = StudentDetail::where('standard_id', (int) $standardId)
            ->whereNotNull('roll_no')
            ->pluck('roll_no')
            ->map(fn ($roll) => self::rollSerial($roll, $prefix))
            ->filter()
            ->max() ?? 0;

        return self::roll($prefix, $max + 1);
    }

    /** What a class's rolls start with: its code, as typed; a class without one, its id ("07"). */
    public static function rollPrefix(?string $code, int $standardId): string
    {
        $code = trim((string) $code);

        return $code !== '' ? $code : str_pad((string) $standardId, 2, '0', STR_PAD_LEFT);
    }

    /** "05" + 7 → "05007". */
    public static function roll(string $prefix, int $serial): string
    {
        return $prefix . str_pad((string) max(1, $serial), 3, '0', STR_PAD_LEFT);
    }

    /** The serial of a roll made from this prefix ("05007" → 7), or null for any other roll. */
    public static function rollSerial(?string $roll, string $prefix): ?int
    {
        $roll = trim((string) $roll);
        if ($prefix === '' || !str_starts_with($roll, $prefix)) {
            return null;
        }
        $rest = substr($roll, strlen($prefix));

        return preg_match('/^\d{3,}$/', $rest) ? (int) $rest : null;
    }

    /**
     * A class's code changed: every roll of it takes the new code, its serial
     * kept ("05007" → "12007"). A student whose roll is not of the old code
     * (none yet, or one typed by hand) takes the next roll of the new code.
     */
    public static function recodeClass(int $standardId, ?string $oldCode, ?string $newCode): int
    {
        $old = self::rollPrefix($oldCode, $standardId);
        $new = self::rollPrefix($newCode, $standardId);
        if ($old === $new) {
            return 0;
        }

        $students = DB::table('student_details')->where('standard_id', $standardId)
            ->orderBy('id')->get(['id', 'roll_no']);

        $serials = $students->mapWithKeys(fn ($s) => [$s->id => self::rollSerial($s->roll_no, $old)]);
        $next    = (int) ($serials->filter()->max() ?? 0);
        $cards   = DB::getSchemaBuilder()->hasTable('admit_cards');
        $changed = 0;

        foreach ($students as $s) {
            $serial = $serials[$s->id] ?? null;
            $changed += self::setRoll((int) $s->id, $s->roll_no, self::roll($new, $serial ?: ++$next), $cards);
        }

        return $changed;
    }

    /**
     * Every student of a class numbered again from 001 by its code, in the
     * order their rolls stood — by the roll's number, then (no roll, or one
     * that is not a number) the order they were added. What the 2026-10-24
     * migration ran over every class.
     */
    public static function renumberClass(int $standardId, ?string $code): int
    {
        $prefix   = self::rollPrefix($code, $standardId);
        $students = DB::table('student_details')->where('standard_id', $standardId)->get(['id', 'roll_no'])
            ->sortBy([
                fn ($a, $b) => self::rollOrder($a->roll_no) <=> self::rollOrder($b->roll_no),
                fn ($a, $b) => $a->id <=> $b->id,
            ])->values();

        $cards   = DB::getSchemaBuilder()->hasTable('admit_cards');
        $changed = 0;
        foreach ($students as $i => $s) {
            $changed += self::setRoll((int) $s->id, $s->roll_no, self::roll($prefix, $i + 1), $cards);
        }

        return $changed;
    }

    /** A roll as a number to sort by; one that is not a number goes last. */
    private static function rollOrder(?string $roll): int
    {
        $roll = trim((string) $roll);

        return preg_match('/^\d{1,15}$/', $roll) ? (int) $roll : PHP_INT_MAX;
    }

    /**
     * Give a student a new roll, and their admit cards with it — a card keeps
     * the roll it was issued with, and one that still shows the old roll would
     * print a number the student no longer has. Returns 1 when it changed.
     */
    public static function setRoll(int $studentDetailId, ?string $old, string $new, ?bool $cards = null): int
    {
        if ((string) $old === $new) {
            return 0;
        }

        DB::table('student_details')->where('id', $studentDetailId)->update(['roll_no' => $new]);
        self::followOnCards($studentDetailId, $old, $new, $cards);

        return 1;
    }

    /**
     * The student's admit cards that show their old roll (or none) show the
     * new one. $cards: whether admit_cards exists, when the caller knows.
     */
    public static function followOnCards(int $studentDetailId, ?string $old, string $new, ?bool $cards = null): void
    {
        if ($cards ?? DB::getSchemaBuilder()->hasTable('admit_cards')) {
            DB::table('admit_cards')->where('student_detail_id', $studentDetailId)
                ->where(fn ($q) => $q->where('roll_number', (string) $old)
                    ->orWhereNull('roll_number')->orWhere('roll_number', '')->orWhere('roll_number', 'N/A'))
                ->update(['roll_number' => $new]);
        }
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
}
