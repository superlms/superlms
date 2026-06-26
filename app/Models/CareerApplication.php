<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerApplication extends Model
{
    protected $fillable = [
        'job_role',
        'full_name',
        'email',
        'mobile',
        'address',
        'qualification',
        'experience',
        'description',
        'document_path',
        'status',
    ];
}
