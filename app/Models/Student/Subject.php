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

    /**
     * Built-in icon for this subject, resolved from its name (see
     * App\Support\SubjectIcons). Subjects no longer carry an uploaded image,
     * so this is what every surface — web and mobile — should render.
     */
    public function iconKey(): string
    {
        return \App\Support\SubjectIcons::keyFor($this->name);
    }

    /** Absolute URL of that icon as an SVG, for the API / mobile apps. */
    public function iconUrl(): string
    {
        return route('subject.icon', ['key' => str_replace(' ', '-', $this->iconKey())]);
    }

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
