<?php

namespace App\Models\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminFeePayment extends Model
{
    protected $fillable = [
        'organization_id',
        'standard_id',
        'section_id',
        'student_detail_id',
        'super_admin_fee_structure_id',
        'installment_period',
        'amount',
        'academic_year',
        'payment_mode',
        'payment_date',
        'receipt_number',
        'remark',
        'is_paid',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'is_paid'      => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->receipt_number)) {
                $model->receipt_number = 'SARC-' . $model->organization_id
                    . '-' . now()->year
                    . '-' . str_pad(self::whereYear('created_at', now()->year)->count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

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

    public function studentDetail(): BelongsTo
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(SuperAdminFeeStructure::class, 'super_admin_fee_structure_id');
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeForYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }
}
