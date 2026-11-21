<?php

namespace App\Models\Messaging;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Money a school paid SuperLMS for its messages (App\Services\MessagePacks):
 * with a pack given "paid now", on approving a pack the school bought, or by
 * Record Payment for what it owes. A credit in the Super Admin's Ledger.
 */
class MessagingPayment extends Model
{
    protected $table = 'messaging_payments';

    protected $fillable = [
        'organization_id',
        'pack_id',
        'amount',
        'mode',
        'reference',
        'paid_on',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function pack()
    {
        return $this->belongsTo(MessagingPack::class, 'pack_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
