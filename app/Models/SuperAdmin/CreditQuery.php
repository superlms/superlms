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

    /**
     * What this credit actually costs to settle, as of today.
     *
     * The principal is fixed; a penalty accrues per day for every day past the
     * end date until the money is collected. Once collected_at is set nothing
     * further accrues, so a settled credit stops growing.
     */
    public function repayment(): array
    {
        $principal  = (float) $this->amount;
        $perDay     = (float) ($this->penalties_per_day ?? 0);
        $dueDate    = $this->end_date;
        $settled    = $this->collected_at !== null;
        $asOf       = $settled ? $this->collected_at->copy()->startOfDay() : now()->startOfDay();

        $daysOverdue = 0;
        $daysLeft    = 0;

        if ($dueDate) {
            $due = $dueDate->copy()->startOfDay();
            // Cast: Carbon 3 returns a float here, and the view compares to 1.
            $daysOverdue = $asOf->gt($due) ? (int) $due->diffInDays($asOf) : 0;
            $daysLeft    = $asOf->lte($due) ? (int) $asOf->diffInDays($due) : 0;
        }

        $penalty = round($daysOverdue * $perDay, 2);

        return [
            'principal'    => $principal,
            'per_day'      => $perDay,
            'due_date'     => $dueDate,
            'days_overdue' => $daysOverdue,
            'days_left'    => $daysLeft,
            'penalty'      => $penalty,
            'total'        => round($principal + $penalty, 2),
            'is_overdue'   => $daysOverdue > 0,
            'settled'      => $settled,
            'as_of'        => $asOf,
        ];
    }

    // Scopes
    public function scopeApproved($query)    { return $query->where('status', 'approved'); }
    public function scopePending($query)     { return $query->where('status', 'pending'); }
    public function scopeProcessing($query)  { return $query->where('status', 'processing'); }
    public function scopeDenied($query)      { return $query->where('status', 'denied'); }
    public function scopeActiveCredit($query){ return $query->where('status', 'approved')->where('end_date', '>=', now()); }
    public function scopeForOrg($query, $orgId){ return $query->where('organization_id', $orgId); }
}
