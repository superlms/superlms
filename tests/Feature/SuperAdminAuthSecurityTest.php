<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\ForgotPassword;
use App\Livewire\SuperAdmin\Login;
use App\Models\User;
use App\Support\AuthThrottle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Super Admin's login and forgot password can't be walked around: the
 * code step only works for the account whose password (or email) was checked
 * on that page, a verified code resets only that account's password and only
 * once, the waits are the server's, and guessing is cut off.
 */
class SuperAdminAuthSecurityTest extends TestCase
{
    private const MAIN = 'superlms.india@gmail.com';
    private const SUB  = 'sub@example.com';

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

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            'zepto.test/*'         => Http::response(['request_id' => 'r1']),
        ]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('password')->nullable(); $t->string('mobile_number')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true); $t->string('remember_token')->nullable(); $t->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Main', 'email' => self::MAIN, 'role' => 'super-admin',
             'password' => Hash::make('Main@1234'), 'mobile_number' => '8864985914', 'is_active' => true],
            ['id' => 2, 'name' => 'Sub', 'email' => self::SUB, 'role' => 'sub-super-admin',
             'password' => Hash::make('Sub@12345'), 'mobile_number' => '9123456780', 'is_active' => true],
        ]);
    }

    /** Codes sent on WhatsApp so far, newest last: [to, code]. */
    private function codes(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'))
            ->map(fn ($pair) => [$pair[0]['to'], $pair[0]['template']['components'][0]['parameters'][0]['text']])
            ->values()->all();
    }

    private function lastCode(): string
    {
        $codes = $this->codes();
        $this->assertNotEmpty($codes, 'No code was sent.');

        return $codes[count($codes) - 1][1];
    }

    private function password(int $id): string
    {
        return DB::table('users')->where('id', $id)->value('password');
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function test_the_code_step_cannot_be_reached_without_the_password(): void
    {
        // Straight to the code step with the main super admin's email: no code
        // goes out, nothing signs in.
        Livewire::test(Login::class)
            ->set('email', self::MAIN)->set('step', 'otp')->set('canResend', true)
            ->call('resendOtp')
            ->assertSet('step', 'credentials')
            ->assertSee('This sign-in has expired. Log in again.')
            ->set('step', 'otp')->set('otp', str_split('123456'))
            ->call('verifyOtp')
            ->assertSet('step', 'credentials');

        $this->assertSame([], $this->codes());
        $this->assertFalse(Auth::guard('superadmin')->check());
    }

    public function test_another_email_on_the_code_step_changes_nothing(): void
    {
        // The sub super admin's password, then the main super admin's email.
        $login = Livewire::test(Login::class)
            ->set('email', self::SUB)->set('password', 'Sub@12345')->call('login')
            ->assertSet('step', 'otp')
            ->assertSet('password', '');
        $this->assertSame('919123456780', $this->codes()[0][0]);

        $this->travel(3)->minutes();
        $login->set('email', self::MAIN)->set('canResend', true)->call('resendOtp');

        // The resend went to the sub super admin again, not the main one.
        $this->assertSame(['919123456780', '919123456780'], array_column($this->codes(), 0));

        $login->set('otp', str_split($this->lastCode()))->call('verifyOtp')
            ->assertRedirect(route('super-admin.quick-links'));
        $this->assertSame(2, Auth::guard('superadmin')->id());
    }

    public function test_the_pending_account_cannot_be_swapped_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(Login::class)
            ->set('email', self::SUB)->set('password', 'Sub@12345')->call('login')
            ->set('pendingUserId', 1);
    }

    public function test_a_sub_super_admin_switched_off_mid_sign_in_is_stopped_at_the_code(): void
    {
        $login = Livewire::test(Login::class)
            ->set('email', self::SUB)->set('password', 'Sub@12345')->call('login')
            ->assertSet('step', 'otp');

        DB::table('users')->where('id', 2)->update(['is_active' => false]);

        $login->set('otp', str_split($this->lastCode()))->call('verifyOtp')
            ->assertSet('step', 'credentials');
        $this->assertFalse(Auth::guard('superadmin')->check());
    }

    public function test_resend_keeps_the_servers_two_minutes(): void
    {
        $login = Livewire::test(Login::class)
            ->set('email', self::MAIN)->set('password', 'Main@1234')->call('login');

        // The page says it may resend; the server says not yet.
        $login->set('canResend', true)->call('resendOtp')
            ->assertSet('canResend', false);
        $this->assertCount(1, $this->codes());

        $this->travel(121)->seconds();
        $login->set('canResend', true)->call('resendOtp');
        $this->assertCount(2, $this->codes());
    }

    public function test_wrong_passwords_are_cut_off_then_the_right_one_waits_too(): void
    {
        for ($i = 0; $i < AuthThrottle::PASSWORD_PER_EMAIL; $i++) {
            Livewire::test(Login::class)->set('email', self::MAIN)->set('password', 'Guess' . $i)->call('login')
                ->assertSee('Wrong password for this email.');
        }

        Livewire::test(Login::class)->set('email', self::MAIN)->set('password', 'Main@1234')->call('login')
            ->assertSet('step', 'credentials')
            ->assertSee('Too many tries. Try again after');
        $this->assertSame([], $this->codes());

        // The wait passes and the right password works again.
        $this->travel(AuthThrottle::PASSWORD_PER_EMAIL_SECONDS + 1)->seconds();
        Livewire::test(Login::class)->set('email', self::MAIN)->set('password', 'Main@1234')->call('login')
            ->assertSet('step', 'otp');
    }

    public function test_one_address_trying_many_emails_is_cut_off(): void
    {
        for ($i = 0; $i < AuthThrottle::PASSWORD_PER_IP; $i++) {
            Livewire::test(Login::class)->set('email', "guess{$i}@example.com")->set('password', 'x')->call('login');
        }

        Livewire::test(Login::class)->set('email', self::SUB)->set('password', 'Sub@12345')->call('login')
            ->assertSee('Too many tries. Try again after');
    }

    public function test_codes_to_one_account_are_capped(): void
    {
        for ($i = 0; $i < AuthThrottle::SENDS_PER_ACCOUNT; $i++) {
            Livewire::test(Login::class)->set('email', self::MAIN)->set('password', 'Main@1234')->call('login')
                ->assertSet('step', 'otp');
        }

        Livewire::test(Login::class)->set('email', self::MAIN)->set('password', 'Main@1234')->call('login')
            ->assertSet('step', 'credentials')
            ->assertSee('Too many codes were asked for this account.');
        $this->assertCount(AuthThrottle::SENDS_PER_ACCOUNT, $this->codes());
    }

    public function test_sql_in_the_fields_is_only_text(): void
    {
        Livewire::test(Login::class)->set('email', "' OR '1'='1' -- ")->set('password', "' OR '1'='1")->call('login')
            ->assertHasErrors('email')
            ->assertSet('step', 'credentials');

        Livewire::test(Login::class)->set('email', self::MAIN)->set('password', "' OR '1'='1")->call('login')
            ->assertSee('Wrong password for this email.');

        Livewire::test(ForgotPassword::class)->set('email', "x@example.com' OR 1=1 --")->call('submitEmail')
            ->assertHasErrors('email')->assertSet('step', 'email');

        $this->assertSame(2, DB::table('users')->count());
        $this->assertSame([], $this->codes());
        $this->assertFalse(Auth::guard('superadmin')->check());
    }

    // ─── Forgot password ──────────────────────────────────────────────────────

    public function test_the_new_password_step_cannot_be_reached_without_a_verified_code(): void
    {
        $before = $this->password(1);

        Livewire::test(ForgotPassword::class)
            ->set('email', self::MAIN)->set('step', 'password')
            ->set('password', 'Hacked@123')->set('password_confirmation', 'Hacked@123')
            ->call('resetPassword')
            ->assertSet('step', 'email')
            ->assertSee('The code was not verified here');

        $this->assertSame($before, $this->password(1));
    }

    public function test_a_code_verified_in_another_browser_does_not_open_this_one(): void
    {
        $before = $this->password(1);

        // The main super admin really is resetting, and has verified the code.
        Livewire::test(ForgotPassword::class)
            ->set('email', self::MAIN)->call('submitEmail')
            ->set('otp', str_split($this->lastCode()))
            ->assertSet('step', 'password');

        // Someone else, meanwhile, with the same email.
        Livewire::test(ForgotPassword::class)
            ->set('email', self::MAIN)->set('step', 'password')
            ->set('password', 'Hacked@123')->set('password_confirmation', 'Hacked@123')
            ->call('resetPassword')
            ->assertSet('step', 'email');

        $this->assertSame($before, $this->password(1));
    }

    public function test_a_code_verified_for_one_account_resets_only_that_one(): void
    {
        $main = $this->password(1);

        $reset = Livewire::test(ForgotPassword::class)
            ->set('email', self::SUB)->call('submitEmail')
            ->set('otp', str_split($this->lastCode()))
            ->assertSet('step', 'password');

        // The main super admin's email typed in now does not move the reset to it.
        $reset->set('email', self::MAIN)
            ->set('password', 'Fresh@1234')->set('password_confirmation', 'Fresh@1234')
            ->call('resetPassword')
            ->assertRedirect(route('super-admin.login'));

        $this->assertSame($main, $this->password(1));
        $this->assertTrue(Hash::check('Fresh@1234', $this->password(2)));
    }

    public function test_a_verified_code_sets_a_password_once(): void
    {
        $reset = Livewire::test(ForgotPassword::class)
            ->set('email', self::SUB)->call('submitEmail')
            ->set('otp', str_split($this->lastCode()))
            ->assertSet('step', 'password');

        $reset->set('password', 'First@1234')->set('password_confirmation', 'First@1234')->call('resetPassword')
            ->assertRedirect(route('super-admin.login'));

        // Played again: refused.
        $reset->set('step', 'password')
            ->set('password', 'Again@1234')->set('password_confirmation', 'Again@1234')->call('resetPassword')
            ->assertSet('step', 'email');

        $this->assertTrue(Hash::check('First@1234', $this->password(2)));
    }

    public function test_a_verified_code_runs_out_after_ten_minutes(): void
    {
        $before = $this->password(2);

        $reset = Livewire::test(ForgotPassword::class)
            ->set('email', self::SUB)->call('submitEmail')
            ->set('otp', str_split($this->lastCode()))
            ->assertSet('step', 'password');

        $this->travel(11)->minutes();
        $reset->set('password', 'Late@12345')->set('password_confirmation', 'Late@12345')->call('resetPassword')
            ->assertSet('step', 'email');

        $this->assertSame($before, $this->password(2));
    }

    public function test_three_wrong_codes_lock_the_reset(): void
    {
        $reset = Livewire::test(ForgotPassword::class)->set('email', self::SUB)->call('submitEmail');
        $right = $this->lastCode();
        $wrong = str_split($right === '000000' ? '111111' : '000000');

        // Six digits in the boxes check the code on their own.
        $reset->set('otp', $wrong)->assertSee('2 attempts left')
            ->set('otp', $wrong)->assertSee('1 attempt left')
            ->set('otp', $wrong)->assertSee('Too many incorrect attempts');

        // Even the right code is refused now.
        $reset->set('otp', str_split($right))->assertSet('step', 'otp')->assertSee('Too many incorrect attempts');
    }

    public function test_forgot_password_resend_keeps_the_servers_two_minutes(): void
    {
        $reset = Livewire::test(ForgotPassword::class)->set('email', self::SUB)->call('submitEmail');

        $reset->set('canResend', true)->call('resendOtp')->assertSet('canResend', false);
        $this->assertCount(1, $this->codes());
    }

    public function test_email_lookups_from_one_address_are_capped(): void
    {
        for ($i = 0; $i < AuthThrottle::LOOKUPS_PER_IP; $i++) {
            Livewire::test(ForgotPassword::class)->set('email', "nobody{$i}@example.com")->call('submitEmail')
                ->assertSee('No Super Admin account uses this email address.');
        }

        Livewire::test(ForgotPassword::class)->set('email', self::SUB)->call('submitEmail')
            ->assertSet('step', 'email')
            ->assertSee('Too many tries. Try again after');
        $this->assertSame([], $this->codes());
    }
}
