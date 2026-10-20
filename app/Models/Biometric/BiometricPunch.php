<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/** One punch as a machine sent it, and what it did to the day (result). */
class BiometricPunch extends Model
{
    protected $fillable = ['organization_id', 'biometric_device_id', 'pin', 'holder_type', 'holder_id', 'punched_at', 'verify', 'result'];

    protected $casts = ['punched_at' => 'datetime'];
}
