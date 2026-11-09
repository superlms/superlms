<?php

namespace App\Livewire;

use App\Exceptions\OtpDeliveryException;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\SuperAdmin\ForgotPassword;
use App\Models\User;
use App\Services\OtpMailService;
use App\Support\AuthThrottle;
use App\Support\SchoolAdminOtp;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The school admin / sub-admin Forgot password (/reset-password), in the
 * Super Admin's look and flow: email → a code by email and on WhatsApp to the
 * registered mobile number → a new password. The Super Admin has a page of
 * its own (SuperAdmin\ForgotPassword); its accounts are not reset here.
 */
class ResetPassword extends Component
{
    public string $step = 'email'; // email | otp | password

    public string $email = '';

    // OTP — array of 6 single digits
    public array $otp       = ['', '', '', '', '', ''];
    public int   $countdown = 120;
    public bool  $canResend = false;

    /** The account has a registered mobile, so the code went there too. */
    public bool $hasMobile = false;

    /**
     * This reset's OTP request — only its own code is accepted here, and once
     * that code is verified, only this request lets a new password through
     * (once, within 10 minutes).
     */
    #[Locked]
    public ?string $otpChallenge = null;

    /**
     * The account the code was sent to — the only one whose password this page
     * can set. Locked: the email field can be changed in the browser, this
     * can't, so a code verified for one account never resets another.
     */
    #[Locked]
    public ?int $pendingUserId = null;

    /**
     * Unix timestamp the OTP lockout lifts at (0 = not locked out). Absolute so
     * the browser can tick it down without a re-render restarting it.
     */
    public int $otpLockedUntil = 0;

    public string $password = '';
    public string $password_confirmation = '';

    // ─── Step 1: Email ────────────────────────────────────────────────────────

    public function submitEmail(): void
    {
        // Spaces or invisible characters that came with a pasted address.
        $this->email = User::cleanEmail($this->email);

        $this->validate(
            ['email' => 'required|email'],
            ['email.required' => 'Enter your email address.',
             'email.email'    => 'This is not a valid email address — it should look like name@example.com.']
        );

        // Look-ups are counted on the server, so the answer below can't be
        // used to go through a list of addresses.
        if ($wait = AuthThrottle::lookupWait()) {
            $this->addError('email', AuthThrottle::message($wait));
            return;
        }
        AuthThrottle::lookedUp();

        $user = AdminLogin::accountByEmail($this->email);

        if (!$user) {
            $this->addError('email', 'No school admin account uses this email address.');
            return;
        }

        // Same rules as the login: a switched-off sub-admin can't get in.
        if ($why = AdminLogin::refusal($user)) {
            $this->addError('email', $why);
            return;
        }

        if ($wait = AuthThrottle::sendWait($user)) {
            $this->addError('email', AuthThrottle::sendMessage($wait));
            return;
        }

        if (!$this->send($user, 'email')) {
            return;
        }

        $this->pendingUserId = $user->id;
        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->step      = 'otp';
    }

    // ─── Step 2: OTP Verify ───────────────────────────────────────────────────

    /** The last of the six digits typed verifies by itself. */
    public function updatedOtp(): void
    {
        $entered = implode('', array_map(fn ($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp));
        if ($this->step === 'otp' && strlen($entered) === 6 && ctype_digit($entered)) {
            $this->verifyOtp();
        }
    }

    /**
     * Set once this request has checked a code: updatedOtp() and the page's
     * own auto-verify can both come in one request, and the second must not
     * put its message over the first one's. Not kept between requests.
     */
    protected bool $checkedNow = false;

    public function verifyOtp(): void
    {
        if ($this->step !== 'otp' || $this->checkedNow) {
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

        // The account the code was sent to — never the email field.
        $user = $this->pendingUser();

        if (!$user) {
            $this->startAgain('This reset has expired, or the account can no longer sign in. Start again.');
            return;
        }

        try {
            // Kept, marked verified, for resetPassword() to use up.
            OtpMailService::verifyOtp($user, $entered, $this->otpChallenge, forReset: true);
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
            return;
        }

        $this->otp  = ['', '', '', '', '', ''];
        $this->step = 'password';
    }

    // ─── Resend OTP ───────────────────────────────────────────────────────────

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }

        $user = $this->pendingUser();

        if (!$user) {
            $this->startAgain('This reset has expired. Start again.');
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

        if (!$this->send($user, 'otp')) {
            return;
        }

        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->resetValidation('otp');
    }

    public function timerFinished(): void
    {
        $this->canResend = true;
    }

    /** "Change Email": the code step is given up. */
    public function changeEmail(): void
    {
        $this->step          = 'email';
        $this->otp           = ['', '', '', '', '', ''];
        $this->pendingUserId = null;
        $this->resetValidation();
    }

    // ─── Step 3: Reset Password ───────────────────────────────────────────────

    public function resetPassword()
    {
        // Every rule the password breaks, each saying what to change — and
        // only those: a rule it already meets isn't listed.
        $this->resetValidation();

        foreach (ForgotPassword::unmetRules($this->password) as $rule) {
            $this->addError('password', $rule);
        }

        if ($this->password_confirmation === '') {
            $this->addError('password_confirmation', 'Type the new password again here.');
        } elseif ($this->password_confirmation !== $this->password) {
            $this->addError('password_confirmation', 'This does not match the new password above.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        // Only the account whose code was verified on this page, once, within
        // 10 minutes of verifying it.
        $user = $this->pendingUser();

        if (!$user || !OtpMailService::consumeVerified($user, $this->otpChallenge)) {
            $this->startAgain('The code was not verified here, or was verified more than 10 minutes ago. Start again from your email.');
            return;
        }

        $user->rememberPlainPassword($this->password);
        $user->update(['password' => Hash::make($this->password)]);

        $this->pendingUserId = null;
        $this->password = $this->password_confirmation = '';

        return redirect()->route('admin.login')
            ->with('success', 'Password reset successfully. Please login with your new password.');
    }

    /**
     * Send the code by email and to the registered mobile. False, with the
     * reason on $errorField, when it reached no one or the account is locked out.
     */
    private function send(User $user, string $errorField): bool
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
        } catch (\Throwable $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            if ($e instanceof OtpDeliveryException) {
                logger()->warning('School admin reset OTP not delivered', ['user_id' => $user->id, 'delivery' => $delivery]);
            }
            $this->addError($errorField, $e instanceof OtpDeliveryException && $delivery
                ? SchoolAdminOtp::whyNotSent($delivery)
                : $e->getMessage());
            return false;
        }

        return true;
    }

    /** The account the code was sent to on this page, while it may still sign in. */
    private function pendingUser(): ?User
    {
        if (!$this->pendingUserId) {
            return null;
        }

        $user = User::whereKey($this->pendingUserId)
            ->whereIn('role', ['admin', 'sub-admin'])
            ->first();

        return $user && !AdminLogin::refusal($user) ? $user : null;
    }

    /** Back to the email step, saying why. */
    private function startAgain(string $why): void
    {
        $this->step          = 'email';
        $this->otp           = ['', '', '', '', '', ''];
        $this->pendingUserId = null;
        $this->password      = $this->password_confirmation = '';
        $this->resetValidation();
        $this->addError('email', $why);
    }

    public function render()
    {
        return view('livewire.reset-password')->layout('components.layouts.fullscreen');
    }
}
