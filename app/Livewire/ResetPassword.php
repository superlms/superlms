<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

class ResetPassword extends Component
{
    public int $step = 1;
    public string $email = '';
    public $otp = ['', '', '', '', '', ''];
    public string $password = '';
    public string $password_confirmation = '';
    public bool $showPassword = false;
    public bool $showConfirmPassword = false;

    /** Seconds a user must wait before a fresh OTP can be requested. */
    private const RESEND_COOLDOWN = 120;

    /**
     * Unix timestamp at which "Resend OTP" unlocks. Deliberately an absolute
     * deadline rather than a ticking counter: the browser recomputes the
     * remaining seconds from it, so a Livewire re-render (a wrong OTP, say)
     * can no longer restart the countdown from the top.
     */
    public int $resendAvailableAt = 0;

    /**
     * Unix timestamp the OTP lockout lifts at (0 = not locked out). Absolute so
     * the browser can tick it down without a re-render restarting it.
     */
    public int $otpLockedUntil = 0;

    public function sendOtp(): void
    {
        $this->validate(['email' => 'required|email']);

        $user = User::where('email', $this->email)
            ->whereIn('role', ['admin', 'super-admin', 'sub-admin'])
            ->first();

        if (!$user) {
            $this->addError('email', 'No admin account found with this email.');
            return;
        }

        try {
            OtpMailService::sendOtp($user, 'Admin Panel');
            $this->step = 2;
            $this->resendAvailableAt = now()->addSeconds(self::RESEND_COOLDOWN)->timestamp;
            $this->dispatch('start-countdown', resendAt: $this->resendAvailableAt);
        } catch (\Exception $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError($this->step === 2 ? 'otp' : 'email', $e->getMessage());
        }
    }

    public function updatedOtp(): void
    {
        $enteredOtp = implode('', $this->otp);
        if (strlen($enteredOtp) === 6 && ctype_digit($enteredOtp)) {
            $this->verifyOtp();
        }
    }

    #[On('verifyOtp')]
    public function verifyOtp(): void
    {
        $enteredOtp = implode('', $this->otp);

        if (strlen($enteredOtp) !== 6) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            return;
        }

        $user = User::where('email', $this->email)
            ->whereIn('role', ['admin', 'super-admin', 'sub-admin'])
            ->first();

        if (!$user) {
            $this->addError('otp', 'User not found.');
            return;
        }

        try {
            OtpMailService::verifyOtp($user, $enteredOtp);
            $this->step = 3;
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
        }
    }

    public function resetPassword()
    {
        $this->validate([
            'password' => [
                'required',
                'min:8',
                'max:16',
                'confirmed',
                'regex:/[0-9]/',
                'regex:/[^a-zA-Z0-9]/',
            ],
        ], [
            'password.regex' => 'Password must contain at least one number and one special character.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 16 characters.',
        ]);

        $user = User::where('email', $this->email)
            ->whereIn('role', ['admin', 'super-admin', 'sub-admin'])
            ->first();

        if (!$user) {
            $this->addError('password', 'User not found.');
            return;
        }

        $user->rememberPlainPassword($this->password);
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        OtpMailService::clearOtp($user);

        session()->flash('message', 'Password reset successfully!');
        return redirect()->route('admin.login');
    }

    public function resendOtp(): void
    {
        // Enforced here rather than trusting the browser's countdown.
        if (now()->timestamp < $this->resendAvailableAt) {
            return;
        }

        // A new code is on its way, so the stale one must not linger in the
        // boxes. The view clears them on click too, so it looks instant.
        $this->otp = ['', '', '', '', '', ''];
        $this->resetErrorBag('otp');

        $this->sendOtp();
    }

    public function render()
    {
        return view('livewire.reset-password')->layout('components.layouts.fullscreen');
    }
}
