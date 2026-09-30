<?php

namespace App\Livewire\SuperAdmin;

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
            ['email.required' => 'The email field is required.',
             'email.email'    => 'Please enter a valid email address.']
        );

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('email', 'No super admin account found with this email address.');
            return;
        }

        // Same rule as the login: an inactive sub super-admin can't get in.
        if ($user->role === 'sub-super-admin' && !$user->is_active) {
            $this->addError('email', 'Your account is inactive. Please contact the administrator.');
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin Panel', $this->otpChallenge);
        } catch (\Throwable $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('email', $e->getMessage());
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
        $entered = implode('', $this->otp);

        if (strlen($entered) !== 6 || !ctype_digit($entered)) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            return;
        }

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('otp', 'Unable to verify. Please start over.');
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
            $this->addError('otp', 'Unable to resend OTP. Please go back and try again.');
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin Panel', $this->otpChallenge);
        } catch (\Throwable $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
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
        $this->validate(
            [
                'password'              => ['required', 'min:8', 'max:16', 'regex:/^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>]).+$/'],
                'password_confirmation' => 'required|same:password',
            ],
            [
                'password.required'              => 'The password field is required.',
                'password.min'                   => 'Password must be at least 8 characters.',
                'password.max'                   => 'Password must not exceed 16 characters.',
                'password.regex'                 => 'Password must contain at least 1 number and 1 special character.',
                'password_confirmation.required' => 'Please confirm your password.',
                'password_confirmation.same'     => 'Passwords do not match.',
            ]
        );

        if (!Cache::get('super_admin_otp_verified_' . $this->email)) {
            $this->addError('password', 'Session expired. Please start the process again.');
            $this->step = 'email';
            return;
        }

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('password', 'User not found. Please start the process again.');
            $this->step = 'email';
            return;
        }

        $user->rememberPlainPassword($this->password);
        $user->update(['password' => Hash::make($this->password)]);

        Cache::forget('super_admin_otp_verified_' . $this->email);

        return redirect()->route('super-admin.login')
            ->with('success', 'Password reset successfully. Please login with your new password.');
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
