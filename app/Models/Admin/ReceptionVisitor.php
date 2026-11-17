<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Admin → More → Reception: one visit to the school — who, when, to meet whom, why, and when they left. */
class ReceptionVisitor extends Model
{
    /** What people come for; "Other" says it in the details. */
    public const PURPOSES = [
        'Admission Enquiry',
        'Fee Payment',
        'Meet Teacher',
        'Meet Principal',
        'Parent Meeting',
        'Student Pickup',
        'Interview',
        'Official Work',
        'Delivery',
        'Other',
    ];

    protected $fillable = [
        'organization_id', 'name', 'mobile', 'email', 'whom_to_meet',
        'purpose', 'details', 'in_at', 'out_at', 'added_by',
    ];

    protected $casts = [
        'in_at'  => 'datetime',
        'out_at' => 'datetime',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /** "1 h 25 m" between coming and leaving, or null while still inside. */
    public function stayed(): ?string
    {
        if (!$this->out_at || !$this->in_at) {
            return null;
        }
        $minutes = max(0, (int) $this->in_at->diffInMinutes($this->out_at));

        return $minutes >= 60
            ? intdiv($minutes, 60) . ' h' . ($minutes % 60 ? ' ' . ($minutes % 60) . ' m' : '')
            : $minutes . ' m';
    }
}
