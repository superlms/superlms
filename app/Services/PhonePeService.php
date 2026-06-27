<?php

namespace App\Services;

use App\Models\OrganizationPaymentSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * PhonePe Standard Checkout (OAuth-based) integration.
 *
 * Flow:
 *   1. accessToken()    — fetch & cache an O-Bearer token (client credentials).
 *   2. createPayment()  — create an order, returns a hosted-checkout redirectUrl.
 *   3. orderStatus()    — poll the order state (COMPLETED | FAILED | PENDING).
 *   4. verifyWebhook()  — validate the server-to-server callback signature.
 *
 * Credentials live in config/services.php → 'phonepe' (env: PHONEPE_*).
 */
class PhonePeService
{
    /** Cache key for the OAuth access token. */
    private const TOKEN_CACHE_KEY = 'phonepe_access_token';

    public function __construct(
        private readonly ?string $clientId = null,
        private readonly ?string $clientSecret = null,
        private readonly ?string $clientVersion = null,
        private readonly string $env = 'sandbox',
        private readonly ?string $webhookUsername = null,
        private readonly ?string $webhookPassword = null,
        /** 'organization' when using the org's own merchant, else 'platform'. */
        public readonly string $scope = 'platform',
    ) {
    }

    public static function fromConfig(): self
    {
        $cfg = config('services.phonepe');

        return new self(
            clientId: $cfg['client_id'] ?? null,
            clientSecret: $cfg['client_secret'] ?? null,
            clientVersion: (string) ($cfg['client_version'] ?? '1'),
            env: $cfg['env'] ?? 'sandbox',
            webhookUsername: $cfg['webhook_username'] ?? null,
            webhookPassword: $cfg['webhook_password'] ?? null,
            scope: 'platform',
        );
    }

    /**
     * Build a service for a specific organization.
     *
     * Uses the org's OWN PhonePe merchant credentials when configured & active —
     * so a student's fee settles into that school's account. Falls back to the
     * platform (SuperLMS) credentials when the org hasn't onboarded yet.
     */
    public static function fromOrganization(int $orgId): self
    {
        $setting = OrganizationPaymentSetting::forOrg($orgId);

        if ($setting && $setting->collectionReady()) {
            return new self(
                clientId: $setting->client_id,
                clientSecret: $setting->client_secret,
                clientVersion: (string) ($setting->client_version ?: '1'),
                env: $setting->env ?: 'sandbox',
                webhookUsername: $setting->webhook_username,
                webhookPassword: $setting->webhook_password,
                scope: 'organization',
            );
        }

        return self::fromConfig();
    }

    public function isProduction(): bool
    {
        return $this->env === 'production';
    }

    /** Base host for the OAuth token endpoint. */
    private function authBase(): string
    {
        return $this->isProduction()
            ? 'https://api.phonepe.com/apis/identity-manager'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    /** Base host for the payment / order-status endpoints. */
    private function pgBase(): string
    {
        return $this->isProduction()
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    /**
     * Returns a valid O-Bearer access token, cached until shortly before expiry.
     */
    public function accessToken(): string
    {
        // Token is per-merchant — key on the client id so per-org and platform
        // tokens never collide.
        $cacheKey = self::TOKEN_CACHE_KEY . ':' . md5((string) $this->clientId);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        if (!$this->clientId || !$this->clientSecret) {
            throw new RuntimeException('PhonePe credentials are not configured.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($this->authBase() . '/v1/oauth/token', [
                'client_id'      => $this->clientId,
                'client_version' => $this->clientVersion,
                'client_secret'  => $this->clientSecret,
                'grant_type'     => 'client_credentials',
            ]);

        if ($response->failed()) {
            Log::error('PhonePe token fetch failed', ['body' => $response->body()]);
            throw new RuntimeException('Unable to authenticate with PhonePe.');
        }

        $token     = $response->json('access_token');
        $expiresAt = (int) $response->json('expires_at'); // epoch seconds

        if (!$token) {
            throw new RuntimeException('PhonePe did not return an access token.');
        }

        // Cache with a 60s safety margin; fall back to ~50 min if no expiry given.
        $ttl = $expiresAt > 0
            ? max(60, $expiresAt - now()->timestamp - 60)
            : 3000;

        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * Create a checkout order.
     *
     * @param  string  $merchantOrderId  Our unique order id.
     * @param  int     $amountPaise      Amount in paise (₹1 = 100).
     * @param  string  $redirectUrl      Where PhonePe sends the user back after payment.
     * @param  string  $message          Short description shown on the checkout page.
     * @return array{orderId:string,state:string,redirectUrl:string,raw:array}
     */
    public function createPayment(
        string $merchantOrderId,
        int $amountPaise,
        string $redirectUrl,
        string $message = 'Fee payment',
    ): array {
        $response = Http::withToken($this->accessToken(), 'O-Bearer')
            ->acceptJson()
            ->post($this->pgBase() . '/checkout/v2/pay', [
                'merchantOrderId' => $merchantOrderId,
                'amount'          => $amountPaise,
                'expireAfter'     => 1200, // seconds (20 min)
                'paymentFlow'     => [
                    'type'         => 'PG_CHECKOUT',
                    'message'      => $message,
                    'merchantUrls' => ['redirectUrl' => $redirectUrl],
                ],
            ]);

        if ($response->failed()) {
            Log::error('PhonePe createPayment failed', [
                'order' => $merchantOrderId,
                'body'  => $response->body(),
            ]);
            throw new RuntimeException('Unable to start PhonePe payment.');
        }

        $data = $response->json();

        return [
            'orderId'     => $data['orderId'] ?? '',
            'state'       => $data['state'] ?? 'PENDING',
            'redirectUrl' => $data['redirectUrl'] ?? '',
            'raw'         => $data,
        ];
    }

    /**
     * Fetch the current state of an order.
     *
     * @return array{state:string,amount:int|null,raw:array}
     */
    public function orderStatus(string $merchantOrderId): array
    {
        $response = Http::withToken($this->accessToken(), 'O-Bearer')
            ->acceptJson()
            ->get($this->pgBase() . "/checkout/v2/order/{$merchantOrderId}/status");

        if ($response->failed()) {
            Log::error('PhonePe orderStatus failed', [
                'order' => $merchantOrderId,
                'body'  => $response->body(),
            ]);
            throw new RuntimeException('Unable to fetch PhonePe order status.');
        }

        $data = $response->json();

        return [
            'state'  => $data['state'] ?? 'PENDING',
            'amount' => $data['amount'] ?? null,
            'raw'    => $data,
        ];
    }

    /**
     * Validate a webhook callback.
     *
     * PhonePe sends an Authorization header equal to
     * SHA256(webhook_username:webhook_password). Compare in constant time.
     */
    public function verifyWebhook(?string $authHeader): bool
    {
        $username = $this->webhookUsername;
        $password = $this->webhookPassword;

        if (!$username || !$password || !$authHeader) {
            return false;
        }

        $expected = hash('sha256', $username . ':' . $password);

        // Some payloads prefix the scheme (e.g. "SHA256 <hash>"); take the last token.
        $provided = trim($authHeader);
        if (str_contains($provided, ' ')) {
            $provided = trim((string) substr($provided, (int) strrpos($provided, ' ')));
        }

        return hash_equals($expected, strtolower($provided));
    }
}
