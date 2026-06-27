<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PhonePeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public (un-authenticated) PhonePe endpoints:
 *   - handleWebhook(): server-to-server payment callback.
 *   - paymentReturn(): the browser redirect target after checkout.
 *
 * Both are safe to hit repeatedly — settlement is idempotent.
 */
class PhonePeController extends Controller
{
    /**
     * POST /api/v1/phonepe/webhook
     *
     * Validates the Authorization header, then settles the transaction.
     * Always returns 200 once authenticated so PhonePe stops retrying.
     */
    public function handleWebhook(Request $request)
    {
        $payload         = $request->input('payload', []);
        $merchantOrderId = $payload['merchantOrderId'] ?? null;
        $state           = $payload['state'] ?? 'PENDING';

        if (!$merchantOrderId) {
            return response()->json(['success' => false, 'message' => 'Missing merchantOrderId'], 422);
        }

        // Find the order first so we can verify against the merchant that
        // actually created it (the org's own account, or the platform).
        $txn     = PaymentTransaction::where('merchant_order_id', $merchantOrderId)->first();
        $service = $this->serviceForTxn($txn);

        if (!$service->verifyWebhook($request->header('Authorization'))) {
            Log::warning('PhonePe webhook: invalid signature', ['ip' => $request->ip(), 'order' => $merchantOrderId]);
            return response()->json(['success' => false], 401);
        }

        if ($txn) {
            $txn->settle($this->normalizeState($state), $payload);
        } else {
            Log::warning('PhonePe webhook: unknown order', ['merchantOrderId' => $merchantOrderId]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /payment/return/{merchantOrderId}
     *
     * Where PhonePe sends the user's browser back. We confirm the status with
     * PhonePe (source of truth), then show a small page that bounces back into
     * the app. The app also polls /fees/pay/{id}/status, so this is best-effort.
     */
    public function paymentReturn(string $merchantOrderId)
    {
        $state = 'PENDING';

        $txn = PaymentTransaction::where('merchant_order_id', $merchantOrderId)->first();

        if ($txn) {
            try {
                $result = $this->serviceForTxn($txn)->orderStatus($merchantOrderId);
                $txn    = $txn->settle($this->normalizeState($result['state']), $result['raw']);
                $state  = $txn->state;
            } catch (\Throwable $e) {
                $state = $txn->state;
            }
        }

        $deepLink = 'superlms://payment/return?orderId=' . urlencode($merchantOrderId) . '&state=' . urlencode($state);

        $label = match ($state) {
            PaymentTransaction::STATE_COMPLETED => 'Payment successful',
            PaymentTransaction::STATE_FAILED    => 'Payment failed',
            default                             => 'Payment processing',
        };

        return response(view('payments.return', [
            'state'    => $state,
            'label'    => $label,
            'deepLink' => $deepLink,
        ]));
    }

    /**
     * Resolve the PhonePe merchant that created a transaction. Uses the org's
     * own account when the order was created under it, else the platform.
     */
    private function serviceForTxn(?PaymentTransaction $txn): PhonePeService
    {
        $scope = $txn?->meta['merchant_scope'] ?? null;

        return ($txn && $scope === 'organization')
            ? PhonePeService::fromOrganization($txn->organization_id)
            : PhonePeService::fromConfig();
    }

    private function normalizeState(string $state): string
    {
        return match (strtoupper($state)) {
            'COMPLETED' => PaymentTransaction::STATE_COMPLETED,
            'FAILED'    => PaymentTransaction::STATE_FAILED,
            default     => PaymentTransaction::STATE_PENDING,
        };
    }
}
