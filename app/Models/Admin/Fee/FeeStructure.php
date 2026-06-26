<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    protected $fillable = [
        'organization_id',
        'standard_id',
        'section_id',
        'fee_name',
        'amount',
        'fee_type',
        'academic_year',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function scopeAcademic($query)
    {
        return $query->where('fee_type', 'academic');
    }

    public function scopeTransport($query)
    {
        return $query->where('fee_type', 'transport');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, int $standardId, ?int $sectionId = null)
    {
        $query->where('standard_id', $standardId);

        if ($sectionId) {
            $query->where(function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)->orWhereNull('section_id');
            });
        }

        return $query;
    }
}
