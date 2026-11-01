<?php

namespace App\Livewire\SuperAdmin;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Cache;
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

    /** This reset's OTP request — only its own code is accepted here. */
    #[Locked]
    public ?string $otpChallenge = null;

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

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        $delivery = null;
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin Panel', $this->otpChallenge, null, $delivery);
        } catch (\Throwable $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('email', self::sendProblem($e, $delivery));
            return;
        }

        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->step      = 'otp';
    }

    // ─── Step 2: OTP Verify ───────────────────────────────────────────────────

    public function updatedOtp(): void
    {
        $entered = implode('', $this->otp);
        if (strlen($entered) === 6 && ctype_digit($entered)) {
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

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('otp', 'No Super Admin account uses this email any more. Start again.');
            $this->step = 'email';
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

        Cache::put('super_admin_otp_verified_' . $this->email, true, 600);

        $this->step = 'password';
    }

    // ─── Resend OTP ───────────────────────────────────────────────────────────

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('otp', 'No Super Admin account uses this email any more. Go back and change the email.');
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        $delivery = null;
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin Panel', $this->otpChallenge, null, $delivery);
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

        if (!Cache::get('super_admin_otp_verified_' . $this->email)) {
            $this->addError('password', 'The verified code is more than 10 minutes old. Start again from your email.');
            $this->step = 'email';
            return;
        }

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('password', 'No Super Admin account uses this email any more. Start again.');
            $this->step = 'email';
            return;
        }

        $user->rememberPlainPassword($this->password);
        $user->update(['password' => Hash::make($this->password)]);

        Cache::forget('super_admin_otp_verified_' . $this->email);

        return redirect()->route('super-admin.login')
            ->with('success', 'Password reset successfully. Please login with your new password.');
    }

    /** Why the code didn't go: the lockout as it reads, else the email's own reason. */
    private static function sendProblem(\Throwable $e, ?array $delivery): string
    {
        $email = $delivery['email'] ?? null;

        if ($e instanceof OtpDeliveryException && !empty($email['error'])) {
            return 'Could not send the code to ' . $email['to'] . ': ' . $email['error'] . '. Try again in a minute.';
        }

        return $e->getMessage();
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
