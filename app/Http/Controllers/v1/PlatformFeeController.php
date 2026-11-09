<?php

namespace App\Http\Controllers\v1;

use App\Models\PaymentTransaction;
use App\Models\Student\StudentDetail;
use App\Services\PhonePeService;
use App\Support\FeeAccess;
use Illuminate\Support\Str;

/**
 * The app's payment screen for a student whose school has them pay the
 * Per Student platform fee themselves (FeeAccess). The money goes to the
 * platform's own PhonePe merchant (not the school's): it is SuperLMS's fee.
 * Once PhonePe says it came, PaymentTransaction::settle books it on the
 * student's Super Admin fee, and the app opens as usual.
 *
 *   GET  /v1/platform-fee                         the fee, what is due, paid or not
 *   POST /v1/platform-fee/pay                     start Pay Now → PhonePe's checkout
 *   GET  /v1/platform-fee/pay/{merchantOrderId}   how that payment went
 */
class PlatformFeeController extends ApiController
{
    public function show()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('user')) return $err;

        $detail = $this->student($user);
        if (!$detail) {
            return $this->error('Student profile not found.', 404);
        }

        return $this->success(FeeAccess::status($detail), 'Platform fee fetched.');
    }

    public function pay()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('user')) return $err;

        $detail = $this->student($user);
        if (!$detail) {
            return $this->error('Student profile not found.', 404);
        }

        $structure = FeeAccess::studentPaysStructure($detail->organization_id);
        if (!$structure) {
            return $this->error('There is no platform fee for you to pay.', 422);
        }

        $due = round(max(0, (float) $structure->amount - FeeAccess::collected($detail, $structure)), 2);
        if ($due < 1) {
            return $this->error('Your platform fee is already paid.', 422);
        }

        $merchantOrderId = 'PLF' . $detail->organization_id . '-' . now()->timestamp . '-' . Str::upper(Str::random(6));

        $txn = PaymentTransaction::create([
            'organization_id'   => $detail->organization_id,
            'student_detail_id' => $detail->id,
            'user_id'           => $user->id,
            'fee_type'          => 'platform',
            'gateway'           => 'phonepe',
            'merchant_order_id' => $merchantOrderId,
            'amount'            => $due,
            'state'             => PaymentTransaction::STATE_PENDING,
            'meta'              => [
                'merchant_scope' => 'platform',
                'structure_id'   => $structure->id,
                'academic_year'  => $structure->academic_year,
            ],
        ]);

        try {
            $result = PhonePeService::fromConfig()->createPayment(
                merchantOrderId: $merchantOrderId,
                amountPaise: (int) round($due * 100),
                redirectUrl: route('phonepe.return', ['merchantOrderId' => $merchantOrderId]),
                message: ($structure->fee_label ?: 'Platform fee') . ' ' . $structure->academic_year,
            );
        } catch (\Throwable $e) {
            report($e);
            $txn->settle(PaymentTransaction::STATE_FAILED, ['error' => $e->getMessage()]);

            return $this->error('Could not start the payment. Please try again.', 502);
        }

        $txn->phonepe_order_id = $result['orderId'] ?: null;
        $txn->save();

        return $this->success([
            'merchant_order_id' => $merchantOrderId,
            'redirect_url'      => $result['redirectUrl'],
            'state'             => $result['state'],
            'amount'            => $due,
        ], 'Payment initiated.');
    }

    public function status(string $merchantOrderId)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('user')) return $err;

        $txn = PaymentTransaction::where('merchant_order_id', $merchantOrderId)
            ->where('user_id', $user->id)
            ->where('fee_type', 'platform')
            ->first();
        if (!$txn) {
            return $this->error('Payment not found.', 404);
        }

        if ($txn->state === PaymentTransaction::STATE_PENDING) {
            try {
                $result = PhonePeService::fromConfig()->orderStatus($merchantOrderId);
                $state  = strtoupper((string) $result['state']);
                $txn    = $txn->settle(in_array($state, ['COMPLETED', 'FAILED'], true) ? $state : PaymentTransaction::STATE_PENDING, $result['raw']);
            } catch (\Throwable $e) {
                // Still pending; the app asks again.
            }
        }

        $detail = $this->student($user);

        return $this->success([
            'merchant_order_id' => $txn->merchant_order_id,
            'state'             => $txn->state,
            'amount'            => (float) $txn->amount,
            'paid'              => $txn->isCompleted(),
            'fee'               => $detail ? FeeAccess::status($detail) : null,
        ], 'Payment status fetched.');
    }

    private function student($user): ?StudentDetail
    {
        return StudentDetail::where('user_id', $user->id)->first();
    }
}
