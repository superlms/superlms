<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteDemo extends Model
{
    protected $fillable = [
        'full_name',
        'school_name',
        'phone',
        'email',
        'city',
        'no_of_students',
        'role',
        'preferred_date',
        'preferred_time',
        'remark',
    ];

    protected $casts = [
        'preferred_date' => 'date',
    ];
}
