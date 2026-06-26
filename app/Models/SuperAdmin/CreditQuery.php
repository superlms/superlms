<?php

namespace App\Models\SuperAdmin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreditQuery extends Model
{
    protected $fillable = [
        'organization_id',
        'amount',
        'start_date',
        'end_date',
        'heading',
        'reason',
        'status',
        'admin_remark',
        'penalties_per_day',
        'approved_by',
        'approved_at',
        'collected_at',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'approved_at'      => 'datetime',
        'collected_at'     => 'datetime',
        'amount'           => 'decimal:2',
        'penalties_per_day'=> 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeApproved($query)    { return $query->where('status', 'approved'); }
    public function scopePending($query)     { return $query->where('status', 'pending'); }
    public function scopeProcessing($query)  { return $query->where('status', 'processing'); }
    public function scopeDenied($query)      { return $query->where('status', 'denied'); }
    public function scopeActiveCredit($query){ return $query->where('status', 'approved')->where('end_date', '>=', now()); }
    public function scopeForOrg($query, $orgId){ return $query->where('organization_id', $orgId); }
}
