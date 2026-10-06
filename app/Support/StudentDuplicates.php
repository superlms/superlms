<?php

namespace App\Support;

use App\Models\Student\StudentDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * One student added once, however often the Add button is pressed.
 *
 * Add Student pressed twice, or pressed again after the network lost the
 * first answer (the student was saved, the phone never heard back), used to
 * add the child again — a second admission number, and a second welcome on
 * WhatsApp and by email. Every way a student is created now asks here first,
 * while it holds StudentNumbers' creation lock, so the first save is committed
 * by the time the second one looks:
 *
 *   recent()  the same child — school, class, name, father, date of birth
 *             and mobile — added in the last WINDOW_MINUTES
 *   forRef()  the app's own mark for one Add Student form (client_ref), the
 *             same however often that form is sent
 *
 * Either answer is the student already saved; the caller hands that one back
 * and adds nobody. Nothing here can make a save fail: a lookup that breaks
 * finds nothing, and the save goes on as it always did.
 */
class StudentDuplicates
{
    /** How long after an add the very same child counts as that add again. */
    public const WINDOW_MINUTES = 30;

    /** How long the app's mark for one form is remembered. */
    private const REF_HOURS = 24;

    /**
     * The same child added to this school a moment ago, if one was.
     */
    public static function recent($orgId, $standardId, $name, $fatherName, $dob, $mobile): ?StudentDetail
    {
        $mobile = trim((string) $mobile);

        if (!$orgId || !$standardId || trim((string) $name) === '' || $mobile === '' || empty($dob)) {
            return null;
        }

        try {
            $date = Carbon::parse((string) $dob)->toDateString();

            return StudentDetail::where('organization_id', (int) $orgId)
                ->where('standard_id', (int) $standardId)
                // As saved — the panel keeps what was typed, the app's request
                // comes trimmed — so either way of writing it.
                ->whereIn('full_name', self::spellings($name))
                ->whereIn('father_name', self::spellings($fatherName))
                ->whereDate('dob', $date)
                ->where('phone', $mobile)
                ->where('created_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
                ->latest('id')
                ->first();
        } catch (\Throwable $e) {
            logger()->warning('StudentDuplicates::recent failed: ' . $e->getMessage());

            return null;
        }
    }

    /** The student this Add Student form already saved, if it did. */
    public static function forRef($orgId, $ref): ?StudentDetail
    {
        $key = self::refKey($orgId, $ref);
        if (!$key) {
            return null;
        }

        try {
            $id = Cache::get($key);

            return $id ? StudentDetail::where('organization_id', (int) $orgId)->find((int) $id) : null;
        } catch (\Throwable $e) {
            logger()->warning('StudentDuplicates::forRef failed: ' . $e->getMessage());

            return null;
        }
    }

    /** Remembers which student this Add Student form saved. */
    public static function remember($orgId, $ref, StudentDetail $detail): void
    {
        $key = self::refKey($orgId, $ref);
        if (!$key) {
            return;
        }

        try {
            Cache::put($key, (int) $detail->id, now()->addHours(self::REF_HOURS));
        } catch (\Throwable $e) {
            logger()->warning('StudentDuplicates::remember failed: ' . $e->getMessage());
        }
    }

    private static function spellings($value): array
    {
        return array_values(array_unique([(string) $value, trim((string) $value)]));
    }

    private static function refKey($orgId, $ref): ?string
    {
        $ref = trim((string) $ref);
        if (!$orgId || $ref === '' || strlen($ref) > 100) {
            return null;
        }

        return 'student_create_ref:' . (int) $orgId . ':' . sha1($ref);
    }
}
