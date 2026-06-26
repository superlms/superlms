<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TeacherIdCard extends Model
{
    protected $fillable = [
        'teacher_detail_id',
        'user_id',
        'organization_id',
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

    public function teacherDetail()
    {
        return $this->belongsTo(TeacherDetail::class);
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
