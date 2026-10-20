<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/** Something a machine is to do (add a user with their card, a fingerprint), handed over when it next asks. */
class BiometricCommand extends Model
{
    protected $fillable = ['biometric_device_id', 'command', 'status', 'return_code', 'sent_at', 'done_at'];

    protected $casts = ['sent_at' => 'datetime', 'done_at' => 'datetime'];
}
