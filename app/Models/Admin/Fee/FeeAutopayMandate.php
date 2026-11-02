<?php

namespace App\Models\Admin\Fee;

use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;

/**
 * A parent's PhonePe UPI AutoPay mandate for a student's academic
 * installments (App\Services\FeeAutopay). One live mandate per student.
 */
class FeeAutopayMandate extends Model
{
    public const STATUSES = [
        'pending'    => 'Waiting for approval',
        'active'     => 'Active',
        'paused'     => 'Paused',
        'cancelling' => 'Cancelling',
        'cancelled'  => 'Cancelled',
        'revoked'    => 'Stopped by the parent',
        'expired'    => 'Expired',
        'failed'     => 'Not set up',
    ];

    /** Mandates still in force, or on their way to being. */
    public const LIVE = ['pending', 'active', 'paused'];

    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'user_id',
        'merchant_subscription_id',
        'setup_order_id',
        'phonepe_subscription_id',
        'max_amount',
        'status',
        'status_reason',
        'activated_at',
        'ended_at',
        'expires_at',
        'synced_at',
        'meta',
    ];

    protected $casts = [
        'max_amount'   => 'decimal:2',
        'activated_at' => 'datetime',
        'ended_at'     => 'datetime',
        'expires_at'   => 'datetime',
        'synced_at'    => 'datetime',
        'meta'         => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(StudentDetail::class, 'student_detail_id');
    }

    public function debits()
    {
        return $this->hasMany(FeeAutopayDebit::class, 'mandate_id');
    }

    public function isLive(): bool
    {
        return in_array($this->status, self::LIVE, true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
