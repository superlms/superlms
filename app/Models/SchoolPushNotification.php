<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A push notification a school's admin sent to its students and teachers (App\Livewire\Admin\PushNotification). */
class SchoolPushNotification extends Model
{
    public const AUDIENCES = [
        'students' => 'Students',
        'teachers' => 'Teachers',
        'everyone' => 'Students and teachers',
        'selected' => 'Pick by name',
    ];

    protected $fillable = [
        'organization_id',
        'title',
        'body',
        'audience',
        'standard_id',
        'section_id',
        'audience_label',
        'user_ids',
        'recipient_count',
        'device_count',
        'web_count',
        'device_breakdown',
        'delivered',
        'sent_by',
    ];

    protected $casts = [
        'user_ids'         => 'array',
        'device_breakdown' => 'array',
        'delivered'        => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
