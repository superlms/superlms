<?php

namespace App\Services;

use App\Models\OrganizationPaymentSetting;
use App\Models\PayoutTransaction;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Outbound payouts from an organization's OWN account (salary, vendor, refund).
 *
 * GROUNDWORK ONLY. Payout records are created and tracked, but actual money
 * movement is intentionally gated until the organization completes PhonePe
 * Payouts onboarding (KYC + payout credentials). `disburse()` throws until then.
 *
 * When wiring the live API later:
 *   - authenticate with the org's payout credentials (see OrganizationPaymentSetting)
 *   - call PhonePe Payouts create + status endpoints
 *   - update PayoutTransaction.state / gateway_payout_id accordingly
 */
class PhonePePayoutService
{
    public function __construct(
        private readonly int $organizationId,
        private readonly ?OrganizationPaymentSetting $setting = null,
    ) {
    }

    public static function fromOrganization(int $orgId): self
    {
        return new self($orgId, OrganizationPaymentSetting::forOrg($orgId));
    }

    public function payoutEnabled(): bool
    {
        return (bool) $this->setting?->payoutReady();
    }

    /**
     * Record a payout request (PENDING). Does NOT move money.
     *
     * @param  array{beneficiary_name:string,amount:float|int,purpose?:string,
     *               beneficiary_account?:string,beneficiary_ifsc?:string,
     *               beneficiary_upi?:string,reference_type?:string,
     *               reference_id?:int,initiated_by?:int}  $data
     */
    public function recordPayout(array $data): PayoutTransaction
    {
        return PayoutTransaction::create([
            'organization_id'     => $this->organizationId,
            'gateway'             => 'phonepe',
            'beneficiary_name'    => $data['beneficiary_name'],
            'beneficiary_account' => $data['beneficiary_account'] ?? null,
            'beneficiary_ifsc'    => $data['beneficiary_ifsc'] ?? null,
            'beneficiary_upi'     => $data['beneficiary_upi'] ?? null,
            'purpose'             => $data['purpose'] ?? 'other',
            'reference_type'      => $data['reference_type'] ?? null,
            'reference_id'        => $data['reference_id'] ?? null,
            'amount'              => $data['amount'],
            'merchant_payout_id'  => 'PO' . $this->organizationId . '-' . now()->timestamp . '-' . Str::upper(Str::random(6)),
            'state'               => PayoutTransaction::STATE_PENDING,
            'initiated_by'        => $data['initiated_by'] ?? null,
        ]);
    }

    /**
     * Disburse a recorded payout. Gated until the org enables PhonePe Payouts.
     *
     * @throws RuntimeException
     */
    public function disburse(PayoutTransaction $txn): PayoutTransaction
    {
        if (!$this->payoutEnabled()) {
            throw new RuntimeException(
                'PhonePe Payouts is not enabled for this organization. Complete payout onboarding first.'
            );
        }

        // TODO: integrate the live PhonePe Payouts API here using the org's
        // payout credentials, then update state from the gateway response.
        throw new RuntimeException('PhonePe Payouts integration is not live yet.');
    }
}
