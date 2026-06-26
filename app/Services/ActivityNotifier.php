<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Sends activity notifications to super-admin users:
 *   - stored in the database (shown in the header notification bell), and
 *   - pushed via FCM web push (browser notification).
 *
 * Fails open: a missing notifications table or unconfigured Firebase never
 * interrupts the originating action.
 */
class ActivityNotifier
{
    public static function toSuperAdmins(string $title, string $body, array $data = []): void
    {
        $superAdmins = User::whereIn('role', ['super-admin', 'sub-super-admin'])->get();

        if ($superAdmins->isEmpty()) {
            return;
        }

        // FCM payload values must be strings.
        $stringData = array_map(fn($v) => is_scalar($v) ? (string) $v : '', $data);

        // Resolve the push service once; null if Firebase isn't configured.
        try {
            $push = app(FirebaseNotificationService::class);
        } catch (\Throwable $e) {
            $push = null;
            logger()->warning('ActivityNotifier: Firebase unavailable, in-app only', [
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($superAdmins as $user) {
            // 1) Store in-app notification (header bell).
            try {
                $user->notifications()->create([
                    'id'   => (string) Str::uuid(),
                    'type' => 'activity',
                    'data' => array_merge([
                        'title' => $title,
                        'body'  => $body,
                    ], $data),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            // 2) Push to the browser via FCM.
            if ($push) {
                try {
                    $push->sendToUser($user, $title, $body, $stringData);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }
}
