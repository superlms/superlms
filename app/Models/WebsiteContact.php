<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteContact extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'school_name',
        'subject',
        'description',
        'remark'
    ];
}
