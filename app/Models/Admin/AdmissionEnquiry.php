<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionEnquiry extends Model
{
    /** Sent from the school website's Online Admission form (App\Http\Controllers\SchoolAdmissionController). */
    public const SOURCE_WEBSITE = 'website';

    protected $fillable = [
        'organization_id',
        'student_name',
        'email',
        'mobile',
        'guardian_name',
        'address',
        'standard_id',
        'stream',
        'admission_fee',
        'collected_amount',
        'payment_mode',
        'collected_by',
        'fee_collected_at',
        'total_marks',
        'obtained_marks',
        'remarks',
        'result_pdf',
        'documents',
        'status',
        // 'website' for one sent from the school's website, null for one added here.
        'source',
        // The website form's answers, in its order: [{label, value}].
        'details',
    ];

    protected $casts = [
        'admission_fee'    => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'fee_collected_at' => 'date',
        'total_marks'      => 'decimal:2',
        'obtained_marks'   => 'decimal:2',
        'documents'        => 'array',
        'details'          => 'array',
    ];

    public function fromWebsite(): bool
    {
        return $this->source === self::SOURCE_WEBSITE;
    }

    /** The website form's answers as [label => value], in its order. */
    public function detailRows(): array
    {
        $out = [];
        foreach (is_array($this->details) ? $this->details : [] as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label !== '' && $value !== '') {
                $out[$label] = $value;
            }
        }

        return $out;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUpdated($query)
    {
        return $query->where('status', 'updated');
    }

    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }
}
