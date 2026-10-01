<?php

namespace Tests\Feature;

use App\Livewire\Accounts\VerifyOtp;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\AppLock;
use App\Livewire\Components\NavBar;
use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Services\OtpMailService;
use App\Support\PanelApp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * An installed panel app asks for the login the first time it is opened on a
 * device, and from then on opens on a mailed code alone for the account that
 * login was made with — every time it is opened afresh, and not while a
 * window of the app is still open. Being signed in to the panel in a browser
 * tab does not open the app.
 *
 * The app's opening page asks the browser where the panel is open and reports
 * 'running' (a window of the app), 'tab' (only a browser tab) or 'closed'.
 *
 * The cookie jar keeps what a request queued, so within a test it stands for
 * the device: what one step left on it, the next step finds.
 */
class PanelAppLockTest extends TestCase
{
    private const PASSWORD = 'Secret@123';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->text('permissions')->nullable();
            $t->string('remember_token')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert([['id' => 9, 'name' => 'Test School'], ['id' => 10, 'name' => 'Other School']]);

        // Codes go out through the app mailer here (no ZeptoMail), and are caught.
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('k', 32)),
            'services.zeptomail.otp_template_key' => null,
            'services.otp.login_enabled' => true,
            'mail.default' => 'smtp',
        ]);
        Http::fake(['*' => Http::response([], 500)]);
        Mail::fake();
    }

    private function user(string $role, array $extra = []): User
    {
        return User::forceCreate(array_merge([
            'name' => ucfirst($role),
            'email' => $role . '@example.com',
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'organization_id' => in_array($role, ['super-admin', 'sub-super-admin'], true) ? null : 9,
            'is_active' => true,
        ], $extra));
    }

    /** The code in the last mail sent. */
    private function mailedCode(): string
    {
        return Mail::sent(LoginOtpMail::class)->last()->otp;
    }

    private function mails(): int
    {
        return Mail::sent(LoginOtpMail::class)->count();
    }

    private function wrong(string $code): array
    {
        return str_split($code === '000000' ? '111111' : '000000');
    }

    private function app(string $panel, $organization = null)
    {
        return Livewire::test(AppLock::class, ['panel' => $panel, 'organization' => $organization]);
    }

    /** The school admin's login screen: email, password, then its code. */
    private function adminLogin(): void
    {
        Livewire::test(AdminLogin::class)
            ->set('email', 'admin@example.com')
            ->set('password', self::PASSWORD)
            ->call('login')
            ->assertSet('step', 'otp')
            // The last digit typed verifies by itself.
            ->set('otp', str_split($this->mailedCode()))
            ->assertRedirect(route('admin.quick-links', ['organization' => 9]));
    }

    public function test_an_app_never_opened_on_the_device_gets_the_login_screen(): void
    {
        $this->user('admin');
        $this->user('accounts');
        $this->user('super-admin');

        $this->app('admin', 9)->assertRedirect(route('admin.login', ['password' => 1]));
        $this->app('admin')->assertRedirect(route('admin.login', ['password' => 1]));
        $this->app('accounts')->assertRedirect(route('accounts.login', ['password' => 1]));
        $this->app('superadmin')->assertRedirect(route('super-admin.login', ['password' => 1]));

        Mail::assertNothingSent();
    }

    public function test_the_first_opening_asks_for_the_login_and_every_later_one_for_a_code(): void
    {
        $admin = $this->user('admin');

        // First opening: the login screen.
        $this->app('admin', 9)->assertRedirect(route('admin.login', ['password' => 1]));
        $this->adminLogin();

        $this->assertTrue(PanelApp::account('admin', 9)->is($admin));
        $this->assertSame([9], PanelApp::schools());
        $sent = $this->mails();

        // The app, closed and opened again: the sign-in ends, a code is mailed.
        $app = $this->app('admin', 9)
            ->assertSet('step', 'checking')
            ->call('opened', 'closed')
            ->assertSet('step', 'otp')
            ->assertSet('otpSentTo', 'ad•••@example.com')
            ->assertSee('Verify OTP');

        $this->assertFalse(Auth::guard('admin')->check());
        $this->assertSame($sent + 1, $this->mails());
        $this->assertTrue(Mail::sent(LoginOtpMail::class)->last()->hasTo('admin@example.com'));
        $code = $this->mailedCode();

        $app->set('otp', $this->wrong($code))
            ->call('verifyOtp')
            ->assertHasErrors('otp')
            ->assertNoRedirect();
        $this->assertFalse(Auth::guard('admin')->check());

        $app->set('otp', str_split($code))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
        $this->assertTrue(Auth::guard('admin')->user()->is($admin));

        // And again, the next time it is closed and opened.
        $this->app('admin', 9)->call('opened', 'closed')->assertSet('step', 'otp');
        $this->assertSame($sent + 2, $this->mails());
    }

    public function test_a_login_in_a_browser_tab_does_not_open_the_app(): void
    {
        $admin = $this->user('admin');

        // Signed in through the browser: the device holds no note for the app.
        $this->adminLogin();
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertNull(PanelApp::account('admin', 9));
        $sent = $this->mails();

        // The app's first opening — with the browser tab still open, or closed
        // — is the login screen, and the sign-in there is ended to show it.
        foreach (['tab', 'closed'] as $state) {
            Auth::guard('admin')->login($admin);

            $this->app('admin', 9)->call('opened', $state)
                ->assertRedirect(route('admin.login', ['password' => 1]));
            $this->assertFalse(Auth::guard('admin')->check());
        }
        $this->assertSame($sent, $this->mails());

        // That login is the app's: from now on it opens on a code.
        $this->adminLogin();
        $this->assertTrue(PanelApp::account('admin', 9)->is($admin));

        // A later login in the browser changes nothing for the app.
        $this->adminLogin();
        $this->app('admin', 9)->call('opened', 'closed')->assertSet('step', 'otp');
    }

    public function test_signed_in_in_a_browser_tab_the_app_still_asks_for_its_code(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);
        Auth::guard('admin')->login($admin);

        // A browser tab has the panel open: the app asks for the code, and
        // the tab keeps its sign-in.
        $app = $this->app('admin', 9)->call('opened', 'tab')->assertSet('step', 'otp');
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame(1, $this->mails());
        $code = $this->mailedCode();

        // Being signed in is not the code.
        $app->call('recheck', 'tab')->assertNoRedirect();
        $app->call('recheck', 'closed')->assertNoRedirect();
        $app->set('otp', $this->wrong($code))
            ->call('verifyOtp')
            ->assertHasErrors('otp')
            ->assertNoRedirect();

        $app->set('otp', str_split($code))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));

        // A doubled request after the code went through just goes in.
        $app->call('verifyOtp')->assertRedirect(route('admin.home', ['organization' => 9]));
    }

    public function test_while_a_window_of_the_app_is_open_it_opens_again_without_a_code(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);
        Auth::guard('admin')->login($admin);

        foreach ([1, 2, 3] as $again) {
            $this->app('admin', 9)
                ->call('opened', 'running')
                ->assertRedirect(route('admin.home', ['organization' => 9]));
        }

        $this->assertTrue(Auth::guard('admin')->check());
        Mail::assertNothingSent();
    }

    public function test_another_window_of_the_app_entering_the_code_lets_this_one_in(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $waiting = $this->app('admin', 9)->call('opened', 'closed')->assertSet('step', 'otp');
        $waiting->call('recheck', 'running')->assertNoRedirect();

        $this->app('admin', 9)->call('opened', 'closed')
            ->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));

        $waiting->call('recheck', 'running')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
    }

    public function test_an_open_window_whose_sign_in_ran_out_still_asks_for_the_code(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $app = $this->app('admin', 9)->call('opened', 'running')->assertSet('step', 'otp');

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
    }

    public function test_the_school_app_stays_on_its_own_school(): void
    {
        $admin = $this->user('admin');
        $other = $this->user('admin', ['email' => 'other@example.com', 'organization_id' => 10]);
        PanelApp::remember('admin', $admin);
        PanelApp::remember('admin', $other);

        // The browser is on the other school: that is not this app being open,
        // and that sign-in is not this app's to end.
        Auth::guard('admin')->login($other);

        $app = $this->app('admin', 9)->call('opened', 'closed')->assertSet('step', 'otp');
        $this->assertTrue(Auth::guard('admin')->user()->is($other));
        $this->assertTrue(Mail::sent(LoginOtpMail::class)->last()->hasTo('admin@example.com'));

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
        $this->assertTrue(Auth::guard('admin')->user()->is($admin));

        // Installed from the login screen (no school in its address): the
        // school signed in now.
        $this->app('admin')->assertSet('school', 9);
    }

    public function test_logging_out_or_a_new_password_brings_the_login_back(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);
        Auth::guard('admin')->login($admin);
        Auth::shouldUse('admin');

        Livewire::test(NavBar::class)->call('adminLogout')->assertRedirect(route('admin.login'));
        $this->assertNull(PanelApp::account('admin', 9));
        $this->app('admin', 9)->assertRedirect(route('admin.login', ['password' => 1]));

        PanelApp::remember('admin', $admin);
        $this->assertNotNull(PanelApp::account('admin', 9));
        $admin->forceFill(['password' => Hash::make('Another@123')])->save();
        $this->assertNull(PanelApp::account('admin', 9));

        // An account that may no longer use the panel.
        $sub = $this->user('sub-admin', ['permissions' => ['admin.student']]);
        PanelApp::remember('admin', $sub);
        $this->assertTrue(PanelApp::account('admin', 9)->is($sub));
        $sub->forceFill(['is_active' => false])->save();
        $this->assertNull(PanelApp::account('admin', 9));
    }

    public function test_sign_in_with_password_instead_leads_to_the_login(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);
        Auth::guard('admin')->login($admin);

        $this->app('admin', 9)
            ->call('opened', 'tab')
            ->call('usePassword')
            ->assertRedirect(route('admin.login', ['password' => 1]));

        // The login screen only shows to someone signed out.
        $this->assertFalse(Auth::guard('admin')->check());
        // The app's account stays: the next opening asks for a code again.
        $this->assertNotNull(PanelApp::account('admin', 9));
    }

    public function test_a_super_admin_opens_a_schools_app_on_their_own_code(): void
    {
        $admin = $this->user('admin');
        $super = $this->user('super-admin');
        Auth::guard('superadmin')->login($super);

        $app = $this->app('admin', 9)->call('opened', 'closed')->assertSet('step', 'otp');
        $this->assertTrue(Mail::sent(LoginOtpMail::class)->last()->hasTo('super-admin@example.com'));

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));

        $this->assertTrue(Auth::guard('admin')->user()->is($admin));
        $this->assertTrue(Auth::guard('superadmin')->user()->is($super));
        // The school's own account never signed in here.
        $this->assertNull(PanelApp::account('admin', 9));

        // In the school through Schools → Login, in a browser tab: the app
        // asks for the super-admin's code, and the tab stays in the school.
        $this->app('admin', 9)->call('opened', 'tab')->assertSet('step', 'otp');
        $this->assertTrue(Auth::guard('admin')->user()->is($admin));
    }

    public function test_a_sub_super_admin_only_opens_the_school_they_may(): void
    {
        $this->user('admin');
        $sub = $this->user('sub-super-admin', [
            'permissions' => ['super-admin.schools'],
            'allowed_organization_id' => 10,
        ]);
        Auth::guard('superadmin')->login($sub);

        $this->app('admin', 9)->assertRedirect(route('admin.login', ['password' => 1]));

        $sub->forceFill(['allowed_organization_id' => 9])->save();
        $this->app('admin', 9)->assertSet('viaSuperAdmin', true);

        $sub->forceFill(['permissions' => ['super-admin.students']])->save();
        $this->app('admin', 9)->assertRedirect(route('admin.login', ['password' => 1]));

        Mail::assertNothingSent();
    }

    public function test_the_accounts_app_opens_on_a_code(): void
    {
        $accounts = $this->user('accounts');

        // First opening: the accounts login — the password, then its code.
        $this->app('accounts')->assertRedirect(route('accounts.login', ['password' => 1]));
        Auth::guard('accounts')->login($accounts);
        Auth::shouldUse('accounts');
        session(['accounts_otp_challenge' => OtpMailService::sendOtp($accounts, 'Accounts Panel')]);
        Livewire::test(VerifyOtp::class)
            ->set('otp', str_split($this->mailedCode()))
            ->assertRedirect(route('accounts.dashboard', ['organization' => 9]));
        $this->assertTrue(PanelApp::account('accounts')->is($accounts));

        // A window of the app still open → in.
        $this->app('accounts')->call('opened', 'running')
            ->assertRedirect(route('accounts.dashboard', ['organization' => 9]));

        // Closed and opened again → a code.
        $app = $this->app('accounts')->call('opened', 'closed')->assertSet('step', 'otp');
        $this->assertFalse(Auth::guard('accounts')->check());
        $this->assertNull(session('accounts_otp_verified'));

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('accounts.dashboard', ['organization' => 9]));
        $this->assertTrue(Auth::guard('accounts')->user()->is($accounts));
        $this->assertTrue(session('accounts_otp_verified'));
    }

    public function test_the_super_admin_app_opens_on_a_code(): void
    {
        $super = $this->user('super-admin');
        PanelApp::remember('superadmin', $super);

        $app = $this->app('superadmin')->call('opened', 'closed')->assertSet('step', 'otp');

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('super-admin.dashboard'));
        $this->assertTrue(Auth::guard('superadmin')->user()->is($super));
    }

    public function test_resend_waits_for_its_cooldown(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $app = $this->app('admin', 9)->call('opened', 'closed');
        $this->assertSame(1, $this->mails());

        $app->call('resendOtp');
        $this->assertSame(1, $this->mails());

        $this->travel(121)->seconds();
        $app->call('resendOtp');
        $this->assertSame(2, $this->mails());

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
    }

    public function test_with_the_login_code_switched_off_the_app_opens_straight_in(): void
    {
        config(['services.otp.login_enabled' => false]);
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $this->app('admin', 9)->call('opened', 'closed')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
        $this->assertTrue(Auth::guard('admin')->user()->is($admin));
        Mail::assertNothingSent();
    }

    public function test_each_apps_address_opens_its_own_panel(): void
    {
        $routes = app('router')->getRoutes();
        $match  = fn (string $path) => $routes->match(\Illuminate\Http\Request::create($path));

        $this->assertSame('accounts.launch', $match('/accounts/launch')->getName());
        $this->assertSame('accounts', $match('/accounts/launch')->parameter('panel'));
        $this->assertSame('admin.launch', $match('/9/launch')->getName());
        $this->assertSame('admin', $match('/9/launch')->parameter('panel'));
        $this->assertSame('superadmin', $match('/app/superadmin')->parameter('panel'));
        $this->assertSame('accounts', $match('/app/accounts')->parameter('panel'));
        $this->assertSame('admin', $match('/app/admin')->parameter('panel'));
    }

    public function test_an_app_window_and_a_browser_tab_are_told_apart(): void
    {
        $admin = $this->user('admin');

        $this->assertSame(['admin', 9], PanelApp::panelOf($admin));
        $this->assertSame(
            ['app' => 'superlms-app-admin-9', 'tab' => 'superlms-tab-admin-9', 'legacy' => 'superlms-open-admin-9'],
            PanelApp::openNames('admin', 9),
        );
        $this->assertSame('superlms-app-accounts', PanelApp::openNames(...PanelApp::panelOf($this->user('accounts')))['app']);
        $this->assertSame('superlms-tab-superadmin', PanelApp::openNames(...PanelApp::panelOf($this->user('super-admin')))['tab']);
        $this->assertNull(PanelApp::panelOf($this->user('teacher')));
        $this->assertNull(PanelApp::panelOf(null));

        // Where a page that turns into an app window (the app was just
        // installed from it) goes: the app's opening page.
        $this->assertSame('/9/launch', PanelApp::gate('admin', 9));
        $this->assertSame('/app/admin', PanelApp::gate('admin'));
        $this->assertSame('/accounts/launch', PanelApp::gate('accounts'));
        $this->assertSame('/app/superadmin', PanelApp::gate('superadmin'));
    }

    public function test_the_login_screen_inside_the_app_hands_over_to_its_opening_page(): void
    {
        $admin = $this->user('admin');

        // No account on the device yet: the app's general opening page.
        $this->assertSame(
            ['prefix' => 'superlms-app-admin-', 'pattern' => '/__ORG__/launch', 'url' => '/app/admin'],
            PanelApp::handover('admin'),
        );
        $this->assertSame('/accounts/launch', PanelApp::handover('accounts')['url']);
        $this->assertSame('/app/superadmin', PanelApp::handover('superadmin')['url']);

        PanelApp::remember('admin', $admin);
        $this->assertSame('/9/launch', PanelApp::handover('admin')['url']);

        // Sent to the login by the opening page itself (?password=1), the
        // login screen stays put.
        $handover = fn () => view('partials.panel-app-login', ['panel' => 'admin'])->render();
        $this->assertStringContainsString('\/9\/launch', $handover());
        request()->merge(['password' => 1]);
        $this->assertStringNotContainsString('launch', $handover());
        request()->replace([]);

        Cookie::flushQueuedCookies();
        $this->assertSame('/app/admin', PanelApp::handover('admin')['url']);
    }
}
