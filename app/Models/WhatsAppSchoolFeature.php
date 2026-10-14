<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A paid WhatsApp message a school has turned on (or off) — see App\Services\SchoolWhatsApp. */
class WhatsAppSchoolFeature extends Model
{
    protected $table = 'whatsapp_school_features';

    protected $fillable = ['organization_id', 'feature', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
