<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentIdCard extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'student_detail_id',
        'card_number',
        'issue_date',
        'expiry_date',
        'status',
        'qr_code'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
