<?php

namespace App\Livewire\Admin;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use App\Support\AuthThrottle;
use App\Support\PanelApp;
use App\Support\SchoolAdminOtp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The school admin / sub-admin login, in the Super Admin login's look and
 * flow: email and password, then a code sent by email and on WhatsApp to the
 * account's registered mobile number (App\Support\SchoolAdminOtp) — either
 * arriving is enough.
 */
class Login extends Component
{
    public string $step = 'credentials'; // 'credentials' | 'otp'

    public string $email    = '';
    public string $password = '';

    // OTP step — array of 6 single digits
    public array $otp       = ['', '', '', '', '', ''];
    public int   $countdown = 120;
    public bool  $canResend = false;

    /** The account has a registered mobile, so the code went there too. */
    public bool $hasMobile = false;

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

    /** The school already signed in on this browser, if any: [name, url] for the "Continue" line. */
    #[Locked]
    public array $signedIn = [];

    public function mount(): void
    {
        $current = Auth::guard('admin')->user();
        if ($current && $current->organization_id && in_array($current->role, ['admin', 'sub-admin'], true)) {
            $this->signedIn = [
                'name' => (string) ($current->organization->name ?? $current->name),
                'url'  => route('admin.quick-links', ['organization' => $current->organization_id]),
            ];
        }
    }

    /**
     * Step 1 — check the password and the account, then send the code and
     * move to the code step. Nobody is signed in until the code is verified.
     */
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

        $user = self::accountByEmail($this->email);

        if (!$user) {
            AuthThrottle::passwordFailed($this->email);
            $this->addError('email', 'No school admin account uses this email address.');
            return;
        }

        if (!Hash::check($this->password, (string) $user->password)) {
            AuthThrottle::passwordFailed($this->email);
            $this->addError('password', 'Wrong password for this email. Check Caps Lock, or use Forgot password.');
            return;
        }

        AuthThrottle::passwordPassed($this->email);

        if ($why = self::refusal($user)) {
            $this->addError('email', $why);
            return;
        }

        // LOGIN_OTP_ENABLED=false (an emergency switch): sign in directly.
        if (!OtpMailService::loginOtpEnabled()) {
            return $this->signIn($user);
        }

        // From here on the code step works for this account only.
        $this->pendingUserId = $user->id;

        if ($wait = AuthThrottle::sendWait($user)) {
            $this->addError('email', AuthThrottle::sendMessage($wait));
            return;
        }

        $notSent = null;
        if (!$this->send($user, $notSent)) {
            return;
        }

        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->step      = 'otp';
        // The password has done its job; it doesn't ride along with the page.
        $this->password  = '';

