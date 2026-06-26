<?php

namespace App\Models\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuperAdminFeeStructure extends Model
{
    protected $fillable = [
        'organization_id',
        'standard_id',
        'fee_type',
        'amount',
        'academic_year',
        'fee_label',
        'is_active',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SuperAdminFeePayment::class);
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeForYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
