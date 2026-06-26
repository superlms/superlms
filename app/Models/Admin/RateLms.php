<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class RateLms extends Model
{
    use HasCommonScopes;
    protected $fillable = [
        'organization_id',
        'feedback',
        'rating',
        'status',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
