<?php

namespace App\Livewire;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use App\Support\PanelApp;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * What an installed panel app (School Admin, Accounts, Super Admin) opens on —
 * the start_url of its desktop / home-screen shortcut.
 *
 * The first time the app is opened on a device it gets the panel's login
 * screen: email, password and the login's code. After that, opening the app
 * asks for a code mailed to the account that login was made with
 * ({@see PanelApp}), and nothing else: no email, no password. While a window
 * of the app is open, opening it again — as often as wanted — goes straight
 * in. Once every window of the app is closed, the next opening asks for a code
 * again.
 *
 * Being signed in to the panel in a browser tab does not open the app: it
 * still asks for its login the first time, and for its code every time after.
 *
 * Whether the app is still open only the browser can tell, so the page asks
 * it first (the "checking" step) and reports back through opened():
 *   'running' — a window of the app has the panel open;
 *   'tab'     — no window of the app, but a browser tab has;
 *   'closed'  — the panel is open nowhere.
 * An app found closed has its sign-in ended on the spot, so until the code is
 * entered the panel is shut from every window, not just this one — except
 * when a browser tab is using that sign-in, which is then left alone: the tab
 * goes on working, and this window still waits for its code.
 *
 * A school's app on a super-admin's own device — installed after Schools →
 * Login, with no school account signed in — opens on the super-admin's code.
 */
class AppLock extends Component
{
    private const PANEL_NAMES = [
        'admin'      => 'School Admin',
        'accounts'   => 'Accounts Panel',
        'superadmin' => 'Super Admin',
    ];

    private const RESEND_COOLDOWN = 120;

    #[Locked]
    public string $panel = 'admin';

    /** The admin app's school. */
    #[Locked]
    public ?int $school = null;

    /** Who the code is mailed to. */
    #[Locked]
    public ?int $accountId = null;

    /** A school opened on a super-admin's code. */
    #[Locked]
    public bool $viaSuperAdmin = false;

    #[Locked]
    public string $step = 'checking'; // 'checking' | 'otp'

    /** The code was entered here: this window may go in. */
    #[Locked]
    public bool $verified = false;

    public string $otpSentTo = '';
    public array  $otp       = ['', '', '', '', '', ''];

    /** Unix timestamp (server clock) before which Resend OTP stays disabled. */
    public int $resendAvailableAt = 0;

    /** This opening's OTP request — only its own code opens the app here. */
    #[Locked]
    public ?string $otpChallenge = null;

    /** Unix timestamp the OTP lockout lifts at (0 = not locked out). */
    public int $otpLockedUntil = 0;

    /** Nothing is mailed or changed here: a page reload must cost nothing. */
    public function mount($panel = null, $organization = null)
    {
        $this->panel = in_array($panel, PanelApp::PANELS, true) ? $panel : 'admin';

        if ($this->panel === 'admin') {
            // The app installed for one school opens that school; the one
            // installed from the login screen, the school signed in here now,
            // else the one this device signed in to last.
            $this->school = ctype_digit((string) $organization) && (int) $organization > 0
                ? (int) $organization
                : (PanelApp::signedIn('admin')?->organization_id ?? PanelApp::schools()[0] ?? null);
        }

        [$account, $viaSuperAdmin] = $this->deviceAccount();
        $this->accountId     = $account?->getKey();
        $this->viaSuperAdmin = $viaSuperAdmin;

        // Not signed in, and the app has no account on this device: its login.
        if (!$account && !$this->signedIn()) {
            return $this->toLogin();
        }
    }

    /**
     * The page's answer to "is the app open?" — asked of the browser once, as
     * the page loads: 'running', 'tab' or 'closed' (see the class comment).
     */
    public function opened(string $state = 'closed')
    {
        if ($this->step !== 'checking') {
            return;
        }

        $user = $this->signedIn();

        // A window of the app is open and signed in: straight in, as often as wanted.
        if ($state === 'running' && $user) {
            return $this->enter($user);
        }

        $account = $this->account();

        // Closed and opened again: the sign-in ended with the app — unless a
        // browser tab is using it. The app's first opening on this device ends
        // it either way: the login screen only shows to someone signed out.
        if ($user && ($state !== 'tab' || !$account)) {
            PanelApp::signOut($this->panel);
        }

        if (!$account) {
            return $this->toLogin();
        }

        // The emergency switch that skips the login OTP skips this one too.
        if (!OtpMailService::loginOtpEnabled()) {
            return $this->signIn($account);
        }

        $this->step = 'otp';
        $this->sendCode($account);
    }

    public function verifyOtp()
    {
        // A doubled request: the code was already entered here.
        if ($this->verified && ($user = $this->signedIn())) {
            return $this->enter($user);
        }

        if ($this->step !== 'otp') {
            return;
        }

        $entered = implode('', array_map(fn ($d) => is_scalar($d) ? (string) $d : '', $this->otp));

        if (strlen($entered) !== 6 || !ctype_digit($entered)) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            return;
        }

        $account = $this->account();
        if (!$account) {
            return $this->toLogin();
        }

        try {
            OtpMailService::verifyOtp($account, $entered, $this->otpChallenge);
        } catch (\Exception $e) {
            $this->otp = ['', '', '', '', '', ''];
            $this->otpLockedUntil = OtpMailService::lockedUntil($account);
            $this->addError('otp', $e->getMessage());
            return;
        }

