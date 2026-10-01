<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * The installed panel apps — the desktop / home-screen shortcut of the School
 * Admin, the Accounts and the Super Admin panel — and whose each one is.
 *
 * The first time an app is opened on a device it asks for the email and
 * password, on the panel's own login screen. That login — the one made inside
 * the app, not one made in a browser tab — leaves a note on the device (an
 * encrypted cookie) of the account that did. From then on the app opens for
 * that account on a mailed code alone, every time it is opened afresh
 * ({@see \App\Livewire\AppLock}). Logging out takes the note away, and a
 * changed password stops it working — either way the next opening asks for
 * the email and password again.
 *
 * The note only ever names who to mail the code to: it never signs anyone in.
 */
class PanelApp
{
    /** The panels that install as an app; each is also its auth guard's name. */
    public const PANELS = ['admin', 'accounts', 'superadmin'];

    /**
     * Not the first name it had: under that one every login left a note, a
     * browser tab's too, and an app never yet opened would skip its login.
     */
    public const COOKIE = 'superlms_app_device';

    /** Session mark: the login about to be made is the app's own. */
    private const LOGIN_MARK = 'panel_app_login.';

    /** Browsers keep a cookie 400 days at most; every opening renews it. */
    private const MINUTES = 60 * 24 * 400;

    /** Schools one device remembers for the admin app (the latest are kept). */
    private const MAX_SCHOOLS = 20;

    /** The app is sending the device to the panel's login screen. */
    public static function expectLogin(string $panel): void
    {
        session()->put(self::LOGIN_MARK . $panel, true);
    }

    /**
     * A login with email and password was just made: note the account, if the
     * app sent the device to the login screen. A login made in a browser tab
     * leaves no note — the app still asks for its own login the first time.
     */
    public static function rememberAppLogin(string $panel, User $user): void
    {
        if (session()->pull(self::LOGIN_MARK . $panel)) {
            self::remember($panel, $user);
        }
    }

    /** Note the account the panel's app opens for on this device. */
    public static function remember(string $panel, User $user): void
    {
        if (!in_array($panel, self::PANELS, true)) {
            return;
        }

        $notes = self::notes();
        $notes[$panel][self::slot($panel, $user->organization_id)] = [
            'u' => (int) $user->getKey(),
            'p' => self::passwordMark($user),
            't' => now()->timestamp,
        ];

        if (count($notes[$panel]) > self::MAX_SCHOOLS) {
            uasort($notes[$panel], fn ($a, $b) => ($b['t'] ?? 0) <=> ($a['t'] ?? 0));
            $notes[$panel] = array_slice($notes[$panel], 0, self::MAX_SCHOOLS, true);
        }

        self::write($notes);
    }

    /** Logged out: the panel's app asks for the email and password again. */
    public static function forget(string $panel, $organization = null): void
    {
        $notes = self::notes();
        $slot  = self::slot($panel, $organization);

        if (!isset($notes[$panel][$slot])) {
            return;
        }

        unset($notes[$panel][$slot]);
        if (!$notes[$panel]) {
            unset($notes[$panel]);
        }

        self::write($notes);
    }

    /**
     * The account a panel's app opens for on this device — for the admin app,
     * the school's (with no school given, the school signed in to last). Null
     * when nobody has signed in here, or the account can no longer use the
     * panel, or its password has changed since.
     */
    public static function account(string $panel, $organization = null): ?User
    {
        $notes = self::notes()[$panel] ?? [];
        if (!is_array($notes) || !$notes) {
            return null;
        }

        if ($panel === 'admin' && !$organization) {
            foreach (self::schools() as $school) {
                return self::account('admin', $school);
            }
            return null;
        }

        $note = $notes[self::slot($panel, $organization)] ?? null;
        $user = is_array($note) ? User::find((int) ($note['u'] ?? 0)) : null;

        if (!$user
            || !self::allowed($panel, $user, $organization)
            || !hash_equals(self::passwordMark($user), (string) ($note['p'] ?? ''))) {
            return null;
        }

        return $user;
    }

    /** Schools the admin app has an account for on this device, the latest first. */
    public static function schools(): array
    {
        $notes = self::notes()['admin'] ?? [];
        if (!is_array($notes)) {
            return [];
        }

        uasort($notes, fn ($a, $b) => ($b['t'] ?? 0) <=> ($a['t'] ?? 0));

        return array_values(array_filter(
            array_map('intval', array_keys($notes)),
            fn (int $school) => $school > 0 && self::account('admin', $school),
        ));
    }

    /**
     * The super-admin of this device who may open a school — signed in to the
     * Super Admin panel here now, or the one its app opens for. Schools → Login
     * lets the same people into a school, by the same rule.
     */
    public static function superAdminFor(int $organization): ?User
    {
        $user = Auth::guard('superadmin')->user();
        if (!$user || !self::allowed('superadmin', $user)) {
            $user = self::account('superadmin');
        }
        // The super-admin signed in in the browser, seen from the app's window.
        if (!$user && AppWindow::isApp() && ($other = AppWindow::signedInElsewhere('superadmin'))
            && self::allowed('superadmin', $other)) {
            $user = $other;
        }
        if (!$user) {
            return null;
        }

        if ($user->role === 'sub-super-admin') {
            $only = $user->allowedOrganizationId();
            if (!$user->canAccessSuperAdminRoute('super-admin.schools') || ($only && $only !== $organization)) {
                return null;
            }
        }

        return $user;
    }

