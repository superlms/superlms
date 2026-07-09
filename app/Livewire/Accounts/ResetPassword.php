<?php

namespace App\Livewire\Accounts;

use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Hash;
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
    public int $countdown = 120;
    public bool $canResend = false;

    public function sendOtp(): void
    {
        $this->validate(['email' => 'required|email']);

        $user = User::where('email', $this->email)->where('role', 'accounts')->first();

        if (!$user) {
            $this->addError('email', 'No accounts user found with this email.');
            return;
        }

        try {
            OtpMailService::sendOtp($user, 'Accounts Panel');
            $this->step = 2;
            $this->countdown = 120;
            $this->canResend = false;
        } catch (\Exception $e) {
            $this->addError('email', 'Failed to send OTP: ' . $e->getMessage());
        }
    }

    public function verifyOtp(): void
    {
        $enteredOtp = implode('', $this->otp);

        if (strlen($enteredOtp) !== 6) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            return;
        }

        $user = User::where('email', $this->email)->where('role', 'accounts')->first();

        if (!$user) {
            $this->addError('otp', 'User not found.');
            return;
        }

        try {
            OtpMailService::verifyOtp($user, $enteredOtp);
            $this->step = 3;
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->addError('otp', $e->getMessage());
        }
    }

    public function updatedOtp(): void
    {
        $enteredOtp = implode('', $this->otp);
        if (strlen($enteredOtp) === 6 && ctype_digit($enteredOtp)) {
            $this->verifyOtp();
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

        $user = User::where('email', $this->email)->where('role', 'accounts')->first();

        if (!$user) {
            $this->addError('email', 'User not found.');
            return;
        }

        $user->rememberPlainPassword($this->password);
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        OtpMailService::clearOtp($user);

        return redirect()->route('accounts.login')
            ->with('success', 'Password reset successfully. Please login with your new password.');
    }

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }
        $this->sendOtp();
    }

    public function timerFinished(): void
    {
        $this->canResend = true;
    }

    public function render()
    {
        return view('livewire.accounts.reset-password')->layout('components.layouts.fullscreen');
    }
}
