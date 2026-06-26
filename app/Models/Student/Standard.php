<?php

namespace App\Models\Student;

use App\Models\Admin\HomeWork;
use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class Standard extends Model
{
    use HasCommonScopes;

    protected $fillable = ['name', 'code', 'organization_id', 'file_path', 'board', 'order', 'is_active'];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'standard_subjects')
            ->withPivot('is_mandatory')
            ->withTimestamps();
    }

    public function studentDetails()
    {
        return $this->hasMany(StudentDetail::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function homeworks()
    {
        return $this->hasMany(HomeWork::class);
    }
}
