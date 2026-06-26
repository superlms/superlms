<?php

namespace App\Models\Admin;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolInfo extends Model
{
    protected $fillable = [
        'organization_id',
        'about_school',
        'website_info',
        'website_url',
        'school_mobile',
        'school_email',
        'school_address',
        'school_document_text',
        'usm_vision',
        'usm_mission',
        'usm_values',
        'usm_goals',
        'custom_sections',
    ];

    protected $casts = [
        'custom_sections' => 'array',
    ];

public function organization()
{
    return $this->belongsTo(\App\Models\Organization::class, 'organization_id');
}

    public function managementTeam()
    {
        return $this->hasMany(SchoolManagementTeam::class)->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(SchoolDocument::class)->orderBy('sort_order');
    }


}
