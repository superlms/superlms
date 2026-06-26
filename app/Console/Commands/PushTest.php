<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;

/**
 * Diagnose mobile-app push delivery end to end.
 *
 *   php artisan push:test            → show how many device tokens are registered
 *   php artisan push:test 42         → send a test push to user #42 and report
 *
 * Use this to pin down where a missing notification is failing:
 *   - 0 tokens overall        → the app never registered (rebuild / login as
 *                               student/teacher on the FCM build).
 *   - tokens but send errors  → FIREBASE_CREDENTIALS not set / wrong key.
 *   - "ok > 0" but no banner  → app-side display (rebuild the app, check channel).
 */
class PushTest extends Command
{
    protected $signature = 'push:test {user_id? : Send a test push to this user id}';

    protected $description = 'Diagnose / send a test mobile push notification';

    public function handle(): int
    {
        $total = UserFcmToken::count();
        $this->info("Registered device tokens (all users): {$total}");

        if ($total > 0) {
            $this->line('By platform: ' . UserFcmToken::selectRaw('platform, count(*) c')
                ->groupBy('platform')->pluck('c', 'platform')->toJson());
        }

        $userId = $this->argument('user_id');

        if (!$userId) {
            // List who actually has a token so it's easy to pick a test target.
            $holders = UserFcmToken::with('user:id,name,role')
                ->get()
                ->map(fn ($t) => [
                    'user_id' => $t->user_id,
                    'name'    => $t->user->name ?? '(deleted)',
                    'role'    => $t->user->role ?? '?',
                    'platform' => $t->platform ?: '?',
                ]);

            if ($holders->isNotEmpty()) {
                $this->table(['user_id', 'name', 'role', 'platform'], $holders);
            }

            $this->line('Pass a user id to send a test push, e.g. php artisan push:test 42');
            return self::SUCCESS;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User #{$userId} not found.");
            return self::FAILURE;
        }

        $userTokens = $user->fcmTokens()->count();
        $this->info("User #{$userId} ({$user->name}, role={$user->role}) has {$userTokens} token(s).");

        if ($userTokens === 0) {
            $this->warn('No tokens for this user — log in as them on the app build first.');
            return self::SUCCESS;
        }

        try {
            // Resolved here (not in the signature) so the token report above
            // still prints when Firebase credentials are missing.
            $fcm = app(FirebaseNotificationService::class);
            $ok = $fcm->notifyUserIds([(int) $userId], 'general', [
                'title'  => 'Test notification',
                'body'   => 'If you can see this, push delivery works 🎉',
                'screen' => 'Notifications',
            ]);
            $this->info($ok ? 'Sent (at least one device accepted it).' : 'Send returned false — check the log lines above.');
        } catch (\Throwable $e) {
            $this->error('Send threw: ' . $e->getMessage());
            $this->warn('This usually means FIREBASE_CREDENTIALS is missing/invalid.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
