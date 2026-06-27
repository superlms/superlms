<?php

namespace App\Livewire\SuperAdmin;

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
    public $otpSentTo = '';

    // OTP step — array of 6 single digits (matches accounts VerifyOtp pattern)
    public array $otp = ['', '', '', '', '', ''];
    public int   $countdown = 120;
    public bool  $canResend = false;

    public function login()
    {
        $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'The email field is required.',
            'email.email'       => 'Must be a valid email address.',
            'password.required' => 'The password field is required.',
        ]);

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', 'Email does not exist.');
            return;
        }

        if (!Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Incorrect password.');
            return;
        }

        $allowedEmails = ['superlms.india@gmail.com', 'superlmsofficial@gmail.com'];

        // Main super-admin: must be a whitelisted email with the super-admin role.
        $isMainSuperAdmin = $user->role === 'super-admin' && in_array($this->email, $allowedEmails);

        // Sub-super-admin: created from the Users panel, identified by role.
        $isSubSuperAdmin  = $user->role === 'sub-super-admin';

        if (!$isMainSuperAdmin && !$isSubSuperAdmin) {
            $this->addError('email', 'You do not have super-admin access.');
            return;
        }

        if ($isSubSuperAdmin && !$user->is_active) {
            $this->addError('email', 'Your account is inactive. Please contact the administrator.');
            return;
        }

        // The OTP is saved to the user inside sendOtp BEFORE the email is sent,
        // so even if email delivery fails we can still continue. TEMPORARY: if
        // delivery fails (e.g. ZeptoMail outage), log the OTP so a super-admin
        // can sign in from the logs. Remove this catch once email is healthy.
        try {
            OtpMailService::sendOtp($user, 'Super Admin');
        } catch (\Throwable $e) {
            \Log::warning('SUPERADMIN OTP email failed; OTP for ' . $user->email . ' = ' . $user->otp . ' (expires 2 min)', ['err' => $e->getMessage()]);
        }

        $this->otpSentTo  = $user->email;
        $this->otp        = ['', '', '', '', '', ''];
        $this->countdown  = 120;
        $this->canResend  = false;
        $this->step       = 'otp';
    }

    public function updatedOtp(): void
    {
        $entered = implode('', array_map(fn($d) => is_scalar($d) ? (string) $d : '', $this->otp));
        if (strlen($entered) === 6 && ctype_digit($entered)) {
            $this->verifyOtp();
        }
    }

    public function verifyOtp()
    {
        $entered = implode('', array_map(fn($d) => is_scalar($d) ? (string) $d : '', $this->otp));

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
            Auth::login($user);
            return redirect()->route('super-admin.quick-links');
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->addError('otp', $e->getMessage());
        }
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
            OtpMailService::sendOtp($user, 'Super Admin');
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

    public function backToLogin()
    {
        $this->step      = 'credentials';
        $this->otp       = ['', '', '', '', '', ''];
        $this->otpSentTo = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.super-admin.login')->layout('components.layouts.fullscreen');
    }
}
