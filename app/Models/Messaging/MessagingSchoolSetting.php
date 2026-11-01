<?php

namespace App\Models\Messaging;

use Illuminate\Database\Eloquent\Model;

/** A school's own Messaging settings: email messaging on / off (off until the Super Admin turns it on). */
class MessagingSchoolSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'email_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
    ];
}
