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
        'total_amount',
        'installment_frequency',
        'academic_year',
        'fee_label',
        'is_active',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    /** Installments per year for this structure's frequency (one_time only). */
    public function installmentDivisor(): int
    {
        return match ($this->installment_frequency) {
            'monthly'   => 12,
            'quarterly' => 4,
            default     => 1,
        };
    }

    /**
     * The list of periods this one-time fee is split across, each with a
     * stable `key` (stored on payments as `installment_period`) and a
     * human `label`. Academic year runs April → March.
     */
    public function installmentPeriods(): array
    {
        [$startYear, $endYear] = array_pad(array_map('intval', explode('-', (string) $this->academic_year)), 2, 0);

        return match ($this->installment_frequency) {
            'monthly' => collect(range(4, 15))->map(function (int $m) use ($startYear, $endYear) {
                $month = $m > 12 ? $m - 12 : $m;
                $year  = $m > 12 ? $endYear : $startYear;

                return [
                    'key'   => sprintf('%04d-%02d', $year, $month),
                    'label' => \Illuminate\Support\Carbon::create($year, $month, 1)->format('M Y'),
                ];
            })->all(),
            'quarterly' => [
                ['key' => $startYear . '-Q1', 'label' => "Q1 (Apr–Jun {$startYear})"],
                ['key' => $startYear . '-Q2', 'label' => "Q2 (Jul–Sep {$startYear})"],
                ['key' => $startYear . '-Q3', 'label' => "Q3 (Oct–Dec {$startYear})"],
                ['key' => $startYear . '-Q4', 'label' => "Q4 (Jan–Mar {$endYear})"],
            ],
            default => [
                ['key' => (string) $this->academic_year, 'label' => "FY {$this->academic_year}"],
            ],
        };
    }

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
