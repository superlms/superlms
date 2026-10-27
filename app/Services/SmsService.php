<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends one SMS through Bhash SMS's HTTP API:
 *
 *   GET {url}?user=…&pass=…&sender=…&phone=9876543210&text=…&priority=ndnd&stype=normal
 *       [&entityid=…&templateid=…]
 *
 * The text is a DLT template (config services.bhash_sms.templates) with its
 * {#var#} filled in order. Does nothing until user, pass and sender are set.
 * Never throws, and never logs the request: its query has the account's
 * password and the text has the person's.
 */
class SmsService
{
    /** DLT lets a variable be 30 characters at most. */
    public const VAR_MAX = 30;

    public static function enabled(): bool
    {
        return (bool) config('services.bhash_sms.enabled')
            && config('services.bhash_sms.user')
            && config('services.bhash_sms.pass')
            && config('services.bhash_sms.sender');
    }

    /** An Indian mobile as Bhash wants it — the ten digits — or null when it is not one. */
    public static function normalizePhone(?string $phone): ?string
    {
        $wa = WhatsAppService::normalizePhone($phone);

        return $wa === null ? null : substr($wa, 2);
    }

    /**
     * The template's text with each {#var#} given its value, in order. A
     * value is put in plain letters (an accent or a curly quote would make the
     * whole SMS Unicode, at 70 characters a part), cut to VAR_MAX, and "-"
     * when nothing is left. A template with more {#var#} than values keeps
     * the rest as "-".
     */
    public static function fill(string $text, array $vars): string
    {
        $vars = array_values($vars);
        $i    = 0;

        return preg_replace_callback('/\{#var#\}/i', function () use (&$i, $vars) {
            $value = self::clean($vars[$i++] ?? null);

            return $value === '' ? '-' : $value;
        }, $text);
    }

    private static function clean($value): string
    {
        $value = Str::ascii((string) $value);
        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        return mb_substr($value, 0, self::VAR_MAX);
    }

    /** How many parts a plain-letters SMS goes as: one up to 160, then 153 each. */
    public static function parts(string $text): int
    {
        $len = strlen($text);

        return $len <= 160 ? 1 : (int) ceil($len / 153);
    }

    /**
     * Send one template. ['ok' => bool, 'id' => Bhash's message id or null,
     * 'error' => why it did not go or null, 'parts' => int].
     */
    public static function send(string $phone, string $templateKey, array $vars): array
    {
        $fail = fn (string $why, int $parts = 0) => ['ok' => false, 'id' => null, 'error' => $why, 'parts' => $parts];

        if (!self::enabled()) {
            return $fail('SMS is not set up.');
        }
        $to = self::normalizePhone($phone);
        if ($to === null) {
            return $fail('No usable mobile number.');
        }
        $template = config("services.bhash_sms.templates.{$templateKey}");
        if (!is_array($template) || empty($template['text'])) {
            return $fail("No SMS template \"{$templateKey}\".");
        }

        $text  = self::fill((string) $template['text'], $vars);
        $parts = self::parts($text);

        $query = [
            'user'     => (string) config('services.bhash_sms.user'),
            'pass'     => (string) config('services.bhash_sms.pass'),
            'sender'   => (string) config('services.bhash_sms.sender'),
            'phone'    => $to,
            'text'     => $text,
            'priority' => (string) config('services.bhash_sms.priority', 'ndnd'),
            'stype'    => (string) config('services.bhash_sms.stype', 'normal'),
        ];
        if (($entity = config('services.bhash_sms.entity_id')) && ($name = config('services.bhash_sms.entity_param'))) {
            $query[$name] = (string) $entity;
        }
        if (!empty($template['id']) && ($name = config('services.bhash_sms.template_param'))) {
            $query[$name] = (string) $template['id'];
        }

        try {
            $res = Http::connectTimeout(3)->timeout(8)
                ->get((string) config('services.bhash_sms.url'), $query);
        } catch (\Throwable $e) {
            $why = self::scrub($e->getMessage());
            Log::warning('SMS: Bhash could not be reached', ['template' => $templateKey, 'error' => $why]);

            return $fail($why, $parts);
        }

        $body = trim((string) $res->body());

        if (!$res->successful()) {
            $why = 'HTTP ' . $res->status() . ($body !== '' ? ': ' . self::scrub($body) : '');
            Log::warning('SMS: Bhash refused it', ['template' => $templateKey, 'error' => $why]);

            return $fail($why, $parts);
        }

        // Bhash answers in plain text: "S.123456" (the message id) when it
        // took the message, otherwise what is wrong ("Invalid Login",
        // "Insufficient credits", "Template not matched" …).
        if (preg_match('/\bS\.\d+/', $body, $m)) {
            return ['ok' => true, 'id' => $m[0], 'error' => null, 'parts' => $parts];
        }
        if ($body === '' || preg_match('/error|invalid|insufficient|fail|denied|block|mismatch|not\s+(?:match|approved|registered|valid|allowed|found)|wrong|expired/i', $body)) {
            $why = $body === '' ? 'Bhash sent back nothing.' : self::scrub($body);
            Log::warning('SMS: Bhash did not take it', ['template' => $templateKey, 'error' => $why]);

            return $fail($why, $parts);
        }

        return ['ok' => true, 'id' => mb_substr($body, 0, 120), 'error' => null, 'parts' => $parts];
    }

    /**
     * An error as it may be kept: without a URL's query (Guzzle puts the
     * whole URL — the account's password and the text — in its messages),
     * and short.
     */
    private static function scrub(string $message): string
    {
        $message = (string) preg_replace('/\?[^\s"\']*/', '?…', $message);
        $pass    = (string) config('services.bhash_sms.pass');
        if ($pass !== '') {
            $message = str_replace([$pass, rawurlencode($pass), urlencode($pass)], '…', $message);
        }

        return mb_substr($message, 0, 500);
    }
}
