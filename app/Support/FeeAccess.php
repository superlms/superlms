<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * What the "per student" Super Admin fee lets a student do.
 *
 * A school's Per Student fee is collected one of two ways (collect_from on its
 * structure, set on Super Admin → Fees):
 *
 *   school  — the school pays and the super admin marks it paid. A student the
 *             school has not paid for cannot sign in to the app (as before).
 *   student — each student pays it in the app. They sign in to a payment
 *             screen with Pay Now (PhonePe) and nothing else until it is paid
 *             (the PlatformFeeGate middleware); meanwhile the school can
 *             neither take their fee nor mark their attendance ("locked").
 *
 * Schools on any other plan (or none) are not gated at all. This is the one
 * place every login, app request, fee submission and attendance mark asks.
 */
class FeeAccess
{
    public const FROM_SCHOOL  = 'school';
    public const FROM_STUDENT = 'student';

    /** org id → [read at, per-student structure or null, locked ids or null]. */
    private static array $memo = [];

    /** How long one read is trusted — a request, or a queue job, never longer. */
    private const MEMO_SECONDS = 5;

    /** Current academic year, matching the "YYYY-YYYY+1" form the Fees screen uses. */
    public static function academicYear(): string
    {
        return now()->year . '-' . (now()->year + 1);
    }

    /** Forget what was read — a structure or payment changed. */
    public static function flush(): void
    {
        self::$memo = [];
    }

    /** The active per-student fee structure for an org this year, or null. */
    public static function perStudentStructure(?int $orgId): ?SuperAdminFeeStructure
    {
        if (!$orgId) {
            return null;
        }

        $memo = self::$memo[$orgId] ?? null;
        if ($memo && (microtime(true) - $memo[0]) < self::MEMO_SECONDS) {
            return $memo[1];
        }

        try {
            $structure = SuperAdminFeeStructure::where('organization_id', $orgId)
                ->where('academic_year', self::academicYear())
                ->where('fee_type', 'per_student')
                ->where('is_active', true)
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            // No fee tables (a bare install, a test's own schema): nobody is gated.
            $structure = null;
        }

        self::$memo[$orgId] = [microtime(true), $structure, null];

        return $structure;
    }

    /** Who pays a structure: 'student' only for a Per Student fee set so, else 'school'. */
    public static function collectFrom(?SuperAdminFeeStructure $structure): string
    {
        return $structure && $structure->collectsFromStudents() ? self::FROM_STUDENT : self::FROM_SCHOOL;
    }

    /** The org's Per Student fee when its students pay it in the app (and it is more than nothing). */
    public static function studentPaysStructure(?int $orgId): ?SuperAdminFeeStructure
    {
        $structure = self::perStudentStructure($orgId);

        return $structure && $structure->collectsFromStudents() && (float) $structure->amount > 0
            ? $structure
            : null;
    }

    /**
     * Should this user be blocked from logging in on fee grounds?
     * Only students ('user') of a school that pays their per-student fee,
     * when it has not paid for them. A student who pays it themselves signs
     * in — to the payment screen.
     */
    public static function studentLoginBlocked(?User $user): bool
    {
        if (!$user || $user->role !== 'user') {
            return false;
        }

        $detail = StudentDetail::where('user_id', $user->id)->first();
        if (!$detail) {
            return false;
        }

        $structure = self::perStudentStructure($detail->organization_id);
        if (!$structure || $structure->collectsFromStudents()) {
            return false; // no per-student plan, or the student pays it in the app
        }

        return !self::studentFullyPaid($detail, $structure);
    }

    /**
     * Has this student fully paid their per-student fee for the current year?
     * With no per-student plan the student is unrestricted (returns true).
     */
    public static function studentFullyPaid(StudentDetail $detail, ?SuperAdminFeeStructure $structure = null): bool
    {
        $structure ??= self::perStudentStructure($detail->organization_id);
        if (!$structure) {
            return true;
        }

        $fee = (float) $structure->amount;

        return $fee > 0 && self::collected($detail, $structure) + 0.01 >= $fee;
    }

    /** What has come in on a student's per-student fee. */
    public static function collected(StudentDetail $detail, SuperAdminFeeStructure $structure): float
    {
        return (float) SuperAdminFeePayment::where('student_detail_id', $detail->id)
            ->where('super_admin_fee_structure_id', $structure->id)
            ->value('amount');
    }

    /** A student who must pay the fee in the app before anything else. */
    public static function mustPay(StudentDetail $detail): bool
    {
        $structure = self::studentPaysStructure($detail->organization_id);

        return $structure !== null && !self::studentFullyPaid($detail, $structure);
    }

    /** The signed-in user is a student who must pay first (the app's gate). */
    public static function userMustPay(?User $user): bool
    {
        if (!$user || $user->role !== 'user') {
            return false;
        }

        // The school is asked first: most are not on this plan, and their
        // students cost one small query a request.
        if ($user->organization_id && !self::studentPaysStructure((int) $user->organization_id)) {
            return false;
        }

        $detail = StudentDetail::where('user_id', $user->id)->first();

        return $detail !== null && self::mustPay($detail);
    }

