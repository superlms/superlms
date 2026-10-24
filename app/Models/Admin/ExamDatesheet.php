<?php

namespace App\Models\Admin;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use App\Support\Cascade;

class ExamDatesheet extends Model
{
    protected $table = 'exam_datesheets';

    protected $fillable = ['organization_id', 'exam_id', 'standard_id', 'section_id'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function papers()
    {
        // Papers sat side by side (one date and start) keep one order: the id's.
        return $this->hasMany(ExamDatesheetPaper::class)->orderBy('exam_date')->orderBy('start_time')->orderBy('id');
    }

    /** Deleted: its papers (Cascade::children) goes with it. */
    protected static function booted(): void
    {
        static::deleting(fn (self $m) => Cascade::children($m->getTable(), (int) $m->getKey()));
    }
}
