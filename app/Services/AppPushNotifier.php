<?php

namespace App\Services;

use App\Models\Admin\Announcement;
use App\Models\Admin\HomeWork;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * High-level "when does which notification fire, to whom" for the mobile app.
 *
 * Each public method maps a domain event to its audience and hands a data-only
 * payload to {@see FirebaseNotificationService}. Everything is fail-open: a
 * notification failure must never break the originating action (saving an
 * announcement, marking attendance, etc.).
 *
 * Role column on `users`: student = 'user', teacher = 'teacher'.
 */
class AppPushNotifier
{
    private const STUDENT = 'user';
    private const TEACHER = 'teacher';

    /**
     * Resolve the FCM transport lazily. When Firebase isn't configured yet (no
     * service-account key), this throws — but it's only ever called inside
     * {@see safe()}, so the originating action (saving the announcement, marking
     * attendance, …) is never broken.
     */
    private function fcm(): FirebaseNotificationService
    {
        return app(FirebaseNotificationService::class);
    }

    // ── Events ────────────────────────────────────────────────────────────────

    /**
     * A new announcement was posted by admin. Audience follows its `type`:
     * all → students + teachers, user → students, teacher → teachers.
     */
    public function announcement(Announcement $a): void
    {
        $this->safe(function () use ($a) {
            $roles = match ($a->type) {
                'user'    => [self::STUDENT],
                'teacher' => [self::TEACHER],
                default   => [self::STUDENT, self::TEACHER], // 'all'
            };

            $this->fcm()->notifyUserIds(
                $this->orgUserIds($a->organization_id, $roles),
                'announcement',
                [
                    'title'  => $a->announcement_name ?: 'New Announcement',
                    // Full content so it can be read on expand; capped well within
                    // FCM's ~4KB message limit.
                    'body'   => Str::limit(trim(strip_tags((string) $a->announcement_content)), 1500),
                    'screen' => 'ViewAnnouncement',
                    'params' => ['item' => ['id' => $a->id]],
                ]
            );
        });
    }

    /**
     * A school-info / policy / legal page changed. Notifies students + teachers
     * (of the org when org-scoped, else everyone) and deep-links to the page.
     */
    public function settingUpdated(string $screen, string $label, ?int $orgId): void
    {
        $this->safe(function () use ($screen, $label, $orgId) {
            $roles = [self::STUDENT, self::TEACHER];
            $ids = $orgId !== null
                ? $this->orgUserIds($orgId, $roles)
                : $this->allUserIds($roles);

            $this->fcm()->notifyUserIds($ids, 'general', [
                'title'  => "{$label} updated",
                'body'   => "{$label} has been updated. Tap to view.",
                'screen' => $screen,
            ]);
        });
    }

    /**
     * Attendance was marked/updated for a set of students. Each student gets
     * their own notification with the recorded status.
     *
     * @param  array<int, array{user_id?: int|null, status?: mixed}>  $rows
     */
    public function attendanceMarked(array $rows): void
    {
        $this->safe(function () use ($rows) {
            foreach ($rows as $row) {
                $userId = $row['user_id'] ?? null;
                if (!$userId) {
                    continue;
                }

                $status = $this->statusLabel($row['status'] ?? null);

                $this->fcm()->notifyUserIds([(int) $userId], 'attendance_marked', [
                    'title'  => 'Attendance Marked',
                    'body'   => $status
                        ? "Your attendance has been marked as {$status}."
                        : 'Your attendance has been marked.',
                    'screen' => 'Attendance',
                ]);
            }
        });
    }

    /** New homework was created → notify the students of that class & section. */
    public function homeworkAssigned(HomeWork $hw): void
    {
        $this->safe(function () use ($hw) {
            $studentUserIds = StudentDetail::where('organization_id', $hw->organization_id)
                ->where('standard_id', $hw->standard_id)
                ->where('section_id', $hw->section_id)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->all();

            // Keep only active student accounts.
            $ids = User::whereIn('id', $studentUserIds)
                ->where('role', self::STUDENT)
                ->pluck('id')
                ->all();

            $subject = optional($hw->subject)->name;
            $title   = $hw->title ?: 'Homework';

            $this->fcm()->notifyUserIds($ids, 'homework_assigned', [
                'title'  => 'New Homework Assigned',
                'body'   => $subject ? "New {$subject} homework: {$title}" : "New homework: {$title}",
                'screen' => 'Homework',
                'params' => ['homeworkId' => $hw->id],
            ]);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return array<int> */
    private function orgUserIds(?int $orgId, array $roles): array
    {
        if (!$orgId) {
            return [];
        }

        return User::where('organization_id', $orgId)
            ->whereIn('role', $roles)
            ->pluck('id')
            ->all();
    }

    /** @return array<int> */
    private function allUserIds(array $roles): array
    {
        return User::whereIn('role', $roles)->pluck('id')->all();
    }

    private function statusLabel($code): ?string
    {
        return match ((int) $code) {
            0 => 'Absent',
            1 => 'Present',
            2 => 'Late',
            3 => 'Half Day',
            4 => 'Holiday',
            default => null,
        };
    }

    private function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            // Most common cause while rolling out: the Firebase service-account
            // key isn't configured yet, so resolving Messaging throws. Log it
            // clearly rather than as a scary stack trace, and never rethrow.
            logger()->warning('[push] dispatch failed (is FIREBASE_CREDENTIALS set?)', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
