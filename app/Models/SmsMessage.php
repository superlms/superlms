<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One SMS sent for a school — see App\Services\SchoolSms. Its text is not kept (it has the password). */
class SmsMessage extends Model
{
    protected $table = 'sms_messages';

    protected $fillable = [
        'organization_id', 'feature', 'template', 'user_id', 'student_detail_id',
        'to_phone', 'provider_message_id', 'status', 'error', 'parts',
    ];
}
