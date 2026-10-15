<?php

namespace App\Models\Admin\Fee;

use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One particular of a student's own fee, in a school on Custom Fee
 * (App\Support\CustomFee): a name and an amount, set on that student alone.
 */
class CustomFeeItem extends Model
{
    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'fee_name',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function studentDetail(): BelongsTo
    {
        return $this->belongsTo(StudentDetail::class);
    }
}
