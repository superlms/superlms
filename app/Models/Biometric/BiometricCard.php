<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/** The 125 kHz card a student or teacher carries — tapped on a USB reader to assign it. */
class BiometricCard extends Model
{
    protected $fillable = ['organization_id', 'holder_type', 'holder_id', 'card_number', 'assigned_by'];
}
