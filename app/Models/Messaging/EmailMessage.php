<?php

namespace App\Models\Messaging;

use Illuminate\Database\Eloquent\Model;

/** An email a school sent through Messaging, with what it cost (App\Services\SchoolEmail). */
class EmailMessage extends Model
{
    protected $fillable = [
        'organization_id',
        'campaign_id',
        'user_id',
        'student_detail_id',
        'to_email',
        'subject',
        'dedupe_key',
        'status',        // sending | sent | failed
        'error',
        'billable',
        'rate',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'rate'     => 'decimal:2',
    ];
}
