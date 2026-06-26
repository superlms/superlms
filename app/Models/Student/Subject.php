<?php

namespace App\Models\Student;

use App\Models\Teacher\TeacherSubject;
use App\Models\User;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasCommonScopes;

    protected $fillable = ['name', 'code', 'organization_id', 'description', 'is_active', 'image', 'detail_image'];

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_subject');
    }

    public function standards()
    {
        return $this->belongsToMany(Standard::class, 'standard_subjects');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_subjects')
            ->withPivot(['standard_id', 'organization_id']);
    }
}
