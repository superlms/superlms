<?php

namespace App\Models\Mcq;

use App\Models\Organization;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Topic;
use Illuminate\Database\Eloquent\Model;
use App\Support\Cascade;

class McqQuestion extends Model
{
    protected $fillable = ['organization_id', 'standard_id', 'section_id', 'chapter_id', 'topic_id', 'created_by', 'question_text', 'time_limit', 'is_active'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function options()
    {
        return $this->hasMany(McqOption::class, 'mcq_question_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(McqUserAnswer::class, 'mcq_question_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /** Deleted: its options and answers (Cascade::children) goes with it. */
    protected static function booted(): void
    {
        static::deleting(fn (self $m) => Cascade::children($m->getTable(), (int) $m->getKey()));
    }
}
