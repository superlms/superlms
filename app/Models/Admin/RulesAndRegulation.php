<?php

namespace App\Models\Admin;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class RulesAndRegulation extends Model
{
    protected $fillable = [
        'organization_id',
        'content'
    ];

    protected $casts = [
        'content' => 'array'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
