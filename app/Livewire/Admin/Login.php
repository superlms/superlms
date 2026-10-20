<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\OtpMailService;
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
    public bool $showPassword = false;

    // OTP step
    public string $otpSentTo = '';
    public array  $otp       = ['', '', '', '', '', ''];
    public int    $countdown = 120;
    public bool   $canResend = false;

    /** This sign-in's OTP request — only its own code signs in here. */
    #[Locked]
    public ?string $otpChallenge = null;

    /**
     * Unix timestamp the OTP lockout lifts at (0 = not locked out). Absolute so
     * the browser can tick it down without a re-render restarting it.
     */
    public int $otpLockedUntil = 0;

    protected $rules = [
        'email'    => 'required|email',
        'password' => 'required',
    ];

    protected $messages = [
        'email.required'    => 'The email field is required.',
        'email.email'       => 'The email must be a valid email address.',
        'password.required' => 'The password field is required.',
    ];

    /** The school already signed in on this browser, if any: [name, url] for the "Continue" line. */
    #[Locked]
    public array $signedIn = [];

    public function mount(): void
    {
        $current = Auth::guard('admin')->user();
        if ($current && $current->organization_id && in_array($current->role, ['admin', 'sub-admin'], true)) {
            $this->signedIn = [
                'name' => (string) ($current->organization->name ?? $current->name),
                'url'  => route('admin.quick-links', ['organization' => $current->organization_id]),
            ];
        }
    }

    /**
     * Sign the admin guard in to this user and send them to their landing page.
     *
     * A browser that already has someone signed in (a school, or the super-admin
     * who opened a school from Schools) keeps its session id, as Schools → Login
     * does: a new id let a request still in flight from a tab of the old school
     * come back with the old cookie and put the browser back on that school.
     * The page landed on tells the browser's other admin tabs, which follow to
     * the new school (partials.school-login-sync). A fresh browser gets a new
     * session id as any sign-in does.
     */
    private function signIn(User $user)
    {
        $guard = Auth::guard('admin');

        if ($guard->check() || Auth::guard('superadmin')->check() || Auth::guard('accounts')->check()) {
            session()->put($guard->getName(), $user->getAuthIdentifier());
            $guard->setUser($user);
            event(new \Illuminate\Auth\Events\Login('admin', $user, false));
        } else {
            $guard->login($user);
        }
        // A login made in the installed app: from now on it opens for this account.
        PanelApp::rememberAppLogin('admin', $user);

        $landingRoute = 'admin.quick-links';
        if ($user->role === 'sub-admin') {
            $permissions  = (array) $user->permissions;
            $landingRoute = $permissions[0] ?? 'admin.profile';
        }

        $url = route($landingRoute, ['organization' => $user->organization_id]);

        return redirect($url)
            ->with('success', 'Login successful.')
            ->with('school-login', ['org' => (string) $user->organization_id, 'url' => $url]);
    }

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

        $user = User::where('email', $this->email)->whereIn('role', ['admin', 'sub-admin'])->first();

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

        // 2FA temporarily disabled (ZeptoMail out of credit) — sign in directly.
        // The OTP step below is left intact; flip services.otp.login_enabled back
        // on to restore it.
        if (!OtpMailService::loginOtpEnabled()) {
            return $this->signIn($user);
        }

        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'School Admin', $this->otpChallenge);
        } catch (\Exception $e) {
            // A lockout is a deliberate refusal, not a delivery failure — say so
            // rather than hiding it behind the generic message.
            if ($lockedUntil = OtpMailService::lockedUntil($user)) {
                $this->otpLockedUntil = $lockedUntil;
                $this->addError('email', $e->getMessage());
                return;
            }
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

        $user = User::where('email', $this->email)->whereIn('role', ['admin', 'sub-admin'])->first();

        if (!$user) {
            $this->addError('otp', 'Session expired. Please login again.');
            $this->step = 'credentials';
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

        // Panel-specific guard: signing in here never touches the super-admin
        // or accounts sessions in the same browser.
        return $this->signIn($user);
    }

    public function resendOtp(): void
    {
        if (!$this->canResend) {
            return;
        }

        $user = User::where('email', $this->email)->whereIn('role', ['admin', 'sub-admin'])->first();

        if (!$user) {
            $this->step = 'credentials';
            return;
        }

        try {
            $this->otpChallenge = OtpMailService::sendOtp($user, 'School Admin', $this->otpChallenge);
            $this->otp       = ['', '', '', '', '', ''];
            $this->countdown = 120;
            $this->canResend = false;
            $this->resetValidation('otp');
        } catch (\Exception $e) {
            $this->otpLockedUntil = OtpMailService::lockedUntil($user);
            $this->addError('otp', $e->getMessage());
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
