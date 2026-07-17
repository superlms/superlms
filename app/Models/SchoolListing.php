<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolListing extends Model
{
    protected $fillable = [
        'location',
        'logo',
        'name',
        'email',
        'mobile',
        'address',
        'classes',
        'no_of_students',
        'avg_fee',
        'status',
        'remark',
        'payment_type',
        'payment_amount',
    ];

    protected $casts = [
        'no_of_students' => 'integer',
        'avg_fee'        => 'decimal:2',
        'payment_amount' => 'decimal:2',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    /** payment_type value => human label */
    public const PAYMENT_TYPES = [
        'monthly'       => 'Monthly',
        'one_time'      => 'One Time',
        'student_based' => 'Student Based',
    ];

    public function getPaymentTypeLabelAttribute(): ?string
    {
        return $this->payment_type ? (self::PAYMENT_TYPES[$this->payment_type] ?? $this->payment_type) : null;
    }
}
