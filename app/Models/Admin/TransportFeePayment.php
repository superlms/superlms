<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransportFeePayment extends Model
{
    protected $table = 'transport_fee_payments';

    protected $fillable = [
        'organization_id',
        'transportation_id',
        'student_detail_id',
        'amount',
        'payment_mode',
        'payment_date',
        'receipt_number',
        'academic_year',
        'remark',
        'submitted_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            // A student who must first pay the platform fee in the app cannot have a fee taken (FeeAccess).
            \App\Support\FeeAccess::guardFeeWrite($model->student_detail_id, $model->organization_id);

            if (empty($model->receipt_number)) {
                $model->receipt_number = self::generateReceiptNumber((int) $model->organization_id);
            }
        });
    }

    /** The school's next TRCT- number this year: one past the highest in use (never a count — see ReceiptNumber). */
    public static function generateReceiptNumber(int $orgId): string
    {
        return \App\Support\ReceiptNumber::next("TRCT-{$orgId}-" . date('Y') . '-', [(new self)->getTable()]);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function transportation()
    {
        return $this->belongsTo(Transportation::class);
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
