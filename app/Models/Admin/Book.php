<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['organization_id', 'standard_id', 'section_id', 'subject_id', 'title', 'book_logo', 'pdf_file', 'is_active'];

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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
