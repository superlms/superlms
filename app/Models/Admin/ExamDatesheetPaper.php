<?php

namespace App\Models\Admin;

use App\Models\Student\Subject;
use Illuminate\Database\Eloquent\Model;

class ExamDatesheetPaper extends Model
{
    protected $table = 'exam_datesheet_papers';

    protected $fillable = [
        'exam_datesheet_id', 'subject_id', 'exam_date',
        'start_time', 'end_time', 'shift',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function datesheet()
    {
        return $this->belongsTo(ExamDatesheet::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
