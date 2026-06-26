<?php

namespace App\Models\Student;

use App\Models\Admin\ExamCopy;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\Transportation;
use App\Models\Organization;
use App\Models\User;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    use HasCommonScopes;
    protected $fillable = [
        'user_id',
        'standard_id',
        'section_id',
        'full_name',
        'father_name',
        'mother_name',
        'email',
        'dob',
        'gender',
        'religion',
        'local_address',
        'permanent_address',
        'city',
        'state',
        'pincode',
        'admission_no',
        'date_of_admission',
        'roll_no',
        'board',
        'aadhar_no',
        'phone',
        'image',
        'organization_id',
        'transportation_required',
        'appar_id',
        'registration_number'
    ];

    protected $casts = [
        'date_of_admission' => 'date',
        'dob' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function attendance()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function idCard()
    {
        return $this->hasOne(StudentIdCard::class, 'student_detail_id');
    }

    public function idCards()
    {
        return $this->hasMany(StudentIdCard::class, 'student_detail_id');
    }

    public function admitCards()
    {
        return $this->hasMany(AdmitCard::class, 'student_detail_id');
    }

    public function examCopies()
    {
        return $this->hasMany(ExamCopy::class, 'student_detail_id');
    }

    public function transportations()
    {
        return $this->belongsToMany(
            Transportation::class,
            'transportation_students',
            'student_detail_id',
            'transportation_id'
        )->withTimestamps();
    }

    public function activeTransportation()
    {
        return $this->transportations()->where('is_active', true)->first();
    }

    public function studentAttendances()
    {
        return $this->hasMany(StudentAttendance::class, 'student_detail_id');
    }
}
