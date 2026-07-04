<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HomeWork extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'standard_id', 'section_id', 'subject_id', 'title', 'description','file'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function completions()
    {
        return $this->hasMany(HomeWorkCompletion::class, 'home_work_id');
    }
}
