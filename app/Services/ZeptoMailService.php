<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generic Zoho ZeptoMail service.
 *
 * Usage:
 *   // Single recipient
 *   ZeptoMailService::sendTemplate('template_key_here', 'user@example.com', 'User Name', [
 *       'otp' => '123456',
 *       'userName' => 'John',
 *   ]);
 *
 *   // Multiple recipients
 *   ZeptoMailService::sendTemplate('template_key_here', [
 *       ['email' => 'a@example.com', 'name' => 'Alice'],
 *       ['email' => 'b@example.com', 'name' => 'Bob'],
 *   ], null, ['greeting' => 'Hello']);
 *
 *   // Custom from address
 *   ZeptoMailService::sendTemplate('key', 'to@test.com', 'To', ['field' => 'val'], 'custom@superlms.site', 'Custom Name');
 */
class ZeptoMailService
{
    /**
     * Send an email using a ZeptoMail template.
     *
     * @param string       $templateKey   ZeptoMail template key
     * @param string|array $to            Email string OR array of ['email' => '', 'name' => '']
     * @param string|null  $toName        Recipient name (used when $to is a string)
     * @param array        $mergeFields   Merge/template variables e.g. ['otp' => '123456']
     * @param string|null  $fromEmail     Override from email (default from config)
     * @param string|null  $fromName      Override from name (default from config)
     */
    public static function sendTemplate(
        string $templateKey,
        string|array $to,
        ?string $toName = null,
        array $mergeFields = [],
        ?string $fromEmail = null,
        ?string $fromName = null,
    ): array {
        $apiToken = config('services.zeptomail.api_token');
        $bounceAddress = config('services.zeptomail.bounce_address');

        if (!$apiToken) {
            throw new \RuntimeException('ZeptoMail API token not configured. Set ZEPTOMAIL_API_TOKEN in .env');
        }

        if (!$templateKey) {
            throw new \RuntimeException('ZeptoMail template key is required.');
        }

        // Build recipients
        $recipients = self::buildRecipients($to, $toName);

        $payload = [
            'template_key' => $templateKey,
            'from' => [
                'address' => $fromEmail ?? config('services.zeptomail.from_email', 'noreply@superlms.in'),
                'name' => $fromName ?? config('services.zeptomail.from_name', 'SuperLMS'),
            ],
            'to' => $recipients,
            'merge_info' => $mergeFields,
        ];

        if (!empty($bounceAddress)) {
            $payload['bounce_address'] = $bounceAddress;
        }

        // Hard cap the HTTP call: connect ≤3s, full request ≤5s. Without
        // this Laravel falls back to PHP's max_execution_time (≈60s) when
        // the upstream is unreachable — which then trips nginx's 60s
        // proxy_read_timeout and the user sees a 504 Gateway Timeout on
        // forms that send a welcome email (student/teacher create, OTP).
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-enczapikey ' . $apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->connectTimeout(3)
            ->timeout(5)
            ->post(config('services.zeptomail.api_url') . '/email/template', $payload);

        if ($response->failed()) {
            $error = $response->json('message') ?? $response->body();
            Log::error('ZeptoMail template send failed', [
                'template_key' => $templateKey,
                'to' => is_string($to) ? $to : collect($to)->pluck('email')->toArray(),
                'status' => $response->status(),
                'error' => $error,
                'response_body' => $response->body(),
            ]);
            throw new \RuntimeException('ZeptoMail send failed: ' . $error);
        }

        Log::info('ZeptoMail template sent', [
            'template_key' => $templateKey,
            'to' => is_string($to) ? $to : collect($to)->pluck('email')->toArray(),
            'request_id' => $response->json('request_id'),
        ]);

        return $response->json();
    }

    /**
     * Send a raw HTML email (no template).
     */
    public static function sendRaw(
        string $subject,
        string $htmlBody,
        string|array $to,
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
    ): array {
        $apiToken = config('services.zeptomail.api_token');
        $bounceAddress = config('services.zeptomail.bounce_address');

        if (!$apiToken) {
            throw new \RuntimeException('ZeptoMail API token not configured.');
        }

        $recipients = self::buildRecipients($to, $toName);

        $payload = [
            'from' => [
                'address' => $fromEmail ?? config('services.zeptomail.from_email', 'noreply@superlms.in'),
                'name' => $fromName ?? config('services.zeptomail.from_name', 'SuperLMS'),
            ],
            'to' => $recipients,
            'subject' => $subject,
            'htmlbody' => $htmlBody,
        ];

        if (!empty($bounceAddress)) {
            $payload['bounce_address'] = $bounceAddress;
        }

        // Same tight timeout as sendTemplate() — never block a web request
        // for more than 8s on an upstream Zoho hiccup.
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-enczapikey ' . $apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->connectTimeout(3)
            ->timeout(5)
            ->post(config('services.zeptomail.api_url') . '/email', $payload);

        if ($response->failed()) {
            $error = $response->json('message') ?? $response->body();
            Log::error('ZeptoMail raw send failed', [
                'subject' => $subject,
                'to' => is_string($to) ? $to : collect($to)->pluck('email')->toArray(),
                'status' => $response->status(),
                'error' => $error,
                'response_body' => $response->body(),
            ]);
            throw new \RuntimeException('ZeptoMail send failed: ' . $error);
        }

        return $response->json();
    }

    /**
     * Normalize recipients into ZeptoMail format.
     */
    private static function buildRecipients(string|array $to, ?string $toName): array
    {
        if (is_string($to)) {
            return [
                [
                    'email_address' => [
                        'address' => $to,
                        'name' => $toName ?? '',
                    ],
                ],
            ];
        }

        return collect($to)->map(fn(array $r) => [
            'email_address' => [
                'address' => $r['email'],
                'name' => $r['name'] ?? '',
            ],
        ])->values()->toArray();
    }
}
