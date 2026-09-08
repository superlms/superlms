<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LedgerTransaction extends Model
{
    protected $fillable = [
        'organization_id',
        'type',        // 'credit' | 'expense'
        'amount',
        'txn_date',
        'party',
        'mode',
        'party_to',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'txn_date' => 'date',
    ];

    /** A manual entry stays editable for this many days after it was added. */
    public const EDIT_WINDOW_DAYS = 7;

    /**
     * Manual entries can be corrected for a week after they were recorded;
     * after that the ledger is closed and the row is read-only.
     */
    public function isEditable(): bool
    {
        if (!$this->created_at) {
            return true;
        }

        return $this->created_at->copy()->addDays(self::EDIT_WINDOW_DAYS)->isFuture();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }
}
