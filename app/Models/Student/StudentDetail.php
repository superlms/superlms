<?php

namespace App\Models\Student;

use App\Models\Admin\ExamCopy;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\Transportation;
use App\Models\Organization;
use App\Models\User;
use App\Support\StudentSection;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;
use App\Support\Cascade;

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

    protected static function booted(): void
    {
        // Deleted: every record of the student's, and of their login's, goes with it (Cascade::student).
        static::deleting(fn (self $detail) => Cascade::student((int) $detail->getKey(), $detail->user_id ? (int) $detail->user_id : null));

        // Whoever saves a student — the panel, the admin or teacher app, the
        // Super Admin — the section is one of their class's own: none picked
        // in a class with a single section puts them in it, and another
        // class's section becomes this class's of the same name
        // (StudentSection has the rule).
        static::saving(function (self $detail) {
            if (! $detail->standard_id) {
                return;
            }
            if ($detail->exists && ! $detail->isDirty(['standard_id', 'section_id']) && $detail->section_id) {
                return;
            }
            $detail->section_id = StudentSection::resolve($detail->standard_id, $detail->section_id);
        });
    }

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