    /**
     * The students of a school who must still pay in the app, as [id => true]:
     * their fee cannot be taken and their attendance cannot be marked.
     * Empty for a school whose students do not pay it themselves.
     */
    public static function lockedIds(?int $orgId): array
    {
        $structure = self::studentPaysStructure($orgId);
        if (!$structure) {
            return [];
        }

        $memo = self::$memo[$orgId] ?? null;
        if ($memo && $memo[2] !== null && (microtime(true) - $memo[0]) < self::MEMO_SECONDS) {
            return $memo[2];
        }

        $fee  = (float) $structure->amount;
        $paid = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->whereNotNull('student_detail_id')
            ->where('amount', '>=', $fee - 0.01)
            ->pluck('student_detail_id')
            ->flip();

        $locked = [];
        foreach (StudentDetail::where('organization_id', $orgId)->pluck('id') as $id) {
            if (!$paid->has($id)) {
                $locked[(int) $id] = true;
            }
        }

        self::$memo[$orgId] = [self::$memo[$orgId][0] ?? microtime(true), $structure, $locked];

        return $locked;
    }

    /** Is this student locked out of fee submission and attendance? */
    public static function locked(int $studentId, ?int $orgId = null): bool
    {
        $orgId ??= (int) StudentDetail::whereKey($studentId)->value('organization_id');

        return $orgId > 0 && isset(self::lockedIds($orgId)[$studentId]);
    }

    /** Stop a fee being taken for a locked student (the fee payment models' last check). */
    public static function guardFeeWrite(?int $studentId, ?int $orgId = null): void
    {
        if (self::$feeWritesAllowed > 0 || !$studentId) {
            return;
        }
        if (self::locked((int) $studentId, $orgId ? (int) $orgId : null)) {
            throw ValidationException::withMessages(['fee' => self::lockedMessage()]);
        }
    }

    private static int $feeWritesAllowed = 0;

    /**
     * Run $fn with the fee lock off — for money a gateway has already taken
     * (an online payment or an Autopay debit settling), which is always booked.
     */
    public static function allowingFeeWrites(callable $fn): mixed
    {
        self::$feeWritesAllowed++;
        try {
            return $fn();
        } finally {
            self::$feeWritesAllowed--;
        }
    }

    /**
     * The student's fee, as the app's payment screen shows it.
     */
    public static function status(StudentDetail $detail): array
    {
        $structure = self::studentPaysStructure($detail->organization_id);
        $org       = Organization::find($detail->organization_id, ['id', 'name', 'logo']);
        $detail->loadMissing(['standard:id,name', 'section:id,name']);

        $fee       = $structure ? round((float) $structure->amount, 2) : 0.0;
        $collected = $structure ? round(self::collected($detail, $structure), 2) : 0.0;
        $paid      = !$structure || ($fee > 0 && $collected + 0.01 >= $fee);
        $last      = $structure
            ? SuperAdminFeePayment::where('student_detail_id', $detail->id)
                ->where('super_admin_fee_structure_id', $structure->id)->first()
            : null;

        return [
            'required'      => $structure !== null,
            'must_pay'      => $structure !== null && !$paid,
            'paid'          => $paid,
            'amount'        => $fee,
            'collected'     => $collected,
            'due'           => round(max(0, $fee - $collected), 2),
            'label'         => $structure?->fee_label ?: 'Annual Platform Fee',
            'academic_year' => $structure?->academic_year ?? self::academicYear(),
            'school'        => ['name' => $org?->name, 'logo' => $org?->logo],
            'student'       => [
                'name'         => $detail->full_name,
                'class'        => trim(($detail->standard?->name ?? '') . ($detail->section?->name ? ' - ' . $detail->section->name : '')),
                'admission_no' => $detail->admission_no,
            ],
            'last_payment'  => $last && (float) $last->amount > 0 ? [
                'amount'         => round((float) $last->amount, 2),
                'date'           => $last->payment_date?->toDateString(),
                'mode'           => $last->payment_mode,
                'receipt_number' => $last->receipt_number,
            ] : null,
        ];
    }

    /**
     * Revoke a student's app sessions (Sanctum tokens) so they are logged out
     * on their next request. Called when a per-student payment is reverted so
     * the student loses access until they pay again. A student who pays it in
     * the app keeps their sign-in: the app's gate shows them the payment
     * screen instead. Fails open.
     */
    public static function logoutStudent(StudentDetail $detail): void
    {
        try {
            if (self::studentPaysStructure($detail->organization_id)) {
                return;
            }
            $user = $detail->user()->first();
            $user?->tokens()->delete();
        } catch (\Throwable $e) {
            logger()->warning('FeeAccess::logoutStudent failed: ' . $e->getMessage());
        }
    }

    /** Standard 403 message shown to a fee-blocked student. */
    public static function blockedMessage(): string
    {
        return 'Your fee payment is pending. Please pay your fee to continue using the app.';
    }

    /** What the app's gate answers a student who must pay first. */
    public static function payFirstMessage(): string
    {
        return 'Please pay your platform fee to continue using the app.';
    }

    /** Why a locked student's fee or attendance was refused. */
    public static function lockedMessage(): string
    {
        return 'This student has not paid the platform fee in the app yet — their fee cannot be submitted and their attendance cannot be marked until they pay.';
    }
}
