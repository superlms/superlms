<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class Transportation extends Model
{
    use HasCommonScopes;

    protected $table = 'transportations';

    protected $fillable = [
        'organization_id',
        'route_name',
        'driver_detail_id',
        'pickup_time',
        'pickup_location',
        'drop_location',
        'stops',
        'monthly_fee',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'monthly_fee' => 'decimal:2',
        'capacity'    => 'integer',
        'stops'       => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function driver()
    {
        return $this->belongsTo(DriverDetail::class, 'driver_detail_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            StudentDetail::class,
            'transportation_students',
            'transportation_id',
            'student_detail_id'
        )->withTimestamps();
    }
}
