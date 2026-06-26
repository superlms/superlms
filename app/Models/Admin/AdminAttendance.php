<?php

namespace App\Models\Admin;

use App\Models\Admin\AdminEmployee;
use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class AdminAttendance extends Model
{
    use HasCommonScopes;

    protected $fillable = [
        'admin_employee_id',
        'organization_id',
        'date',
        'status',
        'note',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee()
    {
        return $this->belongsTo(AdminEmployee::class, 'admin_employee_id');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month]);
    }
}
