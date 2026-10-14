<?php

namespace App\Http\Controllers;

use App\Services\SchoolWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Meta's WhatsApp webhook (public) — /api/whatsapp/webhook, the callback URL
 * set in the Meta app's WhatsApp → Configuration with the "messages" field:
 *   - verify(): the GET Meta sends once when the callback is saved; answers
 *     its challenge when the token is config services.whatsapp.webhook_verify_token.
 *   - handle(): the POSTs, signed with the app secret (X-Hub-Signature-256);
 *     each status moves a message on (SchoolWhatsApp::applyStatus) — one that
 *     failed is not charged to its school.
 */
class WhatsAppWebhookController extends Controller
{
    /** GET /api/whatsapp/webhook */
    public function verify(Request $request)
    {
        $token = (string) config('services.whatsapp.webhook_verify_token');

        // PHP turns the dots in hub.mode / hub.verify_token / hub.challenge into _.
        if ($token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /** POST /api/whatsapp/webhook */
    public function handle(Request $request)
    {
        $secret = (string) config('services.whatsapp.app_secret');
        $sent   = (string) $request->header('X-Hub-Signature-256');

        if ($secret === '' || !hash_equals('sha256=' . hash_hmac('sha256', $request->getContent(), $secret), $sent)) {
            Log::warning('WhatsApp webhook: bad or missing signature', ['ip' => $request->ip(), 'configured' => $secret !== '']);
            return response()->json(['success' => false], 401);
        }

        $changed = 0;
        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                foreach ((array) ($change['value']['statuses'] ?? []) as $status) {
                    try {
                        $changed += (int) SchoolWhatsApp::applyStatus(
                            (string) ($status['id'] ?? ''),
                            (string) ($status['status'] ?? ''),
                            self::errorOf($status),
                        );
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        // 200 once it is Meta's, so it stops retrying.
        return response()->json(['success' => true, 'updated' => $changed]);
    }

    /** "131026 Message undeliverable — …" from a failed status's errors. */
    private static function errorOf(array $status): ?string
    {
        $e = $status['errors'][0] ?? null;
        if (!is_array($e)) {
            return null;
        }

        $text = trim(($e['code'] ?? '') . ' ' . ($e['title'] ?? $e['message'] ?? ''));
        $more = $e['error_data']['details'] ?? null;

        return $more ? $text . ' — ' . $more : ($text !== '' ? $text : null);
    }
}
