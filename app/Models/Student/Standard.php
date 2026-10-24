<?php

namespace App\Models\Student;

use App\Models\Admin\HomeWork;
use App\Models\Organization;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Support\Cascade;
use App\Support\StudentNumbers;

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

    protected static function booted(): void
    {
        /** Deleted: the class's set-up and content, never a student's own records (Cascade::structure) goes with it. */
        static::deleting(fn (self $m) => Cascade::structure($m->getTable(), (int) $m->getKey()));

        // A roll is the class's code + a serial: a new code, and every roll of
        // the class takes it, the serial kept ("05007" → "12007"), wherever the
        // class is saved from — the panel, the admin app, the Super Admin.
        static::updated(function (self $m) {
            if ($m->wasChanged('code')) {
                StudentNumbers::recodeClass((int) $m->getKey(), $m->getOriginal('code'), $m->code);
            }
        });
    }
}