        // Where the code went isn't shown — only that it went nowhere, and why.
        if ($notSent) {
            $this->addError('otp', $notSent);
        }
    }

    /**
     * Set once this request has checked a code: a second verify in it does
     * nothing, so it can't use the code twice or put its own message over
     * the first one's. Not kept between requests.
     */
    protected bool $checkedNow = false;

    /** The last of the six digits typed verifies by itself. */
    public function updatedOtp()
    {
        $entered = implode('', array_map(fn ($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp));
        if ($this->step === 'otp' && strlen($entered) === 6 && ctype_digit($entered)) {
            return $this->verifyOtp();
        }
    }

    /**
     * Step 2 — verify the code, then sign in and go to the landing page
     * (admin → quick links; sub-admin → their first granted page).
     */
    public function verifyOtp()
    {
        // The page's own auto-verify and updatedOtp() can both come in one
        // request: the code is checked once.
        if ($this->checkedNow) {
            return;
        }
        $this->checkedNow = true;

        // Errors stay between requests: the last try's message must not stand
        // in front of this one's.
        $this->resetValidation('otp');

        $entered = implode('', array_map(fn ($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp));

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
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
            return;
        }

        $this->pendingUserId = null;

        // Panel-specific guard: signing in here never touches the super-admin
        // or accounts sessions in the same browser.
        return $this->signIn($user);
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

        $notSent = null;
        if (!$this->send($user, $notSent, 'otp')) {
            return;
        }

        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->resetValidation('otp');

        if ($notSent) {
            $this->addError('otp', $notSent);
        }
    }

    public function timerFinished(): void
    {
        $this->canResend = true;
    }

    public function backToLogin(): void
    {
        $this->step          = 'credentials';
        $this->otp           = ['', '', '', '', '', ''];
        $this->pendingUserId = null;
        $this->resetValidation();
    }

    /**
     * Send the code by email and to the registered mobile. False when there is
     * no code to enter (a lockout, or no request could be opened) — the error
     * is on $errorField then. $notSent says why, when a request was opened
     * but the code reached no one.
     */
    private function send(User $user, ?string &$notSent, string $errorField = 'email'): bool
    {
        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $to = clone $user;
        $to->email = User::cleanEmail($user->email);

        $whatsApp        = SchoolAdminOtp::whatsAppTo($user);
        $this->hasMobile = $whatsApp !== [];
        $delivery        = null;

        AuthThrottle::sent($user);
        try {
            $this->otpChallenge = OtpMailService::sendOtp($to, 'School Admin', $this->otpChallenge, $whatsApp, $delivery);
        } catch (OtpDeliveryException $e) {
            // The request is open: Resend OTP can try again in 2 minutes.
            $this->otpChallenge = $e->challenge;
            logger()->warning('School admin OTP not delivered', ['user_id' => $user->id, 'delivery' => $delivery]);
            $notSent = SchoolAdminOtp::whyNotSent($delivery);
        } catch (\Throwable $e) {
            // A lockout must stop the flow — and with no request opened there
            // is no code to enter either.
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            if (!$this->otpLockedUntil) {
                logger()->error('OTP send failed during admin login: ' . $e->getMessage());
            }
            $this->addError($errorField, $this->otpLockedUntil ? $e->getMessage() : 'Could not make a sign-in code: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Sign the admin guard in to this user and send them to their landing page.
     *
     * A browser that already has someone signed in (a school, or the super-admin
     * who opened a school from Schools) keeps its session id, as Schools → Login
     * does: a new id let a request still in flight from a tab of the old school
     * come back with the old cookie and put the browser back on that school.
     * The page landed on tells the browser's other admin tabs, which follow to
     * the new school (partials.school-login-sync). A fresh browser gets a new
     * session id as any sign-in does.
     */
    private function signIn(User $user)
    {
        $guard = Auth::guard('admin');

        if ($guard->check() || Auth::guard('superadmin')->check() || Auth::guard('accounts')->check()) {
            session()->put($guard->getName(), $user->getAuthIdentifier());
            $guard->setUser($user);
            event(new \Illuminate\Auth\Events\Login('admin', $user, false));
        } else {
            $guard->login($user);
        }
        // A login made in the installed app: from now on it opens for this account.
        PanelApp::rememberAppLogin('admin', $user);

        $landingRoute = 'admin.quick-links';
        if ($user->role === 'sub-admin') {
            $permissions  = (array) $user->permissions;
            $landingRoute = $permissions[0] ?? 'admin.profile';
        }

        $url = route($landingRoute, ['organization' => $user->organization_id]);

        return redirect($url)
            ->with('success', 'Login successful.')
            ->with('school-login', ['org' => (string) $user->organization_id, 'url' => $url]);
    }

    /** The school admin or sub-admin account with this email. */
    public static function accountByEmail(string $email): ?User
    {
        return User::where('email', $email)->whereIn('role', ['admin', 'sub-admin'])->first();
    }

    /** Why this account may not come in, or null when it may. */
    public static function refusal(User $user): ?string
    {
        if (!$user->organization_id) {
            return 'No school is assigned to this account.';
        }

        // Scoped sub-admins must be active to sign in.
        if ($user->role === 'sub-admin' && !$user->is_active) {
            return 'Your account is switched off. Ask your school admin to switch it on in Users.';
        }

        return null;
    }

    /** The account this page checked the password of, while it may still come in. */
    private function pendingUser(): ?User
    {
        if (!$this->pendingUserId) {
            return null;
        }

        $user = User::whereKey($this->pendingUserId)
            ->whereIn('role', ['admin', 'sub-admin'])
            ->first();

        return $user && !self::refusal($user) ? $user : null;
    }

    /** No checked password behind the code step: back to the login. */
    private function expire(): void
    {
        $this->step          = 'credentials';
        $this->otp           = ['', '', '', '', '', ''];
        $this->pendingUserId = null;
        $this->addError('email', 'This sign-in has expired. Log in again.');
    }

    public function render()
    {
        return view('livewire.admin.login')->layout('components.layouts.fullscreen');
    }
}
