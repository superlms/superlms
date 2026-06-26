<?php

namespace App\Models\Student;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['standard_id', 'name', 'code', 'image', 'description', 'is_active', 'organization_id'];

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'section_subjects');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
