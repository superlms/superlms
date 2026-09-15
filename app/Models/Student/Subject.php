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

    /**
     * Absolute URL of that icon for the API / mobile apps — as a PNG, since an
     * app's <Image> cannot draw SVG. The PNGs are the same tiles rendered once
     * into public/subject-icons (scripts/render-subject-icons.mjs); a key with
     * no PNG yet falls back to the SVG route.
     */
    public function iconUrl(): string
    {
        $slug = str_replace(' ', '-', $this->iconKey());

        return is_file(public_path("subject-icons/{$slug}.png"))
            ? asset("subject-icons/{$slug}.png")
            : route('subject.icon', ['key' => $slug]);
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

    /**
     * The subject's section names, each once. A section can be linked more
     * than once (a copy left under its old class after it moved), and two
     * sections can share a name — neither should read "SECTION A, SECTION A".
     */
    public function sectionNames(): string
    {
        return $this->sections->pluck('name')->unique()->implode(', ');
    }
}
