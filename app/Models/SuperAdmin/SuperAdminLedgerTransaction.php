<?php

namespace App\Models\SuperAdmin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A credit or an expense the Super Admin put in the Ledger by hand
 * (App\Services\SuperAdminLedgerService). Fees received and salaries paid are
 * not kept here — they are read from their own tables.
 */
class SuperAdminLedgerTransaction extends Model
{
    protected $fillable = [
        'type',        // credit | expense
        'amount',
        'txn_date',
        'party',       // From
        'party_to',    // To (expense) / Collected by (credit)
        'mode',
        'reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'txn_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
