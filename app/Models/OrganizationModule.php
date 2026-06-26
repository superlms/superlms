<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationModule extends Model
{
    protected $table = 'module_organization';

    protected $fillable = [
        'organization_id',
        'module_key',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
