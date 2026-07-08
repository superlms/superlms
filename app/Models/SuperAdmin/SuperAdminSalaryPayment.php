<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminSalaryPayment extends Model
{
    protected $fillable = [
        'super_admin_employee_id',
        'amount',
        'month',
        'payment_mode',
        'paid_by',
        'status',
        'payment_date',
        'transaction_id',
        'remark',
        'receipt_number',
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
                $model->receipt_number = 'SAL-' . now()->format('Ym')
                    . '-' . str_pad(self::whereMonth('created_at', now()->month)->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(SuperAdminEmployee::class, 'super_admin_employee_id');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->where('month', $month);
    }
}
