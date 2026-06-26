<?php

namespace App\Models\Teacher;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }
    
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
