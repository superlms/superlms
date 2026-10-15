<?php

namespace App\Models\Admin\Fee;

use Illuminate\Database\Eloquent\Model;

/** Whether a school is on Custom Fee — turned on by the super admin (App\Support\CustomFee). */
class CustomFeeSetting extends Model
{
    protected $fillable = ['organization_id', 'enabled', 'updated_by'];

    protected $casts = ['enabled' => 'boolean'];
}
