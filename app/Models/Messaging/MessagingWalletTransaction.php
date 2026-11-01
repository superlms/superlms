<?php

namespace App\Models\Messaging;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Money put in a school's messaging wallet (credit) or taken out (debit)
 * (App\Services\MessagingWallet). A school's own Add Funds is pending until
 * the Super Admin approves it; the Super Admin's own go in approved.
 */
class MessagingWalletTransaction extends Model
{
    protected $fillable = [
        'organization_id',
        'type',          // credit | debit
        'amount',
        'status',        // pending | approved | rejected
        'source',        // super_admin | school
        'mode',
        'reference',
        'note',
        'in_ledger',
        'txn_date',
        'created_by',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'in_ledger'  => 'boolean',
        'txn_date'   => 'date',
        'decided_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
