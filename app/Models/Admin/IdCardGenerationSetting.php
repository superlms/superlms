<?php

namespace App\Models\Admin;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class IdCardGenerationSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'type',
        'auto_enabled',
        'expiry_date',
        'last_generated_at',
    ];

    protected $casts = [
        'auto_enabled'      => 'boolean',
        'expiry_date'       => 'date',
        'last_generated_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
