<?php

namespace App\Models\Student;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentSyllabus extends Model
{
    protected $fillable = [
        'organization_id',
        'standard_id',
        'section_id',
        'user_id',
        'subject_id',
        'name',
        'description',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
