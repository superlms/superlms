<?php

namespace App\Livewire\SuperAdmin;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use App\Services\WhatsAppService;
use App\Support\PanelApp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Login extends Component
{
    public string $step = 'credentials'; // 'credentials' | 'otp'

    public $email    = '';
    public $password = '';
    public $otpSentTo = '';

    /**
     * Where this sign-in's code went, one row per channel, for the OTP screen:
     * [['channel' => 'Email'|'WhatsApp', 'to' => shown address, 'ok' => bool,
     *   'error' => why it didn't go]].
     */
    public array $otpChannels = [];

    // OTP step — array of 6 single digits (matches accounts VerifyOtp pattern)
    public array $otp = ['', '', '', '', '', ''];
    public int   $countdown = 120;
    public bool  $canResend = false;

    /** This sign-in's OTP request — only its own code signs in here. */
    #[Locked]
    public ?string $otpChallenge = null;

    /**
     * Unix timestamp the OTP lockout lifts at (0 = not locked out). Absolute so
     * the browser can tick it down without a re-render restarting it.
     */
    public int $otpLockedUntil = 0;

    public function login()
    {
        // Spaces or invisible characters that came with a pasted address.
        $this->email = User::cleanEmail($this->email);

        $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Enter your email address.',
            'email.email'       => 'This is not a valid email address — it should look like name@example.com.',
            'password.required' => 'Enter your password.',
        ]);

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->addError('email', 'No Super Admin account uses this email address.');
            return;
        }

        if (!Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Wrong password for this email. Check Caps Lock, or use Forgot password.');
            return;
        }

        $allowedEmails = ['superlms.india@gmail.com', 'superlmsofficial@gmail.com'];

        // Main super-admin: must be a whitelisted email with the super-admin role.
        $isMainSuperAdmin = $user->role === 'super-admin' && in_array($this->email, $allowedEmails);

        // Sub-super-admin: created from the Users panel, identified by role.
        $isSubSuperAdmin  = $user->role === 'sub-super-admin';

        if (!$isMainSuperAdmin && !$isSubSuperAdmin) {
            $this->addError('email', 'This email is not allowed into the Super Admin panel.');
            return;
        }

        if ($isSubSuperAdmin && !$user->is_active) {
            $this->addError('email', 'This account is switched off. The main super admin can switch it on in Users.');
            return;
        }

        // 2FA temporarily disabled (ZeptoMail out of credit) — sign in directly.
        // The OTP step below is left intact; flip services.otp.login_enabled back
        // on to restore it.
        if (!OtpMailService::loginOtpEnabled()) {
            Auth::guard('superadmin')->login($user);
            // A login made in the installed app: from now on it opens for this account.
            PanelApp::rememberAppLogin('superadmin', $user);
            return redirect()->route('super-admin.quick-links')
                ->with('success', 'Login successful.');
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        // The OTP request is opened inside sendOtp BEFORE anything is sent, so
        // even if delivery fails we can still continue. The same code goes on
        // WhatsApp to the account's mobile and alternative mobile; one of them
        // arriving is enough. TEMPORARY: if nothing goes (e.g. ZeptoMail
        // outage and WhatsApp down), log the OTP so a super-admin can sign in
        // from the logs. Remove this catch once email is healthy.
        $delivery = null;
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin', $this->otpChallenge, self::whatsAppNumbers($user), $delivery);
        } catch (OtpDeliveryException $e) {
            $this->otpChallenge = $e->challenge;
            \Log::warning('SUPERADMIN OTP not delivered; OTP for ' . $user->email . ' = ' . $e->otp . ' (expires 2 min)', ['err' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // A lockout must stop the flow — and with no request opened there
            // is no code to enter either.
            if ($lockedUntil = OtpMailService::lockedUntil($user)) {
                $this->otpLockedUntil = $lockedUntil;
                $this->addError('email', $e->getMessage());
                return;
            }
            logger()->error('OTP send failed during super-admin login: ' . $e->getMessage());
            $this->addError('email', 'Could not make a sign-in code: ' . $e->getMessage());
            return;
        }

        $this->otpSentTo   = $user->email;
        $this->otpChannels = self::channels($delivery);
        $this->otp        = ['', '', '', '', '', ''];
        $this->countdown  = 120;
        $this->canResend  = false;
        $this->step       = 'otp';
    }

    public function verifyOtp()
    {
        // Idempotency guard: if a previous (possibly duplicated) request already
        // signed us in, just continue to the dashboard instead of re-consuming
        // the OTP — which would throw and strand the user on a stale page.
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('super-admin.quick-links');
        }

        // Errors stay between requests: the last try's message must not stand
        // in front of this one's.
        $this->resetValidation('otp');

        $digits  = array_map(fn($d) => is_scalar($d) ? trim((string) $d) : '', $this->otp);
        $entered = implode('', $digits);

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
            $this->addError('otp', 'This sign-in has expired. Go back and log in again.');
            $this->step = 'credentials';
            return;
        }

        try {
            OtpMailService::verifyOtp($user, $entered, $this->otpChallenge);
            // Panel-specific guard: signing in here never touches the admin
            // or accounts sessions in the same browser.
            Auth::guard('superadmin')->login($user);
            // A login made in the installed app: from now on it opens for this account.
            PanelApp::rememberAppLogin('superadmin', $user);
            return redirect()->route('super-admin.quick-links');
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
        }
    }

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }

        $user = User::superPanelByEmail($this->email);

        if (!$user) {
            $this->step = 'credentials';
            return;
        }

        // The code goes to the address as it should read — an old save can still
        // carry invisible characters the mail provider refuses. Not saved.
        $user->email = User::cleanEmail($user->email);

        $delivery = null;
        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'Super Admin', $this->otpChallenge, self::whatsAppNumbers($user), $delivery);
            $this->otp       = ['', '', '', '', '', ''];
            $this->countdown = 120;
            $this->canResend = false;
            $this->resetValidation('otp');
        } catch (OtpDeliveryException $e) {
            // TEMPORARY, as in login(): the new code is in the log.
            \Log::warning('SUPERADMIN OTP resend not delivered; OTP for ' . $user->email . ' = ' . $e->otp . ' (expires 2 min)', ['err' => $e->getMessage()]);
            $this->addError('otp', 'The new code could not be sent anywhere — the reasons are below.');
        } catch (\Exception $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
        }

        if ($delivery) {
            $this->otpChannels = self::channels($delivery);
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
        $this->otpChannels = [];
        $this->resetValidation();
    }

    /** The account's mobile and alternative mobile — the code goes on WhatsApp to each. */
    private static function whatsAppNumbers(User $user): array
    {
        return array_values(array_filter([$user->mobile_number, $user->alternative_mobile], 'filled'));
    }

    /** sendOtp()'s delivery report as rows for the OTP screen. */
    private static function channels(?array $delivery): array
    {
        if (!$delivery) {
            return [];
        }

        $rows = [[
            'channel' => 'Email',
            'to'      => $delivery['email']['to'],
            'ok'      => $delivery['email']['ok'],
            'error'   => $delivery['email']['error'],
        ]];

        foreach ($delivery['whatsapp'] as $wa) {
            $rows[] = [
                'channel' => 'WhatsApp',
                'to'      => self::maskMobile($wa['to']),
                'ok'      => $wa['ok'],
                'error'   => $wa['error'],
            ];
        }

        return $rows;
    }

    /** "+91 ••••••8563" — enough to tell which phone to look at. */
    private static function maskMobile(?string $mobile): string
    {
        $digits = WhatsAppService::normalizePhone($mobile) ?? preg_replace('/\D+/', '', (string) $mobile);

        return '+91 ••••••' . substr($digits, -4);
    }

    public function render()
    {
        return view('livewire.super-admin.login')->layout('components.layouts.fullscreen');
    }
}
