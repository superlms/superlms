<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The app signs a school admin in only after the code mailed to them, as the
 * web admin login does — at the login screen and in the account switcher —
 * and offers them LMS Assist.
 */
class AdminAppLoginTest extends TestCase
{
    private const PASSWORD = 'Secret@123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('logo')->nullable();
            $t->string('school_code')->nullable();
            $t->string('education_board')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('password_plain')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->string('image')->nullable();
            $t->text('permissions')->nullable();
            $t->string('otp')->nullable();
            $t->timestamp('otp_expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 9, 'name' => 'Test School']);

        // Codes go out through the app mailer here (no ZeptoMail), and are caught.
        config([
            'services.zeptomail.otp_template_key' => null,
            'services.otp.login_enabled' => true,
            'mail.default' => 'smtp',
            'gemini.api_key' => null,
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
            'organization_id' => 9,
            'is_active' => true,
        ], $extra));
    }

    private function login(string $email, bool $otpSupported = true)
    {
        return $this->postJson('/api/v1/login', array_filter([
            'identifier' => $email,
            'password' => self::PASSWORD,
            'otp_supported' => $otpSupported ?: null,
        ]));
    }

    /** The code in the last mail sent. */
    private function mailedCode(): string
    {
        $code = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code) {
            $code = $mail->otp;

            return true;
        });

        return $code;
    }

    private function tokens(): int
    {
        return DB::table('personal_access_tokens')->count();
    }

    public function test_an_admin_gets_a_session_only_after_the_mailed_code(): void
    {
        $admin = $this->user('admin');

        $challenge = $this->login('admin@example.com')
            ->assertOk()
            ->assertJsonPath('data.otp_required', true)
            ->assertJsonPath('data.user_id', $admin->id)
            ->assertJsonMissingPath('data.token')
            ->json('data');
        $this->assertSame(0, $this->tokens());

        $code = $this->mailedCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/login/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $challenge['otp_token'], 'otp' => $wrong,
        ])->assertStatus(401)->assertJsonPath('message', 'Invalid OTP. 2 attempts left.');

        $this->postJson('/api/v1/login/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $challenge['otp_token'], 'otp' => $code,
        ])->assertOk()
            ->assertJsonPath('data.user_type', 'admin')
            ->assertJsonPath('data.user.permissions', ['*']);
        $this->assertSame(1, $this->tokens());

        // Used up.
        $this->postJson('/api/v1/login/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $challenge['otp_token'], 'otp' => $code,
        ])->assertStatus(401);
        $this->assertSame(1, $this->tokens());
    }

    public function test_an_older_app_is_asked_to_update_and_gets_no_session(): void
    {
        $this->user('admin');
        $this->user('sub-admin', ['email' => 'sub@example.com', 'permissions' => ['admin.student']]);

        $this->login('admin@example.com', otpSupported: false)->assertStatus(426);
        $this->login('sub@example.com', otpSupported: false)->assertStatus(426);
        $this->postJson('/api/v1/admin/login', ['email' => 'admin@example.com', 'password' => self::PASSWORD])
            ->assertStatus(426);

        $this->assertSame(0, $this->tokens());
        Mail::assertNothingSent();
    }

    public function test_other_roles_sign_in_as_before_and_admins_do_when_the_code_is_off(): void
    {
        $this->user('teacher');
        $this->user('admin');

        $this->login('teacher@example.com', otpSupported: false)
            ->assertOk()->assertJsonPath('data.user_type', 'teacher');

        config(['services.otp.login_enabled' => false]);
        $this->login('admin@example.com')
            ->assertOk()->assertJsonPath('data.user_type', 'admin');

        $this->assertSame(2, $this->tokens());
        Mail::assertNothingSent();
    }

    public function test_the_web_refusals_hold(): void
    {
        $this->user('sub-admin', ['email' => 'sub@example.com', 'is_active' => false]);
        $this->user('admin', ['organization_id' => null]);

        $this->login('sub@example.com')->assertStatus(403);
        $this->login('admin@example.com')->assertStatus(403);

        Mail::assertNothingSent();
    }

    public function test_a_code_only_works_for_the_flow_that_asked_for_it(): void
    {
        $admin = $this->user('admin');

        // A password-reset code can't sign in.
        $reset = $this->postJson('/api/v1/forgot-password', ['email' => 'admin@example.com'])->assertOk()->json('data');
        $this->postJson('/api/v1/login/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $reset['otp_token'], 'otp' => $this->mailedCode(),
        ])->assertStatus(401)->assertJsonPath('message', 'This code has expired. Please sign in again.');

        // A login code can't add the account to the switcher, nor another user's.
        $login = $this->login('admin@example.com')->json('data');
        $other = $this->user('sub-admin', ['email' => 'sub@example.com']);
        $this->postJson('/api/v1/switch-account/add/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $login['otp_token'], 'otp' => '123456',
        ])->assertStatus(401);
        $this->postJson('/api/v1/login/verify-otp', [
            'user_id' => $other->id, 'otp_token' => $login['otp_token'], 'otp' => '123456',
        ])->assertStatus(401);

        $this->assertSame(0, $this->tokens());
    }

    public function test_a_resend_waits_its_turn(): void
    {
        $admin = $this->user('admin');
        $login = $this->login('admin@example.com')->json('data');

        $this->postJson('/api/v1/login/resend-otp', [
            'user_id' => $admin->id, 'otp_token' => $login['otp_token'],
        ])->assertStatus(429);

        $this->travel(121)->seconds();

        $this->postJson('/api/v1/login/resend-otp', [
            'user_id' => $admin->id, 'otp_token' => $login['otp_token'], 'purpose' => 'switch',
        ])->assertStatus(401);

        $this->postJson('/api/v1/login/resend-otp', [
            'user_id' => $admin->id, 'otp_token' => $login['otp_token'],
        ])->assertOk()->assertJsonPath('data.otp_token', $login['otp_token']);

        Mail::assertSentCount(2);
        $this->postJson('/api/v1/login/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $login['otp_token'], 'otp' => $this->lastCode(),
        ])->assertOk();
    }

    private function lastCode(): string
    {
        return Mail::sent(LoginOtpMail::class)->last()->otp;
    }

    public function test_the_account_switcher_adds_an_admin_after_the_code(): void
    {
        $admin = $this->user('admin');

        $this->postJson('/api/v1/switch-account/add', [
            'identifier' => 'admin@example.com', 'password' => self::PASSWORD,
        ])->assertStatus(426);

        $challenge = $this->postJson('/api/v1/switch-account/add', [
            'identifier' => 'admin@example.com', 'password' => self::PASSWORD, 'otp_supported' => true,
        ])->assertOk()->assertJsonPath('data.otp_required', true)->json('data');
        $this->assertSame(0, $this->tokens());

        $this->postJson('/api/v1/switch-account/add/verify-otp', [
            'user_id' => $admin->id, 'otp_token' => $challenge['otp_token'], 'otp' => $this->mailedCode(),
        ])->assertOk()
            ->assertJsonPath('data.account.user_type', 'admin')
            ->assertJsonPath('data.token_type', 'Bearer');
        $this->assertSame(1, $this->tokens());
    }

    public function test_lms_assist_is_offered_to_admins_only(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/v1/assistant')
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.scope', 'This school');

        $this->postJson('/api/v1/assistant/ask', ['question' => 'Aaj ki summary do'])
            ->assertStatus(503);

        Sanctum::actingAs($this->user('teacher'));
        $this->getJson('/api/v1/assistant')->assertStatus(403);
        $this->postJson('/api/v1/assistant/ask', ['question' => 'Hi'])->assertStatus(403);
    }
}
