<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteVisit extends Model
{
    /** Only created_at is tracked (a visit is a point-in-time event). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'path',
        'page',
        'visitor_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
