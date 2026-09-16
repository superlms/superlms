<?php

namespace Tests\Feature;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Each OTP request has its own code: the same account signing in from two
 * devices at once gets two codes, and each only works for its own device.
 */
class OtpRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Never mail anyone from a test: both channels fail here.
        config(['services.zeptomail.otp_template_key' => null, 'mail.default' => 'array']);
        Http::fake(['*' => Http::response([], 500)]);
    }

    private function user(int $id = 7): User
    {
        $user = new User(['email' => 'someone@example.com', 'name' => 'Someone']);
        $user->id = $id;

        return $user;
    }

    /** No mailer is configured in tests, so the code comes back on the exception. */
    private function send(User $user, ?string $challenge = null): array
    {
        try {
            OtpMailService::sendOtp($user, 'Test', $challenge);
        } catch (OtpDeliveryException $e) {
            return [$e->challenge, $e->otp];
        }

        $this->fail('Expected the test mailer not to deliver.');
    }

    private function wrongCode(string $otp): string
    {
        return str_pad((string) ((((int) $otp) + 1) % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function test_each_device_signs_in_with_its_own_code_only(): void
    {
        $user = $this->user();
        [$mine, $myCode]     = $this->send($user);
        [$theirs, $theirCode] = $this->send($user);

        $this->assertNotSame($mine, $theirs);

        // My code on their request fails — their request stays open.
        if ($myCode !== $theirCode) {
            try {
                OtpMailService::verifyOtp($user, $myCode, $theirs);
                $this->fail('Another request\'s code must not verify.');
            } catch (\Exception $e) {
                $this->assertStringStartsWith('Invalid OTP.', $e->getMessage());
            }
        }

        $this->assertTrue(OtpMailService::verifyOtp($user, $theirCode, $theirs));
        // Their sign-in doesn't use up my request.
        $this->assertTrue(OtpMailService::verifyOtp($user, $myCode, $mine));
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->user();
        [$challenge, $code] = $this->send($user);

        $this->assertTrue(OtpMailService::verifyOtp($user, $code, $challenge));

        $this->expectExceptionMessage('OTP has expired. Please request a new one.');
        OtpMailService::verifyOtp($user, $code, $challenge);
    }

    public function test_a_request_belongs_to_its_user(): void
    {
        [$challenge, $code] = $this->send($this->user(7));

        $this->expectExceptionMessage('OTP has expired. Please request a new one.');
        OtpMailService::verifyOtp($this->user(8), $code, $challenge);
    }

    public function test_resend_keeps_the_request_and_replaces_only_its_code(): void
    {
        $user = $this->user();
        [$mine, $oldCode]     = $this->send($user);
        [$theirs, $theirCode] = $this->send($user);
        [$again, $newCode]    = $this->send($user, $mine);

        $this->assertSame($mine, $again);

        if ($oldCode !== $newCode) {
            try {
                OtpMailService::verifyOtp($user, $oldCode, $mine);
                $this->fail('A resent request must drop its old code.');
            } catch (\Exception $e) {
                $this->assertStringStartsWith('Invalid OTP.', $e->getMessage());
            }
        }

        $this->assertTrue(OtpMailService::verifyOtp($user, $newCode, $mine));
        $this->assertTrue(OtpMailService::verifyOtp($user, $theirCode, $theirs));
    }

    public function test_resend_cooldown_is_per_request(): void
    {
        $user = $this->user();
        [$mine] = $this->send($user);

        $this->assertGreaterThan(0, OtpMailService::resendAvailableIn($user, $mine));
        $this->assertSame(0, OtpMailService::resendAvailableIn($user, 'unknown'));
        $this->assertSame(0, OtpMailService::resendAvailableIn($user, null));
    }

    public function test_only_a_verified_reset_request_lets_a_password_change_once(): void
    {
        $user = $this->user();
        [$login, $loginCode] = $this->send($user);
        [$reset, $resetCode] = $this->send($user);

        $this->assertFalse(OtpMailService::consumeVerified($user, $reset));
        $this->assertFalse(OtpMailService::consumeVerified($user, null));

        OtpMailService::verifyOtp($user, $loginCode, $login);
        $this->assertFalse(OtpMailService::consumeVerified($user, $login));

        OtpMailService::verifyOtp($user, $resetCode, $reset, forReset: true);
        $this->assertFalse(OtpMailService::consumeVerified($this->user(8), $reset));
        $this->assertTrue(OtpMailService::consumeVerified($user, $reset));
        $this->assertFalse(OtpMailService::consumeVerified($user, $reset));
    }

    public function test_three_wrong_codes_lock_the_account(): void
    {
        $user = $this->user();
        [$challenge, $code] = $this->send($user);
        $wrong = $this->wrongCode($code);

        foreach (['Invalid OTP. 2 attempts left.', 'Invalid OTP. 1 attempt left.'] as $message) {
            try {
                OtpMailService::verifyOtp($user, $wrong, $challenge);
            } catch (\Exception $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }

        try {
            OtpMailService::verifyOtp($user, $wrong, $challenge);
            $this->fail('The third wrong code must lock the account.');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Too many incorrect attempts.', $e->getMessage());
        }

        $this->assertGreaterThan(0, OtpMailService::lockedUntil($user));
        $this->expectExceptionMessage('Too many incorrect attempts.');
        OtpMailService::sendOtp($user, 'Test');
    }

    public function test_the_latest_request_stands_in_for_clients_without_a_token(): void
    {
        $user = $this->user();
        $this->send($user);
        [$latest, $code] = $this->send($user);

        $this->assertSame($latest, OtpMailService::latestChallenge($user));
        $this->assertTrue(OtpMailService::verifyOtp($user, $code, OtpMailService::latestChallenge($user)));
    }
}
