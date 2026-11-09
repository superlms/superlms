<?php

namespace Tests\Feature;

use App\Livewire\Admin\Login;
use App\Livewire\ResetPassword;
use App\Models\User;
use App\Support\AdminAppOtp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The school admin's and sub-admin's login and forgot password, in the Super
 * Admin's flow: the code goes by email and on WhatsApp to the registered
 * mobile number (the school's when the account has none), either is enough,
 * and only the account whose password (or email) started the flow can finish it.
 */
class SchoolAdminOtpMobileTest extends TestCase
{
    private const PASSWORD = 'Secret#123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key'                             => 'base64:' . base64_encode(str_repeat('k', 32)),
            'mail.default'                        => 'array',
            'services.otp.login_enabled'          => true,
            'services.zeptomail.api_url'          => 'https://zepto.test/v1.1',
            'services.zeptomail.api_token'        => 'zepto-token',
            'services.zeptomail.otp_template_key' => 'otp-key',
            'services.whatsapp.enabled'           => true,
            'services.whatsapp.token'             => 'wa-token',
            'services.whatsapp.phone_number_id'   => '123',
            'services.whatsapp.templates.otp'     => 'login_otp',
        ]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('mobile_number')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('password')->nullable();
            $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('mobile_number')->nullable(); $t->boolean('is_active')->default(true);
            $t->text('permissions')->nullable(); $t->string('remember_token')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 9, 'name' => 'Doon School', 'mobile_number' => '9811122233']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            'zepto.test/*'         => Http::response(['request_id' => 'r1']),
        ]);
    }

    private function user(string $role, string $email, ?string $mobile, array $extra = []): User
    {
        return User::forceCreate(array_merge([
            'name' => ucfirst($role), 'email' => $email, 'password' => Hash::make(self::PASSWORD),
            'role' => $role, 'organization_id' => 9, 'mobile_number' => $mobile, 'is_active' => true,
        ], $extra));
    }

    /** @return list<Request> */
    private function whatsApps(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'))
            ->map(fn ($pair) => $pair[0])->values()->all();
    }

    private function lastCode(): string
    {
        $all = $this->whatsApps();

        return end($all)['template']['components'][0]['parameters'][0]['text'];
    }

    private static function digits(string $code): array
    {
        return str_split($code);
    }

    public function test_admin_login_sends_the_code_to_the_registered_mobile_and_signs_in_with_it(): void
    {
        $admin = $this->user('admin', 'head@doon.in', '98765 43210');

        $page = Livewire::test(Login::class)
            ->set('email', 'head@doon.in')->set('password', self::PASSWORD)
            ->call('login')
            ->assertHasNoErrors()
            ->assertSet('step', 'otp')
            ->assertSet('password', '')
            ->assertSee('registered mobile number');

        $sent = $this->whatsApps();
        $this->assertCount(1, $sent);
        $this->assertSame('919876543210', $sent[0]['to']);
        $this->assertSame('login_otp', $sent[0]['template']['name']);

        // The last digit typed verifies by itself.
        $page->set('otp', self::digits($this->lastCode()))
            ->assertRedirect(route('admin.quick-links', ['organization' => 9]));

        $this->assertSame($admin->id, Auth::guard('admin')->id());
    }

    public function test_a_sub_admin_gets_it_on_their_own_number_and_an_admin_with_none_on_the_schools(): void
    {
        $this->user('sub-admin', 'desk@doon.in', '9123456780', ['permissions' => json_encode(['admin.fee'])]);
        $this->user('admin', 'nomobile@doon.in', null);

        Livewire::test(Login::class)->set('email', 'desk@doon.in')->set('password', self::PASSWORD)->call('login')->assertSet('step', 'otp');
        Livewire::test(Login::class)->set('email', 'nomobile@doon.in')->set('password', self::PASSWORD)->call('login')->assertSet('step', 'otp');

        $this->assertSame(['919123456780', '919811122233'], array_map(fn ($r) => $r['to'], $this->whatsApps()));
    }

    public function test_the_code_step_signs_in_only_the_account_whose_password_was_checked(): void
    {
        $mine  = $this->user('admin', 'mine@doon.in', '9876543210');
        $other = $this->user('admin', 'other@doon.in', '9000000001');

        Livewire::test(Login::class)
            ->set('email', 'mine@doon.in')->set('password', self::PASSWORD)
            ->call('login')
            // Another address typed in on the code step changes nothing.
            ->set('email', 'other@doon.in')
            ->set('otp', self::digits($this->lastCode()));

        $this->assertSame($mine->id, Auth::guard('admin')->id());
        $this->assertNotSame($other->id, Auth::guard('admin')->id());
    }

    public function test_wrong_password_and_switched_off_sub_admin_send_nothing(): void
    {
        $this->user('admin', 'head@doon.in', '9876543210');
        $this->user('sub-admin', 'off@doon.in', '9123456780', ['is_active' => false]);

        Livewire::test(Login::class)->set('email', 'head@doon.in')->set('password', 'Wrong#123')
            ->call('login')->assertHasErrors('password')->assertSet('step', 'credentials');
        Livewire::test(Login::class)->set('email', 'off@doon.in')->set('password', self::PASSWORD)
            ->call('login')->assertHasErrors('email')->assertSet('step', 'credentials');

        $this->assertSame([], $this->whatsApps());
    }

    public function test_forgot_password_sends_to_the_mobile_and_sets_the_new_password(): void
    {
        $admin = $this->user('admin', 'head@doon.in', '9876543210');

        $page = Livewire::test(ResetPassword::class)
            ->set('email', 'head@doon.in')
            ->call('submitEmail')
            ->assertHasNoErrors()
            ->assertSet('step', 'otp')
            ->assertSee('registered mobile number');

        $this->assertSame('919876543210', $this->whatsApps()[0]['to']);

        $page->set('otp', self::digits($this->lastCode()))
            ->assertSet('step', 'password')
            ->set('password', 'Fresh@2026')->set('password_confirmation', 'Fresh@2026')
            ->call('resetPassword')
            ->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('Fresh@2026', $admin->fresh()->password));
    }

    public function test_forgot_password_names_only_the_rules_the_password_breaks(): void
    {
        $this->user('admin', 'head@doon.in', '9876543210');

        Livewire::test(ResetPassword::class)
            ->set('email', 'head@doon.in')->call('submitEmail')
            ->set('otp', self::digits($this->lastCode()))
            ->set('password', 'short')->set('password_confirmation', 'short')
            ->call('resetPassword')
            ->assertHasErrors('password')
            ->assertSet('step', 'password');
    }

    public function test_the_super_admin_is_not_reset_from_the_school_admin_page(): void
    {
        $this->user('super-admin', 'superlms.india@gmail.com', '8864985914', ['organization_id' => null]);

        Livewire::test(ResetPassword::class)
            ->set('email', 'superlms.india@gmail.com')
            ->call('submitEmail')
            ->assertHasErrors('email')
            ->assertSet('step', 'email');

        $this->assertSame([], $this->whatsApps());
    }

    public function test_a_wrong_code_says_so_and_a_second_check_does_not_hide_it(): void
    {
        $this->user('admin', 'head@doon.in', '9876543210');

        $page = Livewire::test(Login::class)
            ->set('email', 'head@doon.in')->set('password', self::PASSWORD)->call('login');

        $wrong = $this->lastCode() === '000000' ? '111111' : '000000';
        $page->set('otp', self::digits($wrong))
            ->assertHasErrors('otp')
            ->assertSee('Invalid OTP. 2 attempts left.')
            ->assertSet('step', 'otp');

        $this->assertNull(Auth::guard('admin')->id());
    }

    public function test_a_reload_on_the_code_step_keeps_it_and_sends_nothing_new(): void
    {
        $admin = $this->user('admin', 'head@doon.in', '9876543210');

        Livewire::test(Login::class)->set('email', 'head@doon.in')->set('password', self::PASSWORD)->call('login');
        $code = $this->lastCode();

        // Ctrl+Shift+R a few times: still the code step, the same code, nothing sent.
        foreach (range(1, 3) as $i) {
            $page = Livewire::test(Login::class)->assertSet('step', 'otp')->assertSet('canResend', false);
        }
        $this->assertCount(1, $this->whatsApps());

        $page->set('otp', self::digits($code))
            ->assertRedirect(route('admin.quick-links', ['organization' => 9]));
        $this->assertSame($admin->id, Auth::guard('admin')->id());

        // Signed in: a later visit starts at the email and password.
        Livewire::test(Login::class)->assertSet('step', 'credentials');
    }

    public function test_a_reload_after_the_code_ran_out_starts_again(): void
    {
        $this->user('admin', 'head@doon.in', '9876543210');

        Livewire::test(Login::class)->set('email', 'head@doon.in')->set('password', self::PASSWORD)->call('login');
        $this->travel(121)->seconds();

        Livewire::test(Login::class)->assertSet('step', 'credentials');
    }

    public function test_a_reload_on_the_forgot_password_code_step_keeps_it(): void
    {
        $admin = $this->user('admin', 'head@doon.in', '9876543210');

        Livewire::test(ResetPassword::class)->set('email', 'head@doon.in')->call('submitEmail');
        $code = $this->lastCode();

        Livewire::test(ResetPassword::class)
            ->assertSet('step', 'otp')
            ->set('otp', self::digits($code))
            ->assertSet('step', 'password')
            ->set('password', 'Fresh@2026')->set('password_confirmation', 'Fresh@2026')
            ->call('resetPassword')
            ->assertRedirect(route('admin.login'));

        $this->assertCount(1, $this->whatsApps());
        $this->assertTrue(Hash::check('Fresh@2026', $admin->fresh()->password));
    }

    public function test_the_admin_app_login_code_goes_to_the_mobile_too(): void
    {
        $admin = $this->user('admin', 'head@doon.in', '9876543210');

        AdminAppOtp::challenge($admin, 'login');

        $this->assertSame('919876543210', $this->whatsApps()[0]['to']);
    }
}
