<?php

namespace App\Models\Student;

use App\Models\Admin\Exam;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdmitCard extends Model
{
    protected $fillable = [
        'student_detail_id',
        'exam_id',
        'organization_id',
        'admit_card_number',
        'student_name',
        'father_name',
        'mother_name',
        'roll_number',
        'exam_roll_number',
        'standard_id',
        'section_id',
        'exam_name',
        'academic_year',
        'reporting_time',
        'exam_center',
        'exam_center_address',
        'instructions',
        'student_photo',
        'student_signature',
        'authorized_signature',
        'issue_date',
        'status',
        'qr_code',
        'seat_number',
        'room_number',
        'allowed_items',
        'prohibited_items',
        'created_by',
        'updated_by',
        'subjects',
        'printed_at',
        'issue_criteria',
    ];

    protected $casts = [
        'subjects' => 'array',
        'allowed_items' => 'array',
        'prohibited_items' => 'array',
        'issue_date' => 'date',
        'reporting_time' => 'datetime',
        'printed_at' => 'datetime',
    ];

    /** Never printed yet — the ones a fresh print run should produce. */
    public function scopeUnprinted($query)
    {
        return $query->whereNull('printed_at');
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * A new admit card number, unused: ADMIT + the school's first 3 letters +
     * the exam's first 3 + the year + a random number.
     *
     * The column is unique across every school, and that base is shared
     * widely — "F.A 1", "F.A 2", "F.A 3"… all give "F.A", schools whose names
     * start alike give the same 3 letters — so its 9,000 four-digit numbers
     * fill up, and a random pick that was already taken stopped the insert
     * ("Duplicate entry … admit_cards_admit_card_number_unique", 2026-10-14).
     * A taken number is tried again, and a crowded base moves on to longer
     * numbers; cards already issued keep theirs.
     */
    public static function generateAdmitCardNumber($organizationId, $examId)
    {
        $prefix   = 'ADMIT';
        $orgCode  = mb_strtoupper(mb_substr(str_replace(' ', '', Organization::find($organizationId)->name ?? 'ORG'), 0, 3));
        $year     = date('y');
        $examCode = mb_strtoupper(mb_substr(str_replace(' ', '', Exam::find($examId)->exam_name ?? 'EXAM'), 0, 3));
        $base     = "{$prefix}{$orgCode}{$examCode}{$year}";

        foreach ([[1000, 9999, 25], [100000, 999999, 25], [10000000, 99999999, 25]] as [$min, $max, $tries]) {
            for ($i = 0; $i < $tries; $i++) {
                $number = $base . random_int($min, $max);
                if (!static::withoutGlobalScopes()->where('admit_card_number', $number)->exists()) {
                    return $number;
                }
            }
        }

        // Never in practice: the time of day makes it unlike any other.
        return $base . now()->format('His') . random_int(100000, 999999);
    }
}
