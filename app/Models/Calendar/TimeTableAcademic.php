<?php

namespace App\Models\Calendar;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTableAcademic extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_table_id',
        'organization_id',
        'standard_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'batch_name',
        'group_type',
    ];

    public function timeTable()
    {
        return $this->belongsTo(TimeTable::class);
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

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
