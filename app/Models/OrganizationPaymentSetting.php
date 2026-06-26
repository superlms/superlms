<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-organization gateway credentials. Secrets are encrypted at rest.
 *
 * Collection (Standard Checkout): client_id / client_secret / webhook_*.
 * Payout (groundwork): payout_client_id / payout_client_secret.
 */
class OrganizationPaymentSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'gateway',
        'client_id',
        'client_secret',
        'client_version',
        'env',
        'webhook_username',
        'webhook_password',
        'is_active',
        'payout_client_id',
        'payout_client_secret',
        'payout_account_ref',
        'payout_is_active',
    ];

    protected $casts = [
        'client_secret'        => 'encrypted',
        'webhook_password'     => 'encrypted',
        'payout_client_secret' => 'encrypted',
        'is_active'            => 'boolean',
        'payout_is_active'     => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function forOrg(int $orgId): ?self
    {
        return self::where('organization_id', $orgId)->first();
    }

    /** Collection (student fee) is ready to route to this org's own account. */
    public function collectionReady(): bool
    {
        return $this->is_active && !empty($this->client_id) && !empty($this->client_secret);
    }

    /** Payout (org disbursing money out) is enabled. */
    public function payoutReady(): bool
    {
        return $this->payout_is_active && !empty($this->payout_client_id) && !empty($this->payout_client_secret);
    }
}
