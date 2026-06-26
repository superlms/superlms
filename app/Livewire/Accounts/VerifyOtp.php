<?php

namespace App\Livewire\Accounts;

use App\Services\OtpMailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerifyOtp extends Component
{
    public $otp = ['', '', '', '', '', ''];
    public int $countdown = 120;
    public bool $canResend = false;

    public function mount()
    {
        if (!Auth::check() || Auth::user()->role !== 'accounts') {
            return redirect()->route('accounts.login');
        }

        if (session('accounts_otp_verified')) {
            return redirect()->route('accounts.dashboard', ['organization' => Auth::user()->organization_id]);
        }
    }

    public function updatedOtp(): void
    {
        $enteredOtp = implode('', $this->otp);
        if (strlen($enteredOtp) === 6 && ctype_digit($enteredOtp)) {
            $this->verifyOtp();
        }
    }

    public function verifyOtp()
    {
        $enteredOtp = implode('', $this->otp);

        if (strlen($enteredOtp) !== 6) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            return;
        }

        $user = Auth::user();

        try {
            OtpMailService::verifyOtp($user, $enteredOtp);
            session(['accounts_otp_verified' => true]);

            return redirect()->route('accounts.dashboard', ['organization' => $user->organization_id])
                ->with('success', 'Login successful.');
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

        $user = Auth::user();

        try {
            OtpMailService::sendOtp($user, 'Accounts Panel');
            $this->countdown = 120;
            $this->canResend = false;
            $this->otp = ['', '', '', '', '', ''];
            session()->flash('success', 'OTP resent to your email.');
        } catch (\Exception $e) {
            $this->addError('otp', 'Failed to resend OTP: ' . $e->getMessage());
        }
    }

    public function timerFinished(): void
    {
        $this->canResend = true;
    }

    public function render()
    {
        return view('livewire.accounts.verify-otp')->layout('components.layouts.fullscreen');
    }
}
