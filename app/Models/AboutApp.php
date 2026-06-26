<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutApp extends Model
{
    protected $fillable = [
        'heading',
        'sub_heading',
        'content',
        'logo',
        'contact_details',
        'address',
        'core_team',
        'social_media',
        'documents',
    ];

    protected $casts = [
        'content' => 'array',
        'contact_details' => 'array',
        'core_team' => 'array',
        'social_media' => 'array',
        'documents' => 'array',
    ];
}
