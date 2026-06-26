<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class DriverDetail extends Model
{
    use HasCommonScopes;

    protected $table = 'driver_details';

    protected $fillable = [
        'user_id',
        'organization_id',
        'image',
        'license_no',
        'vehicle_no',
        'vehicle_type',
        'phone',
        'address',
        'experience_years',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'experience_years' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function transportations()
    {
        return $this->hasMany(Transportation::class, 'driver_detail_id');
    }
}
