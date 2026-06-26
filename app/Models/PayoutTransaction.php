<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An outbound payment from an organization's own account (salary, vendor, refund).
 * Groundwork: rows are recorded; live money-out is gated on PhonePe Payouts onboarding.
 */
class PayoutTransaction extends Model
{
    public const STATE_PENDING    = 'PENDING';
    public const STATE_PROCESSING = 'PROCESSING';
    public const STATE_SUCCESS    = 'SUCCESS';
    public const STATE_FAILED     = 'FAILED';

    protected $fillable = [
        'organization_id',
        'gateway',
        'beneficiary_name',
        'beneficiary_account',
        'beneficiary_ifsc',
        'beneficiary_upi',
        'purpose',
        'reference_type',
        'reference_id',
        'amount',
        'merchant_payout_id',
        'gateway_payout_id',
        'state',
        'initiated_by',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta'   => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
