<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyPolicy extends Model
{
    protected $fillable = [
        'metadata',
        'last_updated',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'last_updated' => 'date',
    ];
}
