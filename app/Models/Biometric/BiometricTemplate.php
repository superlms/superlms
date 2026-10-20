<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/** A fingerprint enrolled on one machine, kept so the school's other machines get it too. */
class BiometricTemplate extends Model
{
    protected $fillable = ['organization_id', 'pin', 'fid', 'size', 'valid', 'template', 'from_device_id'];
}
