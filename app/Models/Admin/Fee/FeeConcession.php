<?php

namespace App\Models\Admin\Fee;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;

class FeeConcession extends Model
{
    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'standard_id',
        'section_id',
        'concession_type', // 'amount' | 'percent'
        'value',
        'fee_type',        // 'academic' | 'transport' | 'all'
        'reason',
        'academic_year',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /** Resolve this concession to a flat rupee discount against a base amount. */
    public function discountOn(float $base): float
    {
        return $this->concession_type === 'percent'
            ? round($base * ((float) $this->value) / 100, 2)
            : min((float) $this->value, $base);
    }
}
