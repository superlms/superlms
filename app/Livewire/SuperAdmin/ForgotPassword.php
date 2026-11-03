<?php

namespace App\Livewire\SuperAdmin;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use App\Support\AuthThrottle;
use App\Support\OtpNumbers;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $step = 'email'; // email | otp | password

    public string $email = '';

    // OTP — array of 6 single digits (matches accounts VerifyOtp pattern)
    public array $otp = ['', '', '', '', '', ''];
    public int   $countdown = 120;
    public bool  $canResend = false;

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
    public bool   $showPassword = false;
    public bool   $showConfirmPassword = false;

    // ─── Step 1: Email ────────────────────────────────────────────────────────

    public function submitEmail()
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

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('email', 'No Super Admin account uses this email address.');
            return;
        }

        // Same rule as the login: an inactive sub super-admin can't get in.
        if ($user->role === 'sub-super-admin' && !$user->is_active) {
            $this->addError('email', 'This account is switched off. The main super admin can switch it on in Users.');
            return;
        }

        if ($wait = AuthThrottle::sendWait($user)) {
            $this->addError('email', AuthThrottle::sendMessage($wait));
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        $delivery = null;
        AuthThrottle::sent($user);
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin Panel', $this->otpChallenge, array_filter([OtpNumbers::primary($user)]), $delivery);
        } catch (\Throwable $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('email', self::sendProblem($e, $delivery));
            return;
        }

        $this->pendingUserId = $user->id;
        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->step      = 'otp';
    }

    // ─── Step 2: OTP Verify ───────────────────────────────────────────────────

    public function updatedOtp(): void
    {
        $entered = implode('', array_map(fn($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp));
        if ($this->step === 'otp' && strlen($entered) === 6 && ctype_digit($entered)) {
            $this->verifyOtp();
        }
    }

    public function verifyOtp()
    {
        // Errors stay between requests: the last try's message must not stand
        // in front of this one's.
        $this->resetValidation('otp');

        $entered = implode('', array_map(fn($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp));

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
            $this->startAgain('This reset has expired, or the account is no longer a Super Admin one. Start again.');
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

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        $delivery = null;
        AuthThrottle::sent($user);
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin Panel', $this->otpChallenge, array_filter([OtpNumbers::primary($user)]), $delivery);
        } catch (\Throwable $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', self::sendProblem($e, $delivery));
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

    /** "← Change Email": the code step is given up. */
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
        // One message per rule, the first one broken, saying what to change.
        $length = mb_strlen($this->password);
        $this->validate(
            [
                'password'              => ['bail', 'required', 'min:8', 'max:16', 'regex:/[0-9]/',
                    function ($attribute, $value, $fail) {
                        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', (string) $value)) {
                            $fail('Add at least one special character: ! @ # $ % ^ & * ( ) , . ? " : { } | < >');
                        }
                    }],
                'password_confirmation' => ['bail', 'required', 'same:password'],
            ],
            [
                'password.required'              => 'Enter a new password.',
                'password.min'                   => "Too short — {$length} characters. Use 8 to 16.",
                'password.max'                   => "Too long — {$length} characters. Use 8 to 16.",
                'password.regex'                 => 'Add at least one number (0–9).',
                'password_confirmation.required' => 'Type the new password again here.',
                'password_confirmation.same'     => 'This does not match the new password above.',
            ]
        );

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

        return redirect()->route('super-admin.login')
            ->with('success', 'Password reset successfully. Please login with your new password.');
    }

    /** The account the code was sent to on this page, while it is still a Super Admin one that may sign in. */
    private function pendingUser(): ?User
    {
        if (!$this->pendingUserId) {
            return null;
        }

        $user = User::whereKey($this->pendingUserId)
            ->whereIn('role', ['super-admin', 'sub-super-admin'])
            ->first();

        return $user && ($user->role !== 'sub-super-admin' || $user->is_active) ? $user : null;
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

    /** Why the code didn't go: the lockout as it reads, else each channel's own reason. */
    private static function sendProblem(\Throwable $e, ?array $delivery): string
    {
        if (!$e instanceof OtpDeliveryException || !$delivery) {
            return $e->getMessage();
        }

        $reasons = [];
        if (!empty($delivery['email']['error'])) {
            $reasons[] = 'email: ' . $delivery['email']['error'];
        }
        foreach ($delivery['whatsapp'] as $wa) {
            $reasons[] = 'WhatsApp: ' . $wa['error'];
        }

        return 'Could not send the code' . ($reasons ? ' — ' . implode('; ', $reasons) : '') . '. Try again in a minute.';
    }

    // ─── Toggles ──────────────────────────────────────────────────────────────

    public function togglePassword()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function toggleConfirmPassword()
    {
        $this->showConfirmPassword = !$this->showConfirmPassword;
    }

    public function render()
    {
        return view('livewire.super-admin.forgot-password')
            ->layout('components.layouts.fullscreen');
    }
}
