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

    /**
     * Another book still uses this file — a book added for several sections at
     * once is a row a section sharing one uploaded PDF, so the file is taken
     * off storage only with the last of them.
     */
    public static function fileShared(?string $url, int $exceptId): bool
    {
        return $url !== null && $url !== ''
            && static::where('id', '!=', $exceptId)
                ->where(fn ($q) => $q->where('pdf_file', $url)->orWhere('book_logo', $url))
                ->exists();
    }

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
