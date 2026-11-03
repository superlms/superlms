<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Limits on the way into the Super Admin panel — its login, its forgot
 * password and an installed app's code screen. They live on the server, so
 * nothing the browser sends can lift them.
 *
 *  - Wrong passwords (or an email with no account): 5 for one email from one
 *    address (IP) in 5 minutes, and 20 from one address whatever the email in
 *    15 minutes. The right password clears that email's count.
 *  - Email look-ups on Forgot password: 10 from one address in 15 minutes, so
 *    "no such account" can't be used to list the accounts.
 *  - Codes sent: 5 to one account in 15 minutes — login, resend, forgot
 *    password and app openings together — so nobody can flood its inbox and
 *    WhatsApp with codes.
 */
class AuthThrottle
{
    public const PASSWORD_PER_EMAIL         = 5;
    public const PASSWORD_PER_EMAIL_SECONDS = 300;

    public const PASSWORD_PER_IP         = 20;
    public const PASSWORD_PER_IP_SECONDS = 900;

    public const LOOKUPS_PER_IP         = 10;
    public const LOOKUPS_PER_IP_SECONDS = 900;

    public const SENDS_PER_ACCOUNT         = 5;
    public const SENDS_PER_ACCOUNT_SECONDS = 900;

    /** Seconds before another password may be tried for this email (0 = now). */
    public static function passwordWait(string $email): int
    {
        return self::wait([
            [self::passwordKey($email), self::PASSWORD_PER_EMAIL],
            [self::ipKey('pw'), self::PASSWORD_PER_IP],
        ]);
    }

    public static function passwordFailed(string $email): void
    {
        RateLimiter::hit(self::passwordKey($email), self::PASSWORD_PER_EMAIL_SECONDS);
        RateLimiter::hit(self::ipKey('pw'), self::PASSWORD_PER_IP_SECONDS);
    }

    public static function passwordPassed(string $email): void
    {
        RateLimiter::clear(self::passwordKey($email));
    }

    /** Seconds before Forgot password may look up another email (0 = now). */
    public static function lookupWait(): int
    {
        return self::wait([[self::ipKey('lookup'), self::LOOKUPS_PER_IP]]);
    }

    public static function lookedUp(): void
    {
        RateLimiter::hit(self::ipKey('lookup'), self::LOOKUPS_PER_IP_SECONDS);
    }

    /** Seconds before another code may be sent to this account (0 = now). */
    public static function sendWait(User $user): int
    {
        return self::wait([[self::sendKey($user), self::SENDS_PER_ACCOUNT]]);
    }

    public static function sent(User $user): void
    {
        RateLimiter::hit(self::sendKey($user), self::SENDS_PER_ACCOUNT_SECONDS);
    }

    public static function message(int $seconds): string
    {
        return sprintf('Too many tries. Try again after %d:%02d.', intdiv($seconds, 60), $seconds % 60);
    }

    public static function sendMessage(int $seconds): string
    {
        return sprintf('Too many codes were asked for this account. Try again after %d:%02d.', intdiv($seconds, 60), $seconds % 60);
    }

    /** The longest wait among the limits that are used up. */
    private static function wait(array $limits): int
    {
        $wait = 0;
        foreach ($limits as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $wait = max($wait, RateLimiter::availableIn($key), 1);
            }
        }

        return $wait;
    }

    private static function passwordKey(string $email): string
    {
        return 'sa-auth:pw:' . sha1(mb_strtolower(User::cleanEmail($email)) . '|' . request()->ip());
    }

    private static function ipKey(string $what): string
    {
        return 'sa-auth:' . $what . ':ip:' . sha1((string) request()->ip());
    }

    private static function sendKey(User $user): string
    {
        return 'sa-auth:send:' . $user->getKey();
    }
}
