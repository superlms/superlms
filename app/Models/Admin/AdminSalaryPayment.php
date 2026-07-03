<?php

namespace App\Models\Admin;

use App\Models\Admin\AdminEmployee;
use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class AdminSalaryPayment extends Model
{
    use HasCommonScopes;

    protected $fillable = [
        'admin_employee_id',
        'organization_id',
        'month',
        'amount',
        'payment_mode',
        'paid_by',
        'status',
        'payment_date',
        'transaction_id',
        'remark',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee()
    {
        return $this->belongsTo(AdminEmployee::class, 'admin_employee_id');
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
