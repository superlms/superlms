<?php

namespace App\Models\Messaging;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Messages put in a school's account on one channel (give) or taken back
 * (take), with what they cost the school (App\Services\MessagePacks). The
 * Super Admin's go in approved; a school's own Buy Messages is pending until
 * the Super Admin approves it.
 */
class MessagingPack extends Model
{
    protected $table = 'messaging_packs';

    protected $fillable = [
        'organization_id',
        'channel',       // whatsapp | sms | email
        'type',          // give | take
        'messages',
        'rate',
        'amount',
        'status',        // pending | approved | rejected
        'source',        // super_admin | school | carried
        'mode',
        'reference',
        'txn_date',
        'note',
        'created_by',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected $casts = [
        'messages'   => 'integer',
        'rate'       => 'decimal:2',
        'amount'     => 'decimal:2',
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

    public function payments()
    {
        return $this->hasMany(MessagingPayment::class, 'pack_id');
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
