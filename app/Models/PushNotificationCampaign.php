<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationCampaign extends Model
{
    protected $fillable = [
        'title',
        'body',
        'audience_scope',
        'audience_role',
        'organization_id',
        'screen',
        'recipient_count',
        'device_count',
        'delivered',
        'sent_by',
    ];

    protected $casts = [
        'delivered' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
