<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
     use HasCommonScopes;

    protected $table = 'certificates';

    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'type',
        'event_name',
        'issued_by',
        'issued_by_designation',
        'description',
        'issued_date',
        'certificate_no',
    ];

    protected $casts = [
        'issued_date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function student()
    {
        return $this->belongsTo(StudentDetail::class, 'student_detail_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'achievement'   => 'Achievement',
            'participation' => 'Participation',
            default         => ucfirst($this->type),
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $cert) {
            if (empty($cert->certificate_no)) {
                $year   = now()->format('Y');
                $count  = static::whereYear('created_at', $year)->count() + 1;
                $prefix = $cert->type === 'achievement' ? 'ACH' : 'PAR';
                $cert->certificate_no = "{$prefix}-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
