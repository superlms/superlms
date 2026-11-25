<?php

namespace App\Support;

use App\Models\Teacher\TeacherDetail;

/**
 * A teacher's Employee ID is theirs alone within the school (user's ask of
 * 2026-11-25): Add or Edit Teacher — in the panel, the admin app or the Super
 * Admin — is turned down when another teacher of the school already has it,
 * ignoring case and spaces at the ends. A teacher already sharing an ID
 * (from before this) can still be saved while their ID is left as it is.
 */
class EmployeeIds
{
    /** The name of the other teacher of the school who has this Employee ID, or null when it is free. */
    public static function takenBy(int $orgId, ?string $employeeId, ?int $exceptUserId = null): ?string
    {
        $id = mb_strtolower(trim((string) $employeeId));
        if ($id === '' || ! $orgId) {
            return null;
        }

        $row = TeacherDetail::with('user:id,name')
            ->where('organization_id', $orgId)
            ->whereRaw('LOWER(TRIM(employee_id)) = ?', [$id])
            ->when($exceptUserId, fn ($q) => $q->where(fn ($w) => $w->whereNull('user_id')->orWhere('user_id', '!=', $exceptUserId)))
            ->orderBy('id')
            ->first();

        return $row ? (trim((string) ($row->user->name ?? '')) ?: 'another teacher') : null;
    }

    /**
     * Why an Employee ID cannot be saved for this teacher ($userId null for a
     * new one), or null when it can: taken by another teacher, unless it is
     * the very ID this teacher already has.
     */
    public static function problem(int $orgId, ?string $employeeId, ?int $userId = null): ?string
    {
        if ($userId) {
            $current = TeacherDetail::where('user_id', $userId)->value('employee_id');
            if ($current !== null && mb_strtolower(trim((string) $current)) === mb_strtolower(trim((string) $employeeId))) {
                return null;
            }
        }

        $name = self::takenBy($orgId, $employeeId, $userId);

        return $name ? "This Employee ID is already given to {$name}. Each teacher needs their own Employee ID." : null;
    }
}