    /** Signed in to the panel right now (the admin app: to that school). */
    public static function signedIn(string $panel, $organization = null): ?User
    {
        $user = Auth::guard($panel)->user();
        if (!$user || !self::allowed($panel, $user, $organization)) {
            return null;
        }

        // The accounts desk is only in once its login code was entered.
        if ($panel === 'accounts' && !session('accounts_otp_verified')) {
            return null;
        }

        return $user;
    }

    /**
     * End the panel's sign-in, and nothing else: the other panels signed in on
     * this browser stay, and so does the note of whose app this is.
     */
    public static function signOut(string $panel): void
    {
        $guard = Auth::guard($panel);

        session()->forget($guard->getName());
        if ($panel === 'accounts') {
            session()->forget(['accounts_otp_verified', 'accounts_otp_challenge']);
        }
        $guard->forgetUser();
    }

    /** May this account use the panel (the admin panel: of that school)? */
    public static function allowed(string $panel, User $user, $organization = null): bool
    {
        return match ($panel) {
            'admin' => in_array($user->role, ['admin', 'sub-admin'], true)
                && $user->organization_id
                && (!$organization || (int) $user->organization_id === (int) $organization)
                && ($user->role !== 'sub-admin' || $user->is_active),
            'accounts' => $user->role === 'accounts' && $user->organization_id,
            'superadmin' => $user->role === 'super-admin'
                || ($user->role === 'sub-super-admin' && $user->is_active),
            default => false,
        };
    }

    /**
     * What an open page of a panel holds on to in the browser for as long as
     * it is open ({@see resources/views/partials/panel-app-open.blade.php}):
     * 'app' in a window of the installed app, 'tab' in a browser tab. The app
     * is "still open" only while a window of its own is — a browser tab on the
     * panel is not the app.
     */
    public static function openName(string $kind, string $panel, $organization = null): string
    {
        return 'superlms-' . $kind . '-' . $panel . ($panel === 'admin' ? '-' . (int) $organization : '');
    }

    /**
     * The panel a signed-in user is on, and (the admin panel) its school:
     * [panel, organization]. Null for someone who is on no panel.
     */
    public static function panelOf(?User $user): ?array
    {
        return match (true) {
            !$user => null,
            in_array($user->role, ['admin', 'sub-admin'], true) => ['admin', $user->organization_id],
            $user->role === 'accounts' => ['accounts', null],
            in_array($user->role, ['super-admin', 'sub-super-admin'], true) => ['superadmin', null],
            default => null,
        };
    }

    /** The app's opening page for a panel (the admin panel: for that school). */
    public static function gate(string $panel, $organization = null): string
    {
        return match ($panel) {
            'accounts'   => route('accounts.launch', [], false),
            'superadmin' => route('pwa.superadmin', [], false),
            default      => $organization
                ? route('admin.launch', ['organization' => (int) $organization], false)
                : route('pwa.admin', [], false),
        };
    }

    /**
     * Every name the app's opening page looks for. 'legacy' is what pages
     * loaded before the two were told apart still hold, app window and browser
     * tab alike — read as a browser tab, the side that asks for the code.
     */
    public static function openNames(string $panel, $organization = null): array
    {
        return [
            'app'    => self::openName('app', $panel, $organization),
            'tab'    => self::openName('tab', $panel, $organization),
            'legacy' => self::openName('open', $panel, $organization),
        ];
    }

    /**
     * Where a panel's login screen, shown inside the installed app, hands over
     * to: the app's opening page ('url'). For the admin app that is a school's
     * — the school the window had open, which the window itself remembers
     * under 'prefix' + the school's id ('pattern' is that school's address,
     * with __ORG__ for the id); else the school this device signed in to last;
     * else the app's general opening page.
     */
    public static function handover(string $panel): array
    {
        if ($panel !== 'admin') {
            return ['prefix' => null, 'pattern' => null, 'url' => self::gate($panel)];
        }

        return [
            'prefix'  => substr(self::openName('app', 'admin', 0), 0, -1),
            'pattern' => str_replace('987654321', '__ORG__', self::gate('admin', 987654321)),
            'url'     => self::gate('admin', self::schools()[0] ?? null),
        ];
    }

    /** Changes with the password, so a note written before a change stops working. */
    private static function passwordMark(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->getAuthPassword(), (string) config('app.key')), 0, 24);
    }

    private static function slot(string $panel, $organization): string
    {
        return $panel === 'admin' ? (string) (int) $organization : '0';
    }

    /** The notes as they stand — with what this request has already changed. */
    private static function notes(): array
    {
        $raw = Cookie::hasQueued(self::COOKIE)
            ? Cookie::queued(self::COOKIE)->getValue()
            : request()->cookie(self::COOKIE);

        $notes = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($notes) ? $notes : [];
    }

    private static function write(array $notes): void
    {
        Cookie::queue(
            $notes
                ? Cookie::make(self::COOKIE, json_encode($notes), self::MINUTES)
                : Cookie::forget(self::COOKIE)
        );
    }
}
