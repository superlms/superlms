<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An enquiry submitted from a school's public website contact form. */
class SchoolWebsiteEnquiry extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
