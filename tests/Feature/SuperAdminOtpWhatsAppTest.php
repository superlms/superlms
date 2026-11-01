<?php

namespace Tests\Feature;

use App\Exceptions\OtpDeliveryException;
use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The super admin's sign-in code goes by email and on WhatsApp — the same
 * code on both — and either one arriving is enough to sign in.
 */
class SuperAdminOtpWhatsAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default'                       => 'array',
            'services.zeptomail.api_url'         => 'https://zepto.test/v1.1',
            'services.zeptomail.api_token'       => 'zepto-token',
            'services.zeptomail.otp_template_key' => 'otp-key',
            'services.whatsapp.enabled'          => true,
            'services.whatsapp.token'            => 'wa-token',
            'services.whatsapp.phone_number_id'  => '123',
            'services.whatsapp.templates.otp'    => 'login_otp',
        ]);
    }

    private function user(?string $mobile = '98765 43210'): User
    {
        $user = new User(['email' => 'boss@example.com', 'name' => 'Boss', 'mobile_number' => $mobile]);
        $user->id = 11;

        return $user;
    }

    private function whatsAppRequests(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'))
            ->map(fn ($pair) => $pair[0])->all();
    }

    public function test_the_same_code_goes_by_email_and_on_whatsapp(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            'zepto.test/*'         => Http::response(['request_id' => 'r1']),
        ]);

        $user = $this->user();
        $challenge = OtpMailService::sendOtp($user, 'Super Admin', null, $user->mobile_number, $sent);

        $this->assertTrue($sent);

        [$wa] = $this->whatsAppRequests();
        $this->assertSame('919876543210', $wa['to']);
        $this->assertSame('login_otp', $wa['template']['name']);
        $waCode     = $wa['template']['components'][0]['parameters'][0]['text'];
        $buttonCode = $wa['template']['components'][1]['parameters'][0]['text'];

        $mail = Http::recorded(fn (Request $r) => str_contains($r->url(), 'zepto.test'))->first()[0];
        $mailCode = $mail['merge_info']['OTP'];

        $this->assertMatchesRegularExpression('/^\d{6}$/', $waCode);
        $this->assertSame($waCode, $mailCode);
        $this->assertSame($waCode, $buttonCode);
        $this->assertTrue(OtpMailService::verifyOtp($user, $waCode, $challenge));
    }

    public function test_whatsapp_alone_is_enough_when_the_email_fails(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.2']]]),
            '*'                    => Http::response([], 500),
        ]);

        $user = $this->user();
        $challenge = OtpMailService::sendOtp($user, 'Super Admin', null, $user->mobile_number, $sent);

        $this->assertTrue($sent);
        [$wa] = $this->whatsAppRequests();
        $this->assertTrue(OtpMailService::verifyOtp($user, $wa['template']['components'][0]['parameters'][0]['text'], $challenge));
    }

    public function test_nothing_delivered_still_fails(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $user = $this->user();
        $sent = null;

        try {
            OtpMailService::sendOtp($user, 'Super Admin', null, $user->mobile_number, $sent);
            $this->fail('Expected the OTP not to be delivered.');
        } catch (OtpDeliveryException) {
            $this->assertFalse($sent);
        }
    }

    public function test_no_mobile_means_no_whatsapp(): void
    {
        Http::fake(['*' => Http::response(['request_id' => 'r2'])]);

        $user = $this->user(null);
        OtpMailService::sendOtp($user, 'Super Admin', null, $user->mobile_number, $sent);

        $this->assertFalse($sent);
        $this->assertSame([], $this->whatsAppRequests());
    }

    public function test_other_panels_get_email_only(): void
    {
        Http::fake(['*' => Http::response(['request_id' => 'r3'])]);

        OtpMailService::sendOtp($this->user(), 'School Admin');

        $this->assertSame([], $this->whatsAppRequests());
    }
}
