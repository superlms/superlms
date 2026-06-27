<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFcmToken;
use Illuminate\Support\Collection;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;

/**
 * Push delivery for the mobile app (FCM).
 *
 * The React Native app (superlmsapp) handles *display* itself via Notifee and
 * keeps its own in-app inbox. So we deliver **data-only** messages — no FCM
 * `notification` block — otherwise Android would also auto-post a tray banner in
 * the background and we'd get duplicates. The app's `push.ts` reads the data
 * payload and calls `notify()`.
 *
 * Data contract (every value must be a string — FCM only allows string data):
 *   {
 *     type:    string  // a catalog.ts key, e.g. "marks_uploaded"
 *     title?:  string
 *     body?:   string
 *     screen?: string  // route name to deep-link on tap
 *     params?: string  // JSON-encoded object
 *   }
 *
 * High-level entry point for event rules: notifyUser() / notifyUsers().
 */
class FirebaseNotificationService
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  High-level API — call these from event rules ("konsa notification kab").
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send one notification to a single user (all of their devices).
     *
     * @param  array  $opts  title?, body?, screen?, params? (array)
     */
    public function notifyUser(?User $user, string $type, array $opts = []): bool
    {
        if (!$user) {
            return false;
        }

        return $this->sendData(
            $user->fcmTokens()->pluck('token')->filter()->all(),
            $type,
            $opts,
            $user->id
        );
    }

    /**
     * Send the same notification to many users (e.g. a whole class).
     *
     * @param  iterable<User>  $users
     */
    public function notifyUsers(iterable $users, string $type, array $opts = []): bool
    {
        return $this->notifyUserIds(
            collect($users)->pluck('id')->all(),
            $type,
            $opts
        );
    }

    /**
     * Send the same notification to many users given their ids (most efficient
     * path — no User models loaded, tokens queried in one shot).
     *
     * @param  array<int>  $userIds
     */
    public function notifyUserIds(array $userIds, string $type, array $opts = []): bool
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (empty($userIds)) {
            logger()->info('[push] no recipients', ['type' => $type]);
            return false;
        }

        $tokens = UserFcmToken::whereIn('user_id', $userIds)
            ->pluck('token')
            ->filter()
            ->all();

        logger()->info('[push] dispatch', [
            'type'   => $type,
            'users'  => count($userIds),
            'tokens' => count($tokens),
        ]);

        return $this->sendData($tokens, $type, $opts);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Core data-only sender.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  string[]  $tokens
     */
    protected function sendData(array $tokens, string $type, array $opts = [], ?int $userId = null): bool
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if (empty($tokens)) {
            logger()->warning('No FCM tokens to deliver notification', [
                'type' => $type,
                'user_id' => $userId,
            ]);
            return false;
        }

        $data = $this->buildDataPayload($type, $opts);

        $message = CloudMessage::new()
            ->withData($data)
            // Wake the app even in the background / Doze so Notifee can post it.
            ->withAndroidConfig([
                'priority' => 'high',
            ])
            // iOS: content-available lets the app process data in the background.
            ->withApnsConfig([
                'headers' => ['apns-priority' => '5'],
                'payload' => ['aps' => ['content-available' => 1]],
            ]);

        $anySuccess = false;
        $sent = 0;
        $failed = 0;

        // FCM allows at most 500 tokens per multicast — chunk for large audiences.
        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $report = $this->messaging->sendMulticast($message, $chunk);
                $this->pruneInvalidTokens($report);
                $sent += $report->successes()->count();
                $failed += $report->failures()->count();
                $anySuccess = $anySuccess || $report->successes()->count() > 0;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        logger()->info('[push] sent', ['type' => $type, 'ok' => $sent, 'failed' => $failed]);

        return $anySuccess;
    }

    /**
     * Build the string-only data payload the app expects.
     */
    protected function buildDataPayload(string $type, array $opts): array
    {
        $data = ['type' => $type];

        foreach (['title', 'body', 'screen'] as $key) {
            if (!empty($opts[$key])) {
                $data[$key] = (string) $opts[$key];
            }
        }

        if (!empty($opts['params']) && is_array($opts['params'])) {
            $data['params'] = json_encode($opts['params']);
        }

        return $data;
    }

    protected function pruneInvalidTokens(MulticastSendReport $report): void
    {
        if (!$report->hasFailures()) {
            return;
        }

        // Tokens that are permanently dead (unregistered / invalid) should be
        // dropped so we stop trying to reach them.
        $invalid = array_merge(
            $report->unknownTokens(),
            $report->invalidTokens()
        );

        if (!empty($invalid)) {
            UserFcmToken::whereIn('token', $invalid)->delete();
        }

        foreach ($report->failures()->getItems() as $failure) {
            logger()->warning('FCM delivery failure', [
                'reason' => $failure->error()?->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Token registration.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Register / move a device token to this user (multi-device safe).
     * A given token belongs to exactly one user — if it was seen against
     * someone else (shared device, re-login), it gets reassigned.
     */
    public function saveToken(User $user, string $token, ?string $platform = null): UserFcmToken
    {
        return UserFcmToken::updateOrCreate(
            ['token' => $token],
            ['user_id' => $user->id, 'platform' => $platform]
        );
    }

    /**
     * Remove a device token (on logout). Scoped to the user so one account
     * can't unregister another account's device.
     */
    public function removeToken(User $user, string $token): bool
    {
        return (bool) UserFcmToken::where('token', $token)
            ->where('user_id', $user->id)
            ->delete();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Backwards-compatible helpers (existing /save-fcm-token + send-to-me).
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Legacy single-token save used by the old /save-fcm-token route and the
     * super-admin panel. Delegates to the multi-device upsert.
     */
    public function saveFcmToken($user, $token): bool
    {
        $this->saveToken($user, $token);
        return true;
    }

    /**
     * Legacy title/body send used by /notifications/send-to-me. Routed through
     * the data-only path so it stays consistent with the app contract.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        return $this->notifyUser($user, $data['type'] ?? 'general', [
            'title'  => $title,
            'body'   => $body,
            'screen' => $data['screen'] ?? null,
            'params' => $data['params'] ?? (empty($data) ? null : $data),
        ]);
    }
}
