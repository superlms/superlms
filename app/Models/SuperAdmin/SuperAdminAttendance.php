<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminAttendance extends Model
{
    protected $fillable = [
        'super_admin_employee_id', 'date', 'status', 'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(SuperAdminEmployee::class, 'super_admin_employee_id');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month]);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('super_admin_employee_id', $employeeId);
    }
}
