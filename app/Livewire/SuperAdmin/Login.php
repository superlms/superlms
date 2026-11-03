<?php

namespace App\Livewire\SuperAdmin;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use App\Services\WhatsAppService;
use App\Support\AuthThrottle;
use App\Support\OtpNumbers;
use App\Support\PanelApp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Login extends Component
{
    public string $step = 'credentials'; // 'credentials' | 'otp'

    public string $email    = '';
    public string $password = '';

    // OTP step — array of 6 single digits (matches accounts VerifyOtp pattern)
    public array $otp = ['', '', '', '', '', ''];
    public int   $countdown = 120;
    public bool  $canResend = false;

    /** This sign-in's OTP request — only its own code signs in here. */
    #[Locked]
    public ?string $otpChallenge = null;

    /**
     * The account whose password was checked on this page — the only one the
     * code step can sign in or send a code to. Locked: the email field can be
     * changed in the browser, this can't, so the password can't be skipped by
     * typing another address on the code step.
     */
    #[Locked]
    public ?int $pendingUserId = null;

    /**
     * Unix timestamp the OTP lockout lifts at (0 = not locked out). Absolute so
     * the browser can tick it down without a re-render restarting it.
     */
    public int $otpLockedUntil = 0;

    public function login()
    {
        // Spaces or invisible characters that came with a pasted address.
        $this->email = User::cleanEmail($this->email);

        $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Enter your email address.',
            'email.email'       => 'This is not a valid email address — it should look like name@example.com.',
            'password.required' => 'Enter your password.',
        ]);

        // Wrong passwords are counted on the server: guessing stops here.
        if ($wait = AuthThrottle::passwordWait($this->email)) {
            $this->addError('email', AuthThrottle::message($wait));
            return;
        }

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            AuthThrottle::passwordFailed($this->email);
            $this->addError('email', 'No Super Admin account uses this email address.');
            return;
        }

        if (!Hash::check($this->password, (string) $user->password)) {
            AuthThrottle::passwordFailed($this->email);
            $this->addError('password', 'Wrong password for this email. Check Caps Lock, or use Forgot password.');
            return;
        }

        AuthThrottle::passwordPassed($this->email);

        if (!self::mayEnter($user, $this->email)) {
            $this->addError('email', $user->role === 'sub-super-admin'
                ? 'This account is switched off. The main super admin can switch it on in Users.'
                : 'This email is not allowed into the Super Admin panel.');
            return;
        }

        // 2FA temporarily disabled (ZeptoMail out of credit) — sign in directly.
        // The OTP step below is left intact; flip services.otp.login_enabled back
        // on to restore it.
        if (!OtpMailService::loginOtpEnabled()) {
            Auth::guard('superadmin')->login($user);
            // A login made in the installed app: from now on it opens for this account.
            PanelApp::rememberAppLogin('superadmin', $user);
            return redirect()->route('super-admin.quick-links')
                ->with('success', 'Login successful.');
        }

        // From here on the code step works for this account only.
        $this->pendingUserId = $user->id;

        if ($wait = AuthThrottle::sendWait($user)) {
            $this->addError('email', AuthThrottle::sendMessage($wait));
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        // The OTP request is opened inside sendOtp BEFORE anything is sent, so
        // even if delivery fails we can still continue. The same code goes on
        // WhatsApp to the account's primary number; either arriving is enough. TEMPORARY: if nothing goes (e.g. ZeptoMail
        // outage and WhatsApp down), log the OTP so a super-admin can sign in
        // from the logs. Remove this catch once email is healthy.
        $delivery = null;
        AuthThrottle::sent($user);
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin', $this->otpChallenge, self::whatsAppNumbers($user), $delivery);
        } catch (OtpDeliveryException $e) {
            $this->otpChallenge = $e->challenge;
            \Log::warning('SUPERADMIN OTP not delivered; OTP for ' . $user->email . ' = ' . $e->otp . ' (expires 2 min)', ['err' => $e->getMessage()]);
            $notSent = self::whyNotSent($delivery);
        } catch (\Throwable $e) {
            // A lockout must stop the flow — and with no request opened there
            // is no code to enter either.
            if ($lockedUntil = OtpMailService::lockedUntil($user)) {
                $this->otpLockedUntil = $lockedUntil;
                $this->addError('email', $e->getMessage());
                return;
            }
            logger()->error('OTP send failed during super-admin login: ' . $e->getMessage());
            $this->addError('email', 'Could not make a sign-in code: ' . $e->getMessage());
            return;
        }

        $this->otp        = ['', '', '', '', '', ''];
        $this->countdown  = 120;
        $this->canResend  = false;
        $this->step       = 'otp';
        // The password has done its job; it doesn't ride along with the page.
        $this->password   = '';

        // Where the code went isn't shown — only that it went nowhere, and why.
        if (isset($notSent)) {
            $this->addError('otp', $notSent);
        }
    }

    public function verifyOtp()
    {
        // Idempotency guard: if a previous (possibly duplicated) request already
        // signed us in, just continue to the dashboard instead of re-consuming
        // the OTP — which would throw and strand the user on a stale page.
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('super-admin.quick-links');
        }

        // Errors stay between requests: the last try's message must not stand
        // in front of this one's.
        $this->resetValidation('otp');

        $digits  = array_map(fn($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp);
        $entered = implode('', $digits);

        if ($entered === '') {
            $this->addError('otp', 'Enter the 6-digit code.');
            return;
        }

        if (!ctype_digit($entered)) {
            $this->addError('otp', 'The code has numbers only (0–9) — remove any letters or signs.');
            return;
        }

        if (strlen($entered) !== 6) {
            $this->addError('otp', 'Enter all 6 digits — only ' . strlen($entered) . ' entered.');
            return;
        }

        // The account whose password was checked here — never the email field.
        $user = $this->pendingUser();

        if (!$user) {
            $this->expire();
            return;
        }

        try {
            OtpMailService::verifyOtp($user, $entered, $this->otpChallenge);
            // Panel-specific guard: signing in here never touches the admin
            // or accounts sessions in the same browser.
            Auth::guard('superadmin')->login($user);
            // A login made in the installed app: from now on it opens for this account.
            PanelApp::rememberAppLogin('superadmin', $user);
            $this->pendingUserId = null;
            return redirect()->route('super-admin.quick-links');
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
        }
    }

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }

        $user = $this->pendingUser();

        if (!$user) {
            $this->expire();
            return;
        }

        // The 2-minute wait is the server's: a page that says otherwise is
        // only shown the time left.
        if ($wait = OtpMailService::resendAvailableIn($user, $this->otpChallenge)) {
            $this->countdown = $wait;
            $this->canResend = false;
            return;
        }

        if ($wait = AuthThrottle::sendWait($user)) {
            $this->addError('otp', AuthThrottle::sendMessage($wait));
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        $delivery = null;
        AuthThrottle::sent($user);
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin', $this->otpChallenge, self::whatsAppNumbers($user), $delivery);
            $this->otp       = ['', '', '', '', '', ''];
            $this->countdown = 120;
            $this->canResend = false;
            $this->resetValidation('otp');
        } catch (OtpDeliveryException $e) {
            // TEMPORARY, as in login(): the new code is in the log.
            \Log::warning('SUPERADMIN OTP resend not delivered; OTP for ' . $user->email . ' = ' . $e->otp . ' (expires 2 min)', ['err' => $e->getMessage()]);
            $this->addError('otp', self::whyNotSent($delivery));
        } catch (\Exception $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
        }
    }

    public function timerFinished(): void
    {
        $this->canResend = true;
    }

    public function backToLogin()
    {
        $this->step          = 'credentials';
        $this->otp           = ['', '', '', '', '', ''];
        $this->pendingUserId = null;
        $this->resetValidation();
    }

    /**
     * Who may come in: the main super-admin (whitelisted email, super-admin
     * role) or a sub super-admin (made in Users) that is switched on.
     */
    private static function mayEnter(User $user, string $email): bool
    {
        $allowedEmails = ['superlms.india@gmail.com', 'superlmsofficial@gmail.com'];

        return match ($user->role) {
            'super-admin'     => in_array($email, $allowedEmails, true),
            'sub-super-admin' => (bool) $user->is_active,
            default           => false,
        };
    }

    /** The account this page checked the password of, while it may still come in. */
    private function pendingUser(): ?User
    {
        if (!$this->pendingUserId) {
            return null;
        }

        $user = User::whereKey($this->pendingUserId)
            ->whereIn('role', ['super-admin', 'sub-super-admin'])
            ->first();

        return $user && self::mayEnter($user, mb_strtolower(User::cleanEmail($user->email))) ? $user : null;
    }

    /** No checked password behind the code step: back to the login. */
    private function expire(): void
    {
        $this->step          = 'credentials';
        $this->otp           = ['', '', '', '', '', ''];
        $this->pendingUserId = null;
        $this->addError('email', 'This sign-in has expired. Log in again.');
    }

    /** The account's primary number (Profile → OTP Numbers) — the only one the code goes to on WhatsApp. */
    private static function whatsAppNumbers(User $user): array
    {
        return array_filter([OtpNumbers::primary($user)]);
    }

    /**
     * The code reached no one: why, channel by channel, from sendOtp()'s
     * delivery report — "Email: …; WhatsApp +91 ••••••5914: …".
     */
    private static function whyNotSent(?array $delivery): string
    {
        $reasons = [];

        if (!empty($delivery['email']['error'])) {
            $reasons[] = 'Email: ' . $delivery['email']['error'];
        }
        foreach ($delivery['whatsapp'] ?? [] as $wa) {
            $reasons[] = 'WhatsApp ' . self::maskMobile($wa['to']) . ': ' . $wa['error'];
        }

        return 'The code could not be sent' . ($reasons ? ' — ' . implode('; ', $reasons) : '') . '. Try Resend OTP in 2 minutes.';
    }

    /** "+91 ••••••8563" — enough to tell which phone to look at. */
    private static function maskMobile(?string $mobile): string
    {
        $digits = WhatsAppService::normalizePhone($mobile) ?? preg_replace('/\D+/', '', (string) $mobile);

        return '+91 ••••••' . substr($digits, -4);
    }

    public function render()
    {
        return view('livewire.super-admin.login')->layout('components.layouts.fullscreen');
    }
}
