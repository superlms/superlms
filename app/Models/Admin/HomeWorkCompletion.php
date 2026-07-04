<?php

namespace App\Models\Admin;

use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HomeWorkCompletion extends Model
{
    protected $table = 'home_work_completions';

    protected $fillable = [
        'organization_id',
        'home_work_id',
        'user_id',
        'student_detail_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function homework()
    {
        return $this->belongsTo(HomeWork::class, 'home_work_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(StudentDetail::class, 'student_detail_id');
    }
}
