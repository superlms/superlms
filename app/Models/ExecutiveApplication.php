<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutiveApplication extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'mobile',
        'address',
        'qualification',
        'description',
        'document_path',
        'status',
        'admin_remark',
    ];
}
