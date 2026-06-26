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
        'subjects'
    ];

    protected $casts = [
        'subjects' => 'array',
        'allowed_items' => 'array',
        'prohibited_items' => 'array',
        'issue_date' => 'date',
        'reporting_time' => 'datetime',
    ];

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

    // Generate unique admit card number
    public static function generateAdmitCardNumber($organizationId, $examId)
    {
        $prefix = 'ADMIT';
        $orgCode = strtoupper(substr(str_replace(' ', '', Organization::find($organizationId)->name ?? 'ORG'), 0, 3));
        $year = date('y');
        $examCode = strtoupper(substr(str_replace(' ', '', Exam::find($examId)->exam_name ?? 'EXAM'), 0, 3));
        $random = mt_rand(1000, 9999);

        return "{$prefix}{$orgCode}{$examCode}{$year}{$random}";
    }
}
