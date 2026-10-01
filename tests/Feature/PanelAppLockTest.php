<?php

namespace Tests\Feature;

use App\Livewire\Accounts\VerifyOtp;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\AppLock;
use App\Livewire\Components\NavBar;
use App\Mail\LoginOtpMail;
use App\Models\User;
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
 * An installed panel app opens on a mailed code alone for the account that
 * signed in on the device — every time it is opened afresh, and not while the
 * panel is still open somewhere. A device nobody has signed in on gets the
 * login screen.
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

    private function wrong(string $code): array
    {
        return str_split($code === '000000' ? '111111' : '000000');
    }

    private function app(string $panel, $organization = null)
    {
        return Livewire::test(AppLock::class, ['panel' => $panel, 'organization' => $organization]);
    }

    public function test_a_device_nobody_signed_in_on_gets_the_login_screen(): void
    {
        $this->user('admin');
        $this->user('accounts');
        $this->user('super-admin');

        $this->app('admin', 9)->assertRedirect(route('admin.login'));
        $this->app('admin')->assertRedirect(route('admin.login'));
        $this->app('accounts')->assertRedirect(route('accounts.login'));
        $this->app('superadmin')->assertRedirect(route('super-admin.login'));

        Mail::assertNothingSent();
    }

    public function test_after_one_login_the_app_opens_on_a_code_alone(): void
    {
        $admin = $this->user('admin');

        // The first time: email, password and the login's own code.
        Livewire::test(AdminLogin::class)
            ->set('email', 'admin@example.com')
            ->set('password', self::PASSWORD)
            ->call('login')
            ->assertSet('step', 'otp');
        Livewire::test(AdminLogin::class)
            ->set('email', 'admin@example.com')
            ->set('password', self::PASSWORD)
            ->call('login')
            // The last digit typed verifies by itself.
            ->set('otp', str_split($this->mailedCode()))
            ->assertRedirect(route('admin.quick-links', ['organization' => 9]));

        $this->assertTrue(PanelApp::account('admin', 9)->is($admin));
        $this->assertSame([9], PanelApp::schools());
        $sent = Mail::sent(LoginOtpMail::class)->count();

        // The app, closed and opened again: the sign-in ends, a code is mailed.
        $app = $this->app('admin', 9)
            ->assertSet('step', 'checking')
            ->call('opened', false)
            ->assertSet('step', 'otp')
            ->assertSet('otpSentTo', 'ad•••@example.com')
            ->assertSee('Verify OTP');

        $this->assertFalse(Auth::guard('admin')->check());
        $this->assertSame($sent + 1, Mail::sent(LoginOtpMail::class)->count());
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
    }

    public function test_while_the_panel_is_open_it_opens_again_without_a_code(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);
        Auth::guard('admin')->login($admin);

        foreach ([1, 2, 3] as $again) {
            $this->app('admin', 9)
                ->call('opened', true)
                ->assertRedirect(route('admin.home', ['organization' => 9]));
        }

        $this->assertTrue(Auth::guard('admin')->check());
        Mail::assertNothingSent();
    }

    public function test_open_in_a_tab_whose_sign_in_ran_out_still_asks_for_the_code(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $app = $this->app('admin', 9)->call('opened', true)->assertSet('step', 'otp');

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
    }

    public function test_a_sign_in_from_before_goes_through_the_login_once(): void
    {
        // Signed in, but the device holds no note of it (a sign-in older than
        // this feature): still open → in; closed and opened again → the login.
        $admin = $this->user('admin');
        Auth::guard('admin')->login($admin);

        $this->app('admin', 9)->call('opened', true)
            ->assertRedirect(route('admin.home', ['organization' => 9]));

        $this->app('admin', 9)->call('opened', false)
            ->assertRedirect(route('admin.login'));
        $this->assertFalse(Auth::guard('admin')->check());
        Mail::assertNothingSent();
    }

    public function test_the_school_app_stays_on_its_own_school(): void
    {
        $admin = $this->user('admin');
        $other = $this->user('admin', ['email' => 'other@example.com', 'organization_id' => 10]);
        PanelApp::remember('admin', $admin);
        PanelApp::remember('admin', $other);

        // The browser is on the other school: that is not this app being open.
        Auth::guard('admin')->login($other);

        $app = $this->app('admin', 9)->call('opened', true)->assertSet('step', 'otp');
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
        $this->app('admin', 9)->assertRedirect(route('admin.login'));

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

        $this->app('admin', 9)
            ->call('opened', false)
            ->call('usePassword')
            ->assertRedirect(route('admin.login', ['password' => 1]));

        // The app's account stays: the next opening asks for a code again.
        $this->assertNotNull(PanelApp::account('admin', 9));
    }

    public function test_a_super_admin_opens_a_schools_app_on_their_own_code(): void
    {
        $admin = $this->user('admin');
        $super = $this->user('super-admin');
        Auth::guard('superadmin')->login($super);

        $app = $this->app('admin', 9)->call('opened', false)->assertSet('step', 'otp');
        $this->assertTrue(Mail::sent(LoginOtpMail::class)->last()->hasTo('super-admin@example.com'));

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));

        $this->assertTrue(Auth::guard('admin')->user()->is($admin));
        $this->assertTrue(Auth::guard('superadmin')->user()->is($super));
        // The school's own account never signed in here.
        $this->assertNull(PanelApp::account('admin', 9));
    }

    public function test_a_sub_super_admin_only_opens_the_school_they_may(): void
    {
        $this->user('admin');
        $sub = $this->user('sub-super-admin', [
            'permissions' => ['super-admin.schools'],
            'allowed_organization_id' => 10,
        ]);
        Auth::guard('superadmin')->login($sub);

        $this->app('admin', 9)->assertRedirect(route('admin.login'));

        $sub->forceFill(['allowed_organization_id' => 9])->save();
        $this->app('admin', 9)->assertSet('viaSuperAdmin', true);

        $sub->forceFill(['permissions' => ['super-admin.students']])->save();
        $this->app('admin', 9)->assertRedirect(route('admin.login'));

        Mail::assertNothingSent();
    }

    public function test_the_accounts_app_opens_on_a_code(): void
    {
        $accounts = $this->user('accounts');

        // The accounts login: the password, then its code.
        Auth::guard('accounts')->login($accounts);
        Auth::shouldUse('accounts');
        session(['accounts_otp_challenge' => \App\Services\OtpMailService::sendOtp($accounts, 'Accounts Panel')]);
        Livewire::test(VerifyOtp::class)
            ->set('otp', str_split($this->mailedCode()))
            ->assertRedirect(route('accounts.dashboard', ['organization' => 9]));
        $this->assertTrue(PanelApp::account('accounts')->is($accounts));

        // Still open → in.
        $this->app('accounts')->call('opened', true)
            ->assertRedirect(route('accounts.dashboard', ['organization' => 9]));

        // Closed and opened again → a code.
        $app = $this->app('accounts')->call('opened', false)->assertSet('step', 'otp');
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

        $app = $this->app('superadmin')->call('opened', false)->assertSet('step', 'otp');

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('super-admin.dashboard'));
        $this->assertTrue(Auth::guard('superadmin')->user()->is($super));
    }

    public function test_resend_waits_for_its_cooldown(): void
    {
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $app = $this->app('admin', 9)->call('opened', false);
        $this->assertSame(1, Mail::sent(LoginOtpMail::class)->count());

        $app->call('resendOtp');
        $this->assertSame(1, Mail::sent(LoginOtpMail::class)->count());

        $this->travel(121)->seconds();
        $app->call('resendOtp');
        $this->assertSame(2, Mail::sent(LoginOtpMail::class)->count());

        $app->set('otp', str_split($this->mailedCode()))
            ->call('verifyOtp')
            ->assertRedirect(route('admin.home', ['organization' => 9]));
    }

    public function test_with_the_login_code_switched_off_the_app_opens_straight_in(): void
    {
        config(['services.otp.login_enabled' => false]);
        $admin = $this->user('admin');
        PanelApp::remember('admin', $admin);

        $this->app('admin', 9)->call('opened', false)
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

    public function test_the_login_screen_knows_where_the_app_opens(): void
    {
        $admin = $this->user('admin');
        $super = $this->user('super-admin');

        $this->assertSame([], PanelApp::launches('admin'));
        $this->assertSame([], PanelApp::launches('superadmin'));

        PanelApp::remember('admin', $admin);
        PanelApp::remember('superadmin', $super);

        $this->assertSame(
            [['name' => 'superlms-open-admin-9', 'url' => '/9/launch']],
            PanelApp::launches('admin'),
        );
        $this->assertSame(
            [['name' => 'superlms-open-superadmin', 'url' => '/app/superadmin']],
            PanelApp::launches('superadmin'),
        );
        $this->assertSame('superlms-open-admin-9', PanelApp::openNameFor($admin));

        // The login screen carries the handover only when the app has an
        // account here, and never when the password was asked for.
        $handover = fn () => view('partials.panel-app-login', ['panel' => 'admin'])->render();
        $this->assertStringContainsString('\/9\/launch', $handover());
        request()->merge(['password' => 1]);
        $this->assertStringNotContainsString('launch', $handover());
        request()->replace([]);

        Cookie::flushQueuedCookies();
        $this->assertSame([], PanelApp::launches('admin'));
        $this->assertStringNotContainsString('launch', $handover());
    }
}
