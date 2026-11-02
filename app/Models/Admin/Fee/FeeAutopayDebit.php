<?php

namespace App\Models\Admin\Fee;

use App\Models\PaymentTransaction;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;

/**
 * One installment taken on an Autopay mandate: told to the parent a day
 * ahead (PhonePe's pre-debit notification), then debited. It is paid through
 * a PaymentTransaction, so a completed debit is an ordinary online fee
 * payment with its receipt.
 */
class FeeAutopayDebit extends Model
{
    public const STATUSES = [
        'notifying' => 'Telling the parent',
        'notified'  => 'Parent told',
        'debiting'  => 'Debiting',
        'completed' => 'Paid',
        'failed'    => 'Failed',
        'skipped'   => 'Skipped — already paid',
    ];

    protected $fillable = [
        'mandate_id',
        'organization_id',
        'student_detail_id',
        'fee_cycle_id',
        'due_date',
        'label',
        'amount',
        'merchant_order_id',
        'payment_transaction_id',
        'status',
        'failure_reason',
        'notified_at',
        'valid_after',
        'executed_at',
        'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'amount'       => 'decimal:2',
        'notified_at'  => 'datetime',
        'valid_after'  => 'datetime',
        'executed_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function mandate()
    {
        return $this->belongsTo(FeeAutopayMandate::class, 'mandate_id');
    }

    public function student()
    {
        return $this->belongsTo(StudentDetail::class, 'student_detail_id');
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
