<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;

class CreditPolicy extends Model
{
    protected $fillable = [
        'title',
        'content',
        'image',
        'link',
        'document',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
