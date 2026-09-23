<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp template messages through Meta's Cloud API.
 *
 * Does nothing until services.whatsapp.token and phone_number_id are set, so
 * the code can ship before the account is ready. Never throws: a message that
 * could not go is logged, and whatever called it carries on.
 */
class WhatsAppService
{
    public static function enabled(): bool
    {
        return (bool) config('services.whatsapp.enabled')
            && config('services.whatsapp.token')
            && config('services.whatsapp.phone_number_id');
    }

    /**
     * An Indian mobile as WhatsApp wants it — 91 then ten digits — or null
     * when it is not one.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        }

        return preg_match('/^91[6-9]\d{9}$/', $digits) ? $digits : null;
    }

    /**
     * One template message.
     *
     * @param  array<int, string>  $body       the body's {{1}}, {{2}} … in order
     * @param  string|array<int, string>|null  $urlSuffix  the {{1}} of the
     *         first URL button, or of each dynamic URL button by its index
     *         ([0 => 'token', 1 => 'wa'])
     */
    public static function sendTemplate(string $to, string $template, array $body = [], string|array|null $urlSuffix = null): bool
    {
        if (!self::enabled()) {
            Log::info('WhatsApp: not configured, skipped', ['template' => $template]);
            return false;
        }

        $phone = self::normalizePhone($to);
        if (!$phone) {
            Log::warning('WhatsApp: no usable mobile number, skipped', ['template' => $template]);
            return false;
        }

        $components = [];
        if ($body) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], array_values($body)),
            ];
        }
        $buttons = is_array($urlSuffix) ? $urlSuffix : ($urlSuffix !== null ? [0 => $urlSuffix] : []);
        foreach ($buttons as $index => $suffix) {
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => (string) $index,
                'parameters' => [['type' => 'text', 'text' => (string) $suffix]],
            ];
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('services.whatsapp.api_version', 'v21.0'),
            config('services.whatsapp.phone_number_id'),
        );

        try {
            $res = Http::withToken((string) config('services.whatsapp.token'))
                ->connectTimeout(3)
                ->timeout(8)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $template,
                        'language'   => ['code' => config('services.whatsapp.language', 'en')],
                        'components' => $components,
                    ],
                ]);

            if ($res->failed()) {
                Log::error('WhatsApp: send failed', [
                    'template' => $template,
                    'status'   => $res->status(),
                    'error'    => $res->json('error.message') ?? $res->body(),
                ]);
                return false;
            }

            Log::info('WhatsApp: sent', ['template' => $template, 'id' => $res->json('messages.0.id')]);
            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp: send failed', ['template' => $template, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
