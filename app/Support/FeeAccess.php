<?php

namespace App\Support;

use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\User;

/**
 * Fee-based login gating for the "per student" super-admin fee plan.
 *
 * When a school's active fee structure for the current academic year is
 * `per_student`, only students who have FULLY paid that fee may sign in to the
 * app. Schools on any other plan (or no plan) don't gate login at all. This is
 * the single source of truth used by the login endpoints and by the Fees
 * screen when it needs to log a student out after their status changes.
 */
class FeeAccess
{
    /** Current academic year, matching the "YYYY-YYYY+1" form the Fees screen uses. */
    public static function academicYear(): string
    {
        return now()->year . '-' . (now()->year + 1);
    }

    /** The active per-student fee structure for an org this year, or null. */
    public static function perStudentStructure(?int $orgId): ?SuperAdminFeeStructure
    {
        if (!$orgId) {
            return null;
        }

        return SuperAdminFeeStructure::where('organization_id', $orgId)
            ->where('academic_year', self::academicYear())
            ->where('fee_type', 'per_student')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Should this user be blocked from logging in on fee grounds?
     * Only students ('user') in a per-student school who haven't fully paid.
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
        if (!$structure) {
            return false; // school isn't on the per-student plan → no gate
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

        $collected = (float) SuperAdminFeePayment::where('student_detail_id', $detail->id)
            ->where('super_admin_fee_structure_id', $structure->id)
            ->value('amount');

        return $fee > 0 && $collected + 0.01 >= $fee;
    }

    /**
     * Revoke a student's app sessions (Sanctum tokens) so they are logged out
     * on their next request. Called when a per-student payment is reverted so
     * the student loses access until they pay again. Fails open.
     */
    public static function logoutStudent(StudentDetail $detail): void
    {
        try {
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
}
