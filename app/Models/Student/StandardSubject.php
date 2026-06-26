<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Model;

class StandardSubject extends Model
{
    protected $fillable = ['standard_id', 'subject_id', 'organization_id', 'is_mandatory'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
