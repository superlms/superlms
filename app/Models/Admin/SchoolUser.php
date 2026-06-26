<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SchoolUser extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'employee_id',
        'designation',
        'department',
        'phone',
        'address',
        'profile_picture',
        'is_active',
        'image',
        'alternate_mobile_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
