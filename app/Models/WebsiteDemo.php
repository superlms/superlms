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
        'remark',
    ];
}
