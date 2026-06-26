<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeSettings extends Model
{
    protected $fillable = [
        'organization_id',
        'penalty_per_day',
        'cycle_type',
        'due_day_of_month',
        'is_active',
    ];

    protected $casts = [
        'penalty_per_day' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function getForOrg(int $orgId): self
    {
        return self::firstOrCreate(
            ['organization_id' => $orgId],
            [
                'penalty_per_day'  => 0,
                'cycle_type'       => 'monthly',
                'due_day_of_month' => 10,
                'is_active'        => true,
            ]
        );
    }
}
