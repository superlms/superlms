<?php

namespace Tests\Feature;

use App\Exceptions\OtpDeliveryException;
use App\Livewire\SuperAdmin\ForgotPassword;
use App\Livewire\SuperAdmin\Login;
use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The super admin's sign-in code goes by email and on WhatsApp to the
 * account's mobile and alternative mobile — the same code everywhere — and
 * any one of them arriving is enough. Every failure says what went wrong.
 */
class SuperAdminOtpWhatsAppTest extends TestCase
{
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

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('password')->nullable(); $t->string('mobile_number')->nullable();
            $t->string('alternative_mobile')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
    }

    private function user(?string $mobile = '98765 43210', ?string $alt = '9123456780'): User
    {
        $user = new User(['email' => 'boss@example.com', 'name' => 'Boss', 'mobile_number' => $mobile, 'alternative_mobile' => $alt]);
        $user->id = 11;

        return $user;
    }

    private function whatsAppRequests(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'))
            ->map(fn ($pair) => $pair[0])->values()->all();
    }

    private static function code(Request $wa): string
    {
        return $wa['template']['components'][0]['parameters'][0]['text'];
    }

    public function test_the_same_code_goes_by_email_and_to_both_whatsapp_numbers(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            'zepto.test/*'         => Http::response(['request_id' => 'r1']),
        ]);

        $user = $this->user();
        $challenge = OtpMailService::sendOtp($user, 'Super Admin', null, [$user->mobile_number, $user->alternative_mobile], $delivery);

        $this->assertTrue($delivery['email']['ok']);
        $this->assertSame(['919876543210', '919123456780'], array_column($delivery['whatsapp'], 'to'));
        $this->assertSame([true, true], array_column($delivery['whatsapp'], 'ok'));

        [$first, $second] = $this->whatsAppRequests();
        $this->assertSame('919876543210', $first['to']);
        $this->assertSame('919123456780', $second['to']);
        $this->assertSame('login_otp', $first['template']['name']);

        $mail = Http::recorded(fn (Request $r) => str_contains($r->url(), 'zepto.test'))->first()[0];

        $code = self::code($first);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertSame($code, self::code($second));
        $this->assertSame($code, $first['template']['components'][1]['parameters'][0]['text']);
        $this->assertSame($code, $mail['merge_info']['OTP']);
        $this->assertTrue(OtpMailService::verifyOtp($user, $code, $challenge));
    }

    public function test_one_number_twice_gets_one_message(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.x']], 'request_id' => 'r'])]);

        OtpMailService::sendOtp($this->user(), 'Super Admin', null, ['9876543210', '+91 98765 43210'], $delivery);

        $this->assertCount(1, $delivery['whatsapp']);
        $this->assertCount(1, $this->whatsAppRequests());
    }

    public function test_whatsapp_alone_is_enough_when_the_email_fails(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.2']]]),
            '*'                    => Http::response(['message' => 'Mail Credit exhausted'], 500),
        ]);

        $user = $this->user(alt: null);
        $challenge = OtpMailService::sendOtp($user, 'Super Admin', null, [$user->mobile_number], $delivery);

        $this->assertFalse($delivery['email']['ok']);
        $this->assertSame('the mail service (ZeptoMail) is out of credit', $delivery['email']['error']);
        [$wa] = $this->whatsAppRequests();
        $this->assertTrue(OtpMailService::verifyOtp($user, self::code($wa), $challenge));
    }

    public function test_nothing_delivered_fails_and_says_why_for_each(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => '(#132001) Template name does not exist in the translation']], 404),
            '*'                    => Http::response([], 500),
        ]);

        $user = $this->user(alt: '12345');

        try {
            OtpMailService::sendOtp($user, 'Super Admin', null, [$user->mobile_number, $user->alternative_mobile], $delivery);
            $this->fail('Expected the OTP not to be delivered.');
        } catch (OtpDeliveryException) {
            $this->assertFalse($delivery['email']['ok']);
            $this->assertStringStartsWith('the mail service refused it', $delivery['email']['error']);
            $this->assertSame('the WhatsApp template "login_otp" is not in Meta yet, or not approved', $delivery['whatsapp'][0]['error']);
            $this->assertSame('not a valid Indian mobile number', $delivery['whatsapp'][1]['error']);
        }
    }

    public function test_no_mobile_means_no_whatsapp(): void
    {
        Http::fake(['*' => Http::response(['request_id' => 'r2'])]);

        OtpMailService::sendOtp($this->user(null, null), 'Super Admin', null, [null, ''], $delivery);

        $this->assertSame([], $delivery['whatsapp']);
        $this->assertSame([], $this->whatsAppRequests());
    }

    public function test_other_panels_get_email_only(): void
    {
        Http::fake(['*' => Http::response(['request_id' => 'r3'])]);

        OtpMailService::sendOtp($this->user(), 'School Admin');

        $this->assertSame([], $this->whatsAppRequests());
    }

    public function test_the_migration_gives_the_main_super_admin_the_two_numbers(): void
    {
        DB::table('users')->insert([
            ['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin', 'mobile_number' => null],
            ['name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin', 'mobile_number' => '9000000001'],
        ]);

        (require base_path('database/migrations/2026_11_01_150000_set_super_admin_whatsapp_numbers.php'))->up();

        $main = DB::table('users')->where('role', 'super-admin')->first();
        $this->assertSame(['8864985914', '9368388050'], [$main->mobile_number, $main->alternative_mobile]);
        $this->assertSame('9000000001', DB::table('users')->where('role', 'sub-super-admin')->value('mobile_number'));
    }

    public function test_login_says_exactly_what_is_wrong(): void
    {
        DB::table('users')->insert(['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin', 'password' => Hash::make('Right@123')]);

        Livewire::test(Login::class)
            ->call('login')
            ->assertHasErrors(['email' => 'required', 'password' => 'required'])
            ->assertSee('Enter your email address.')
            ->assertSee('Enter your password.');

        Livewire::test(Login::class)->set('email', 'nobody@')->set('password', 'x')->call('login')
            ->assertSee('This is not a valid email address');

        Livewire::test(Login::class)->set('email', 'nobody@example.com')->set('password', 'x')->call('login')
            ->assertSee('No Super Admin account uses this email address.');

        Livewire::test(Login::class)->set('email', 'superlms.india@gmail.com')->set('password', 'Wrong@123')->call('login')
            ->assertSee('Wrong password for this email.');
    }

    public function test_login_shows_where_the_code_went_and_why_not(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['messages' => [['id' => 'wamid.3']]])
                ->push(['error' => ['message' => '(#131026) Message undeliverable']], 400),
            'zepto.test/*' => Http::response(['request_id' => 'r4']),
        ]);
        DB::table('users')->insert([
            'name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin',
            'password' => Hash::make('Right@123'), 'mobile_number' => '8864985914', 'alternative_mobile' => '9368388050',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'superlms.india@gmail.com')->set('password', 'Right@123')
            ->call('login')
            ->assertSet('step', 'otp')
            ->assertSee('superlms.india@gmail.com')
            ->assertSee('+91 ••••••5914')
            ->assertSee('+91 ••••••8050 — not sent: this number is not on WhatsApp, or cannot receive messages');
    }

    public function test_login_code_boxes_say_what_is_missing(): void
    {
        DB::table('users')->insert(['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin']);

        Livewire::test(Login::class)->set('email', 'superlms.india@gmail.com')->set('step', 'otp')
            ->set('otp', ['1', '2', '3', '', '', ''])->call('verifyOtp')
            ->assertSee('Enter all 6 digits — only 3 entered.')
            ->set('otp', ['', '', '', '', '', ''])->call('verifyOtp')
            ->assertSee('Enter the 6-digit code.')
            ->set('otp', ['1', 'a', '3', '4', '5', '6'])->call('verifyOtp')
            ->assertSee('The code has numbers only');
    }

    public function test_new_password_names_the_rule_it_breaks(): void
    {
        $test = Livewire::test(ForgotPassword::class)->set('step', 'password')->set('email', 'superlms.india@gmail.com');

        $test->set('password', 'ab1!')->set('password_confirmation', 'ab1!')->call('resetPassword')
            ->assertSee('Too short — 4 characters. Use 8 to 16.');
        $test->set('password', 'abcdefgh!')->set('password_confirmation', 'abcdefgh!')->call('resetPassword')
            ->assertSee('Add at least one number (0–9).');
        $test->set('password', 'abcdefg1')->set('password_confirmation', 'abcdefg1')->call('resetPassword')
            ->assertSee('Add at least one special character');
        $test->set('password', 'abcdef1!')->set('password_confirmation', 'abcdef1?')->call('resetPassword')
            ->assertSee('This does not match the new password above.');
    }
}
