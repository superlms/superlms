<?php

namespace App\Models\Messaging;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One message a school sent to many — by WhatsApp or by email — and how far
 * it has got (App\Jobs\SendMessagingCampaign).
 */
class MessagingCampaign extends Model
{
    protected $fillable = [
        'organization_id',
        'channel',          // whatsapp | email
        'audience',         // students | teachers | everyone
        'standard_id',
        'section_id',
        'audience_label',
        'subject',
        'body',
        'recipients',
        'done',
        'sent',
        'failed',
        'skipped',
        'rate',
        'status',           // queued | sending | done
        'created_by',
        'finished_at',
    ];

    protected $casts = [
        'rate'        => 'decimal:2',
        'finished_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
