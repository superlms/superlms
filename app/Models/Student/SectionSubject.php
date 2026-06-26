<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    protected $fillable = ['section_id', 'subject_id', 'standard_id', 'organization_id'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
