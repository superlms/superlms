<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One WhatsApp message the platform sent, and who pays for it — see
 * App\Services\SchoolWhatsApp.
 */
class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'organization_id',
        'feature',
        'template',
        'user_id',
        'student_detail_id',
        'to_phone',
        'dedupe_key',
        'wa_message_id',
        'status',
        'error',
        'billable',
        'rate',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'rate'     => 'decimal:2',
    ];
}
