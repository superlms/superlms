<?php

namespace App\Models\Student;

use App\Models\Admin\HomeWork;
use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Standard extends Model
{
    use HasCommonScopes;

    protected $fillable = ['name', 'code', 'organization_id', 'file_path', 'board', 'order', 'is_active'];

    /**
     * The order the Standard page lists classes in: each class's Order, then
     * the order the classes were added. Every class list and picker uses it.
     */
    public function scopeInClassOrder(Builder $query): Builder
    {
        return $query->orderBy($this->qualifyColumn('order'))->orderBy($this->qualifyColumn('id'));
    }

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
