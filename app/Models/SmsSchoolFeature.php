<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An SMS a school has turned on (or off) — see App\Services\SchoolSms. */
class SmsSchoolFeature extends Model
{
    protected $table = 'sms_school_features';

    protected $fillable = ['organization_id', 'feature', 'enabled', 'updated_by'];

    protected $casts = ['enabled' => 'boolean'];
}
