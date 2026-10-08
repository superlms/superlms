<?php

namespace App\Models\Student;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Support\Cascade;

class Chapter extends Model
{
    protected $fillable = ['organization_id', 'standard_id', 'section_id', 'user_id', 'subject_id', 'name', 'description', 'image_path', 'pdf_path', 'content_type', 'file_path', 'thumbnail', 'duration', 'order', 'is_published', 'is_free', 'metadata'];

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

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /** Deleted: its topics, questions and exam-syllabus rows (Cascade::children) goes with it. */
    protected static function booted(): void
    {
        static::deleting(fn (self $m) => Cascade::children($m->getTable(), (int) $m->getKey()));
    }
}
