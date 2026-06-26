<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class ContactAdminTeacher extends Model
{
     use HasCommonScopes;
    protected $fillable = ['teacher_detail_id', 'user_id', 'organization_id', 'topic', 'teacher_query', 'image', 'admin_text', 'admin_reply'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacherDetail()
    {
        return $this->belongsTo(TeacherDetail::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
