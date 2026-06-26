<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['organization_id', 'exam_name', 'term', 'academic_year', 'start_date', 'end_date', 'description', 'is_published', 'exam_type', 'total_marks', 'passing_marks', 'created_by', 'updated_by', 'status'];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
    ];

    public function Organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class);
    }
}
