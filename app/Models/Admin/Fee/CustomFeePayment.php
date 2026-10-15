<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment against a student's Custom Fee (App\Support\CustomFee), with its
 * own receipt series — CFR-{school}-{year}-{00001} — apart from fee_payments.
 */
class CustomFeePayment extends Model
{
    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'standard_id',
        'section_id',
        'amount',
        'payment_mode',
        'payment_date',
        'submitted_by',
        'remark',
        'receipt_number',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->receipt_number)) {
                $model->receipt_number = self::nextReceiptNumber((int) $model->organization_id);
            }
        });
    }

    /** The next number in the school's series for this year (call inside a transaction). */
    public static function nextReceiptNumber(int $orgId): string
    {
        $prefix = 'CFR-' . $orgId . '-' . date('Y') . '-';
        $last   = self::where('receipt_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('receipt_number')
            ->value('receipt_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
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
}
