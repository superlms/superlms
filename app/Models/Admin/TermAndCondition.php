<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class TermAndCondition extends Model
{
    protected $fillable = ['platform_logo', 'platform_name', 'company_name', 'company_cin', 'metadata', 'last_updated'];

    protected $casts = [
        'metadata' => 'array',
        'last_updated' => 'date'
    ];
}
