<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The app's Forgot Password: the code goes by email AND on WhatsApp to the
 * registered mobile — a student's own phone (else their account's), a
 * teacher's mobile, a school admin's own number (else the school's). Either
 * one is enough; the reply says, half hidden, only where it really went.
 */
class AppForgotPasswordWhatsAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        config([
            'app.key'                             => 'base64:' . base64_encode(str_repeat('k', 32)),
            'mail.default'                        => 'array',
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
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('username', 30)->nullable();
            $t->string('password')->nullable(); $t->string('password_plain')->nullable();
            $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('mobile_number')->nullable(); $t->boolean('is_active')->default(true);
            $t->string('remember_token')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('admission_no')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token', 64)->unique();
            $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 9, 'name' => 'Doon School', 'mobile_number' => '9811122233']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            'zepto.test/*'         => Http::response(['request_id' => 'r1']),
        ]);
    }

    private function user(string $role, ?string $email, ?string $mobile, array $extra = []): User
    {
        return User::forceCreate(array_merge([
            'name' => ucfirst($role), 'email' => $email, 'password' => Hash::make('Old#Pass1'),
            'role' => $role, 'organization_id' => 9, 'mobile_number' => $mobile, 'is_active' => true,
        ], $extra));
    }

    private function student(?string $email, ?string $accountMobile, ?string $phone): User
    {
        $user = $this->user('user', $email, $accountMobile);
        DB::table('student_details')->insert([
            'user_id' => $user->id, 'organization_id' => 9, 'admission_no' => 'ADM' . $user->id, 'phone' => $phone,
        ]);

        return $user;
    }

    /** @return list<Request> */
    private function whatsApps(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'))
            ->map(fn ($pair) => $pair[0])->values()->all();
    }

    /** @return list<Request> */
    private function emails(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'zepto.test'))
            ->map(fn ($pair) => $pair[0])->values()->all();
    }

    private function lastWhatsAppCode(): string
    {
        $all = $this->whatsApps();

        return end($all)['template']['components'][0]['parameters'][0]['text'];
    }

    public function test_a_student_gets_the_code_by_email_and_on_whatsapp_to_their_own_phone(): void
    {
        $student = $this->student('ravi.gupta@gmail.com', '9000000001', '98765 43210');

        $this->postJson('/api/v1/forgot-password', ['identifier' => 'ADM' . $student->id])
            ->assertOk()
            ->assertJsonPath('data.user_id', $student->id)
            ->assertJsonPath('data.email', 'ra****ta@gmail.com')
            ->assertJsonPath('data.mobile', '+91 ••••••3210')
            ->assertJsonPath('message', 'OTP sent to your email and WhatsApp.');

        $sent = $this->whatsApps();
        $this->assertCount(1, $sent);
        $this->assertSame('919876543210', $sent[0]['to']);
        $this->assertSame('login_otp', $sent[0]['template']['name']);
        $this->assertCount(1, $this->emails());
    }

    public function test_the_whatsapp_code_resets_the_password(): void
    {
        $student = $this->student('ravi@gmail.com', null, '9876543210');

        $token = $this->postJson('/api/v1/forgot-password', ['identifier' => 'ADM' . $student->id])
            ->assertOk()->json('data.otp_token');

        $this->postJson('/api/v1/verify-otp', [
            'user_id' => $student->id, 'otp' => $this->lastWhatsAppCode(), 'otp_token' => $token,
        ])->assertOk();

        $res = $this->postJson('/api/v1/change-password', [
            'user_id' => $student->id, 'otp_token' => $token,
            'password' => 'New#Pass12', 'password_confirmation' => 'New#Pass12',
        ]);
        $this->assertSame(200, $res->status(), (string) $res->json('message'));

        $this->assertTrue(Hash::check('New#Pass12', $student->fresh()->password));
    }

    public function test_a_teacher_and_an_admin_get_it_on_their_registered_numbers(): void
    {
        $this->user('teacher', 'meera@gmail.com', '9123456780', ['username' => 'meera@tds']);
        $this->user('admin', 'head@doon.in', null); // none of their own → the school's

        $this->postJson('/api/v1/forgot-password', ['identifier' => 'meera@tds'])
            ->assertOk()->assertJsonPath('data.mobile', '+91 ••••••6780');
        $this->postJson('/api/v1/forgot-password', ['identifier' => 'head@doon.in'])
            ->assertOk()->assertJsonPath('data.mobile', '+91 ••••••2233');

        $this->assertSame(['919123456780', '919811122233'], array_map(fn ($r) => $r['to'], $this->whatsApps()));
        $this->assertCount(2, $this->emails());
    }

    public function test_a_student_without_an_email_still_gets_it_on_whatsapp(): void
    {
        $student = $this->student(null, '9876543210', null);

        $this->postJson('/api/v1/forgot-password', ['identifier' => 'ADM' . $student->id])
            ->assertOk()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.mobile', '+91 ••••••3210')
            ->assertJsonPath('message', 'OTP sent on WhatsApp.');

        $this->assertCount(1, $this->whatsApps());
        $this->assertCount(0, $this->emails());
    }

    public function test_no_email_and_no_mobile_is_refused_and_email_alone_still_works(): void
    {
        $none = $this->student(null, null, null);
        $this->postJson('/api/v1/forgot-password', ['identifier' => 'ADM' . $none->id])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'no email address or mobile number'));

        $emailOnly = $this->student('only@mail.com', null, '12345');
        $this->postJson('/api/v1/forgot-password', ['identifier' => 'ADM' . $emailOnly->id])
            ->assertOk()
            ->assertJsonPath('data.mobile', null)
            ->assertJsonPath('message', 'OTP sent successfully to your email address.');

        $this->assertCount(0, $this->whatsApps());
    }

    public function test_a_resend_goes_to_both_again(): void
    {
        $student = $this->student('ravi@gmail.com', null, '9876543210');
        $token = $this->postJson('/api/v1/forgot-password', ['identifier' => 'ADM' . $student->id])->json('data.otp_token');

        $this->travel(121)->seconds();

        $this->postJson('/api/v1/resend-otp', ['user_id' => $student->id, 'otp_token' => $token])
            ->assertOk()
            ->assertJsonPath('data.otp_token', $token)
            ->assertJsonPath('data.mobile', '+91 ••••••3210')
            ->assertJsonPath('message', 'OTP resent to your email and WhatsApp.');

        $this->assertCount(2, $this->whatsApps());
        $this->assertCount(2, $this->emails());
    }
}
