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
            if (empty($model->receipt_number)) {
                $model->receipt_number = self::generateReceiptNumber((int) $model->organization_id);
            }
        });
    }

    public static function generateReceiptNumber(int $orgId): string
    {
        $year = date('Y');
        $count = self::where('organization_id', $orgId)
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count();

        $sequence = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        return "TRCT-{$orgId}-{$year}-{$sequence}";
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
