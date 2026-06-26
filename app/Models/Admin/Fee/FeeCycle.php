<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeCycle extends Model
{
    protected $fillable = [
        'organization_id',
        'fee_type',
        'payment_serial',
        'start_date',
        'end_date',
        'due_date',
        'penalty_per_day',
        'fee_percent',
        'amount',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'due_date'        => 'date',
        'penalty_per_day' => 'decimal:2',
        'fee_percent'     => 'decimal:2',
        'amount'          => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
