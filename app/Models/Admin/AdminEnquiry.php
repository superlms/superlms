<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class AdminEnquiry extends Model
{
    use HasCommonScopes;
    protected $fillable = ['organization_id', 'full_name', 'type', 'email', 'mobile_number', 'description'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
