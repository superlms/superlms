<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Model;

/** One of a school's attendance machines, known by the serial number it sends with every call. */
class BiometricDevice extends Model
{
    protected $fillable = ['organization_id', 'serial_number', 'name', 'last_seen_at', 'last_ip', 'attlog_stamp', 'operlog_stamp'];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function commands()
    {
        return $this->hasMany(BiometricCommand::class);
    }

    /** Heard from in the last few minutes (it asks for work every few seconds while it is on). */
    public function online(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3));
    }
}