        return $this->signIn($account);
    }

    public function resendOtp()
    {
        if ($this->step !== 'otp' || now()->getTimestamp() < $this->resendAvailableAt) {
            return;
        }

        $account = $this->account();
        if (!$account) {
            return $this->toLogin();
        }

        $this->sendCode($account);
    }

    /**
     * Back at the window: another window of the app may have entered the code
     * meanwhile — the page looked, and found the app open.
     */
    public function recheck(string $state = 'closed')
    {
        if ($state === 'running' && ($user = $this->signedIn())) {
            return $this->enter($user);
        }
    }

    /** "Sign in with email and password instead" — the panel's own login. */
    public function usePassword()
    {
        // Its login screen sends anyone signed in on the guard back inside.
        PanelApp::signOut($this->panel);

        return $this->toLogin();
    }

    /**
     * To the panel's login screen, for the app's own login: the account that
     * signs in there is the one the app opens for from then on. ?password=1
     * keeps the login screen from handing the window straight back here.
     */
    private function toLogin()
    {
        PanelApp::expectLogin($this->panel);

        return redirect()->route($this->loginRoute(), ['password' => 1]);
    }

    private function sendCode(User $account): void
    {
        // The code goes to the address as it should read — an old save can
        // still carry invisible characters the mail provider refuses. Not saved.
        $to = clone $account;
        $to->email = User::cleanEmail($account->email);

        $this->otp       = ['', '', '', '', '', ''];
        $this->otpSentTo = $this->masked($to->email);
        $this->resetValidation('otp');

        try {
            $this->otpChallenge = OtpMailService::sendOtp(
                $to,
                self::PANEL_NAMES[$this->viaSuperAdmin ? 'superadmin' : $this->panel],
                $this->otpChallenge,
            );
            $this->resendAvailableAt = now()->getTimestamp() + self::RESEND_COOLDOWN;
        } catch (\Throwable $e) {
            // Nothing was mailed: Resend OTP is there to try again at once.
            $this->resendAvailableAt = 0;

            if ($lockedUntil = OtpMailService::lockedUntil($account)) {
                $this->otpLockedUntil = $lockedUntil;
                $this->addError('otp', $e->getMessage());
                return;
            }

            if ($e instanceof OtpDeliveryException) {
                $this->otpChallenge = $e->challenge;
            }
            logger()->error('OTP send failed opening the ' . $this->panel . ' app: ' . $e->getMessage());
            $this->addError('otp', "Couldn't send the OTP email right now. Please try again in a moment.");
        }
    }

    /**
     * Sign the panel's guard in. The session keeps its id, as Schools → Login
     * does: a new id would be undone by any request another panel's tab had
     * in flight, which comes back with the old cookie.
     */
    private function signIn(User $account)
    {
        $user = $account;

        if ($this->viaSuperAdmin) {
            $user = User::where('organization_id', $this->school)->where('role', 'admin')->first();
            if (!$user) {
                $this->addError('otp', 'No admin account found for this school.');
                return;
            }
        }

        $guard = Auth::guard($this->panel);
        session()->put($guard->getName(), $user->getAuthIdentifier());
        $guard->setUser($user);
        event(new Login($this->panel, $user, false));

        if ($this->panel === 'accounts') {
            session()->forget('accounts_otp_challenge');
            session(['accounts_otp_verified' => true]);
        }

        // Renewed on every opening, so it only lapses on an app left unused.
        if (!$this->viaSuperAdmin) {
            PanelApp::remember($this->panel, $user);
        }

        $this->verified = true;

        return $this->enter($user);
    }

    private function enter(User $user)
    {
        return redirect()->to(match ($this->panel) {
            'superadmin' => route('super-admin.dashboard'),
            'accounts'   => route('accounts.dashboard', ['organization' => $user->organization_id]),
            default      => route('admin.home', ['organization' => $user->organization_id]),
        });
    }

    private function signedIn(): ?User
    {
        return PanelApp::signedIn($this->panel, $this->school);
    }

    /** Who the code goes to on this device, and whether that is a super-admin's way in. */
    private function deviceAccount(): array
    {
        if ($account = PanelApp::account($this->panel, $this->school)) {
            return [$account, false];
        }

        if ($this->panel === 'admin' && $this->school && ($super = PanelApp::superAdminFor($this->school))) {
            return [$super, true];
        }

        return [null, false];
    }

    /** The account the page was opened for, if the device still holds it. */
    private function account(): ?User
    {
        [$account, $viaSuperAdmin] = $this->deviceAccount();

        return $account
            && $this->accountId !== null
            && (int) $account->getKey() === $this->accountId
            && $viaSuperAdmin === $this->viaSuperAdmin
                ? $account
                : null;
    }

    private function loginRoute(): string
    {
        return match ($this->panel) {
            'superadmin' => 'super-admin.login',
            'accounts'   => 'accounts.login',
            default      => 'admin.login',
        };
    }

    /** "su••••@gmail.com" — enough to know which inbox, before anything is proved. */
    private function masked(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $shown = mb_substr($name, 0, mb_strlen($name) > 2 ? 2 : 1);

        return $shown . str_repeat('•', max(3, mb_strlen($name) - mb_strlen($shown)))
            . ($domain !== '' ? '@' . $domain : '');
    }

    public function render()
    {
        return view('livewire.app-lock', [
            'openNames' => PanelApp::openNames($this->panel, $this->school),
        ])->layout('components.layouts.fullscreen');
    }
}
