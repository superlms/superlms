<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Login extends Component
{
    public string $step = 'credentials'; // 'credentials' | 'otp'

    public $email    = '';
    public $password = '';
    public bool $showPassword = false;

    // OTP step
    public string $otpSentTo = '';
    public array  $otp       = ['', '', '', '', '', ''];
    public int    $countdown = 120;
    public bool   $canResend = false;

    protected $rules = [
        'email'    => 'required|email',
        'password' => 'required',
    ];

    protected $messages = [
        'email.required'    => 'The email field is required.',
        'email.email'       => 'The email must be a valid email address.',
        'password.required' => 'The password field is required.',
    ];

    public function toggleShowPassword(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    /**
     * Step 1 — verify credentials, run the same role / org / active checks the
     * old flow did, then send an OTP and switch to the OTP step. The user is
     * NOT logged in until they successfully verify the OTP.
     */
    public function login()
    {
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', 'Email does not exist.');
            return;
        }

        if (!Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Incorrect password.');
            return;
        }

        if (!in_array($user->role, ['admin', 'sub-admin'], true)) {
            $this->addError('email', 'You do not have school admin access.');
            return;
        }

        if (!$user->organization_id) {
            $this->addError('email', 'No organization assigned to this account.');
            return;
        }

        // Scoped sub-admins must be active to sign in.
        if ($user->role === 'sub-admin' && !$user->is_active) {
            $this->addError('email', 'Your account is inactive. Please contact the administrator.');
            return;
        }

        try {
            OtpMailService::sendOtp($user, 'School Admin');
        } catch (\Exception $e) {
            logger()->error('OTP send failed during admin login: ' . $e->getMessage());
            $this->addError('email', 'Failed to send OTP. Please try again.');
            return;
        }

        $this->otpSentTo = $user->email;
        $this->otp       = ['', '', '', '', '', ''];
        $this->countdown = 120;
        $this->canResend = false;
        $this->step      = 'otp';
    }

    /**
     * Auto-submit when the user has filled all six digits.
     */
    public function updatedOtp(): void
    {
        $entered = implode('', array_map(fn ($d) => is_scalar($d) ? (string) $d : '', $this->otp));
        if (strlen($entered) === 6 && ctype_digit($entered)) {
            $this->verifyOtp();
        }
    }

    /**
     * Step 2 — verify OTP, then log the user in and redirect to their landing
     * route (admin → quick-links; sub-admin → first granted permission).
     */
    public function verifyOtp()
    {
        $entered = implode('', array_map(fn ($d) => is_scalar($d) ? (string) $d : '', $this->otp));

        if (strlen($entered) !== 6 || !ctype_digit($entered)) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            return;
        }

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('otp', 'Session expired. Please login again.');
            $this->step = 'credentials';
            return;
        }

        try {
            OtpMailService::verifyOtp($user, $entered);
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->addError('otp', $e->getMessage());
            return;
        }

        Auth::login($user);

        $landingRoute = 'admin.quick-links';
        if ($user->role === 'sub-admin') {
            $permissions  = (array) $user->permissions;
            $landingRoute = $permissions[0] ?? 'admin.profile';
        }

        return redirect()->route($landingRoute, ['organization' => $user->organization_id])
            ->with('success', 'Login successful.');
    }

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->step = 'credentials';
            return;
        }

        try {
            OtpMailService::sendOtp($user, 'School Admin');
            $this->otp       = ['', '', '', '', '', ''];
            $this->countdown = 120;
            $this->canResend = false;
            $this->resetValidation('otp');
        } catch (\Exception $e) {
            $this->addError('otp', 'Failed to resend OTP: ' . $e->getMessage());
        }
    }

    public function timerFinished(): void
    {
        $this->canResend = true;
    }

    public function backToLogin(): void
    {
        $this->step      = 'credentials';
        $this->otp       = ['', '', '', '', '', ''];
        $this->otpSentTo = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.login')->layout('components.layouts.fullscreen');
    }
}
