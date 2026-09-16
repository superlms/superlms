<?php

namespace App\Support;

use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Cache;

/**
 * The school admin panel's emailed OTP, for the mobile app.
 *
 * On the web a school admin or sub-admin is only signed in after the code
 * mailed to them is entered (App\Livewire\Admin\Login). The app follows the
 * same rule: the password check sends the code and hands back a request token
 * instead of a session, and a session is issued only once that request's code
 * is verified — at the login screen and when the account is added to the
 * account switcher alike. The code, its expiry, resends and the lockout after
 * wrong entries are the web's own (OtpMailService); the panel name in the
 * email is the web's too.
 *
 * App builds from before this send no `otp_supported` flag; they can't show
 * the code step, so an admin is told to update the app rather than be signed
 * in without it.
 */
class AdminAppOtp
{
    public const PANEL = 'School Admin';

    public const UPDATE_APP_MESSAGE = 'Please update the SuperLMS app to sign in as a school admin — a code sent to your email is now needed.';

    public const EXPIRED_MESSAGE = 'This code has expired. Please sign in again.';

    /** How long an issued request can be verified or resent. */
    private const REQUEST_TTL_MINUTES = 15;

    /** Whether this user needs the emailed code before the app gets a session. */
    public static function required(User $user): bool
    {
        return in_array($user->role, ['admin', 'sub-admin'], true)
            && OtpMailService::loginOtpEnabled();
    }

    /** The web admin login's refusals for a user whose password has cleared. */
    public static function refusal(User $user): ?string
    {
        if (!$user->organization_id) {
            return 'No organization assigned to this account.';
        }
        if ($user->role === 'sub-admin' && !$user->is_active) {
            return 'Your account is inactive. Please contact the administrator.';
        }

        return null;
    }

    /**
     * Mails a code for a sign-in the password has already cleared and returns
     * what the app needs for the code step. $purpose keeps a login's request
     * from being spent on an account-switcher add and the other way round.
     *
     * @throws \RuntimeException with a message fit for the user (lockout, mail failure)
     */
    public static function challenge(User $user, string $purpose): array
    {
        $otpToken = OtpMailService::sendOtp($user, self::PANEL);
        self::remember($user, $purpose, $otpToken);

        return [
            'otp_required' => true,
            'user_id'      => $user->id,
            'email'        => $user->email,
            'user_type'    => 'admin',
            'otp_token'    => $otpToken,
            'expires_in'   => OtpMailService::CODE_TTL_SECONDS,
            'resend_in'    => OtpMailService::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Mails a fresh code for the same request. Returns seconds to wait when
     * it is too soon, or null once sent.
     *
     * @throws \InvalidArgumentException when the request is unknown or gone
     * @throws \RuntimeException with a message fit for the user
     */
    public static function resend(User $user, string $purpose, string $otpToken): ?int
    {
        self::assertIssued($user, $purpose, $otpToken);

        if ($wait = OtpMailService::resendAvailableIn($user, $otpToken)) {
            return $wait;
        }

        OtpMailService::sendOtp($user, self::PANEL, $otpToken);
        self::remember($user, $purpose, $otpToken);

        return null;
    }

    /**
     * Checks the code against the request it was sent for; the request is
     * used up on success.
     *
     * @throws \Exception with a message fit for the user
     */
    public static function verify(User $user, string $purpose, string $otpToken, string $otp): void
    {
        self::assertIssued($user, $purpose, $otpToken);

        OtpMailService::verifyOtp($user, $otp, $otpToken);
        Cache::forget(self::key($purpose, $otpToken));
    }

    /**
     * A request this flow issued for this user and purpose — anything else is refused.
     *
     * @throws \InvalidArgumentException
     */
    private static function assertIssued(User $user, string $purpose, string $otpToken): void
    {
        if ((int) Cache::get(self::key($purpose, $otpToken)) !== (int) $user->id) {
            throw new \InvalidArgumentException(self::EXPIRED_MESSAGE);
        }
    }

    private static function remember(User $user, string $purpose, string $otpToken): void
    {
        Cache::put(self::key($purpose, $otpToken), $user->id, now()->addMinutes(self::REQUEST_TTL_MINUTES));
    }

    private static function key(string $purpose, string $otpToken): string
    {
        return 'admin_app_otp:' . $purpose . ':' . $otpToken;
    }
}
