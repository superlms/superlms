<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'standard_id',
        'section_id',
        'fee_type',
        'amount',
        'waiver_amount',
        'waiver_reason',
        'penalty_amount',
        'payment_mode',
        'payment_date',
        'remark',
        'submitted_by',
        'receipt_number',
        // 'dues' — an academic payment taken for Last Year Dues (App\Support\FeeDues).
        'applies_to',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'waiver_amount'  => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'payment_date'   => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            // A student who must first pay the platform fee in the app cannot have a fee taken (FeeAccess).
            \App\Support\FeeAccess::guardFeeWrite($model->student_detail_id, $model->organization_id);

            if (empty($model->receipt_number)) {
                $model->receipt_number = self::generateReceiptNumber($model->organization_id);
            }
        });
    }

    /**
     * The school's next RCT- number this year: one past the highest in use,
     * here or on a transport payment that kept its RCT- number when it moved
     * out of this table (never a count — see ReceiptNumber).
     */
    public static function generateReceiptNumber(int $orgId): string
    {
        return \App\Support\ReceiptNumber::next(
            "RCT-{$orgId}-" . date('Y') . '-',
            [(new self)->getTable(), (new \App\Models\Admin\TransportFeePayment)->getTable()]
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function studentDetail(): BelongsTo
    {
        return $this->belongsTo(StudentDetail::class);
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

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_detail_id', $studentId);
    }

    public function scopeForClass($query, int $standardId, ?int $sectionId = null)
    {
        $query->where('standard_id', $standardId);
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        return $query;
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }
}
