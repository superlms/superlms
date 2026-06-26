<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeIdCard extends Model
{
    protected $fillable = [
        'admin_employee_id',
        'user_id',
        'organization_id',
        'card_number',
        'issue_date',
        'expiry_date',
        'status',
        'qr_code',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function adminEmployee()
    {
        return $this->belongsTo(AdminEmployee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
