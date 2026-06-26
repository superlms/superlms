<?php

namespace App\Http\Controllers\v1;

use App\Models\PaymentTransaction;
use App\Models\Student\StudentDetail;
use App\Services\PhonePeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends ApiController
{
    /**
     * POST /api/v1/fees/pay
     *
     * Start an online fee payment. Creates a PENDING transaction and returns
     * a PhonePe hosted-checkout URL for the app to open.
     *
     * Body: amount (₹, required), fee_type (academic|transport, default academic)
     */
    public function initiate(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        if ($err = $this->validateWith($request, [
            'amount'            => ['required', 'numeric', 'min:1'],
            'fee_type'          => ['nullable', 'in:academic,transport'],
            'months'            => ['nullable', 'array'],
            'months.*'          => ['string'],
            'transportation_id' => ['nullable', 'integer'],
        ])) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $feeType     = $request->input('fee_type', 'academic');
        $amountRupees = round((float) $request->input('amount'), 2);
        $amountPaise  = (int) round($amountRupees * 100);

        // For transport, capture which months the student intends to clear and
        // the route, so the recorded payment carries that context.
        $meta = [];
        if ($feeType === 'transport') {
            $meta['months'] = array_values(array_filter((array) $request->input('months', [])));
            $transportId = $request->input('transportation_id')
                ?: optional($student->transportations()->where('is_active', true)->first())->id;
            $meta['transportation_id'] = $transportId;
        }

        $merchantOrderId = 'TXN' . $user->organization_id . '-' . now()->timestamp . '-' . Str::upper(Str::random(6));

        $txn = PaymentTransaction::create([
            'organization_id'   => $user->organization_id,
            'student_detail_id' => $student->id,
            'user_id'           => $user->id,
            'fee_type'          => $feeType,
            'gateway'           => 'phonepe',
            'merchant_order_id' => $merchantOrderId,
            'amount'            => $amountRupees,
            'state'             => PaymentTransaction::STATE_PENDING,
            'meta'              => $meta,
        ]);

        // Route to the organization's own PhonePe merchant when configured,
        // so the money settles into that school's account (else platform).
        $phonepe = PhonePeService::fromOrganization($user->organization_id);

        try {
            $result = $phonepe->createPayment(
                merchantOrderId: $merchantOrderId,
                amountPaise: $amountPaise,
                redirectUrl: route('phonepe.return', ['merchantOrderId' => $merchantOrderId]),
                message: ucfirst($feeType) . ' fee payment',
            );
        } catch (\Throwable $e) {
            $txn->settle(PaymentTransaction::STATE_FAILED, ['error' => $e->getMessage()]);
            return $this->error('Could not start payment. Please try again.', 502);
        }

        $txn->phonepe_order_id = $result['orderId'] ?: null;
        $txn->meta = array_merge($txn->meta ?? [], ['merchant_scope' => $phonepe->scope]);
        $txn->save();

        return $this->success([
            'merchant_order_id' => $merchantOrderId,
            'redirect_url'      => $result['redirectUrl'],
            'state'             => $result['state'],
            'amount'            => $amountRupees,
        ], 'Payment initiated.');
    }

    /**
     * GET /api/v1/fees/pay/{merchantOrderId}/status
     *
     * Poll the latest state of a payment. On COMPLETED, the matching
     * fee_payments record is created exactly once.
     */
    public function status(string $merchantOrderId)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $txn = PaymentTransaction::where('merchant_order_id', $merchantOrderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$txn) {
            return $this->error('Transaction not found.', 404);
        }

        // Already final — return without re-hitting PhonePe.
        if ($txn->state !== PaymentTransaction::STATE_PENDING) {
            return $this->statusResponse($txn);
        }

        try {
            $result = PhonePeService::fromOrganization($txn->organization_id)->orderStatus($merchantOrderId);
            $txn = $txn->settle($result['state'], $result['raw']);
        } catch (\Throwable $e) {
            // Leave it PENDING; the app can poll again.
            return $this->statusResponse($txn);
        }

        return $this->statusResponse($txn);
    }

    private function statusResponse(PaymentTransaction $txn)
    {
        return $this->success([
            'merchant_order_id' => $txn->merchant_order_id,
            'state'             => $txn->state,
            'amount'            => (float) $txn->amount,
            'fee_type'          => $txn->fee_type,
            'paid'              => $txn->isCompleted(),
            'receipt_number'    => $txn->feePayment?->receipt_number,
        ], 'Payment status fetched.');
    }
}
