<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class TransferCertificate extends Model
{
    use HasCommonScopes;

    protected $table = 'transfer_certificates';

    protected $fillable = [
        'organization_id', 'student_detail_id', 'tc_no', 'book_no',
        'nationality', 'is_sc_st', 'last_class_studied', 'exam_last_taken',
        'whether_failed', 'subjects_studied', 'qualified_for_promotion',
        'fees_paid_upto', 'fee_concession', 'total_working_days', 'days_present',
        'is_ncc_scout', 'extra_activities', 'general_conduct',
        'application_date', 'issue_date', 'reason_for_leaving', 'remarks',
    ];

    protected $casts = [
        'is_sc_st'         => 'boolean',
        'application_date' => 'date',
        'issue_date'       => 'date',
    ];

    public function organization() { return $this->belongsTo(Organization::class); }

    public function student() { return $this->belongsTo(StudentDetail::class, 'student_detail_id'); }

    protected static function booted(): void
    {
        static::creating(function (TransferCertificate $tc) {
            if (empty($tc->tc_no)) {
                $year  = now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $tc->tc_no = 'TC-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
