<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuperAdminEmployee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'designation',
        'type',
        'salary',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_holder_name',
        'bank_branch',
        'bank_ifsc',
        'photo',
        'is_active',
        'joining_date',
    ];

    protected $casts = [
        'salary'       => 'decimal:2',
        'is_active'    => 'boolean',
        'joining_date' => 'date',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(SuperAdminAttendance::class, 'super_admin_employee_id');
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SuperAdminSalaryPayment::class, 'super_admin_employee_id');
    }

    public function getAttendanceForMonth(string $month): \Illuminate\Database\Eloquent\Collection
    {
        return $this->attendances()
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])
            ->get();
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'teacher'    => 'blue',
            'management' => 'purple',
            'driver'     => 'amber',
            default      => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'teacher'    => 'academic-cap',
            'management' => 'briefcase',
            'driver'     => 'truck',
            default      => 'user',
        };
    }
}
