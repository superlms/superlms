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
        'audience_roles',
        'organization_id',
        'screen',
        'recipient_count',
        'device_count',
        'web_count',
        'device_breakdown',
        'delivered',
        'sent_by',
    ];

    protected $casts = [
        'delivered'        => 'boolean',
        'audience_roles'   => 'array',
        'device_breakdown' => 'array',
    ];

    /** Human labels for the role keys stored in audience_roles. */
    public const ROLE_LABELS = [
        'students' => 'Students',
        'teachers' => 'Teachers',
        'admins'   => 'Admins',
    ];

    /** "Students, Teachers" — falls back to the legacy single role for old rows. */
    public function audienceRolesLabel(): string
    {
        $roles = $this->audience_roles ?: [];

        if (empty($roles)) {
            return match ($this->audience_role) {
                'students' => 'Students',
                'teachers' => 'Teachers',
                'both'     => 'Students, Teachers',
                default    => ucfirst((string) $this->audience_role),
            };
        }

        return collect($roles)
            ->map(fn ($r) => self::ROLE_LABELS[$r] ?? ucfirst($r))
            ->implode(', ');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
