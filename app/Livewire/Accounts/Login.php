<?php

namespace App\Livewire\Accounts;

use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $email, $password;
    public $showPassword = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    protected $messages = [
        'email.required' => 'The email field is required.',
        'email.email' => 'The email must be a valid email address.',
        'password.required' => 'The password field is required.',
    ];

    public function toggleShowPassword()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function login()
    {
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', 'Email does not exist.');
            return;
        }

        // Panel-specific guard: signing in here never touches the admin or
        // super-admin sessions in the same browser.
        if (!Auth::guard('accounts')->attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('password', 'Incorrect password.');
            return;
        }

        if ($user->role !== 'accounts') {
            Auth::guard('accounts')->logout();
            $this->addError('email', 'You do not have accounts panel access.');
            return;
        }

        if (!$user->organization_id) {
            Auth::guard('accounts')->logout();
            $this->addError('email', 'No organization assigned to this account.');
            return;
        }

        // Clear any previous OTP verification so user must verify again
        session()->forget('accounts_otp_verified');

        // Generate and send OTP for 2-step verification
        try {
            OtpMailService::sendOtp($user, 'Accounts Panel');
        } catch (\Exception $e) {
            logger()->error('OTP send failed during login: ' . $e->getMessage());
            $this->addError('email', 'Failed to send OTP. Please try again.' . $e->getMessage());
            Auth::guard('accounts')->logout();
            return;
        }

        return redirect()->route('accounts.verify-otp')
            ->with('success', 'OTP sent to your email address.');
    }

    public function render()
    {
        return view('livewire.accounts.login')->layout('components.layouts.fullscreen');
    }
}
