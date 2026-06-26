<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermOfUse extends Model
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
