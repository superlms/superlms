<?php

namespace App\Livewire\Accounts;

use App\Services\OtpMailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerifyOtp extends Component
{
    public $otp = ['', '', '', '', '', ''];

    /** Unix timestamp (server clock) before which Resend OTP stays disabled. */
    public int $resendAvailableAt = 0;

    private const RESEND_COOLDOWN = 120;

    public function mount()
    {
        if (!Auth::check() || Auth::user()->role !== 'accounts') {
            return redirect()->route('accounts.login');
        }

        if (session('accounts_otp_verified')) {
            return redirect()->route('accounts.dashboard', ['organization' => Auth::user()->organization_id]);
        }

        // OTP was just sent during login — start the resend cooldown.
        $this->resendAvailableAt = now()->getTimestamp() + self::RESEND_COOLDOWN;
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
        // Server-side cooldown guard — independent of any client timer state, so
        // a single click always works the moment the cooldown has elapsed.
        if (now()->getTimestamp() < $this->resendAvailableAt) {
            return;
        }

        $user = Auth::user();

        try {
            OtpMailService::sendOtp($user, 'Accounts Panel');
            $this->resendAvailableAt = now()->getTimestamp() + self::RESEND_COOLDOWN;
            $this->otp = ['', '', '', '', '', ''];
            session()->flash('success', 'OTP resent to your email.');
        } catch (\Exception $e) {
            $this->addError('otp', 'Failed to resend OTP: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.accounts.verify-otp')->layout('components.layouts.fullscreen');
    }
}
