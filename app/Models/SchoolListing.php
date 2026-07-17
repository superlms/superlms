<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolListing extends Model
{
    protected $fillable = [
        'location',
        'logo',
        'name',
        'email',
        'mobile',
        'address',
        'classes',
        'no_of_students',
        'avg_fee',
        'status',
        'remark',
    ];

    protected $casts = [
        'no_of_students' => 'integer',
        'avg_fee'        => 'decimal:2',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected'];
}
