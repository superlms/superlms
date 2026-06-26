<?php

namespace App\Models\Mcq;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class McqUserAnswer extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'mcq_question_id', 'mcq_option_id', 'time_taken', 'is_correct'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function options()
    {
        return $this->hasMany(McqOption::class, 'mcq_question_id');
    }

    public function question()
    {
        return $this->belongsTo(McqQuestion::class, 'mcq_question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(McqOption::class, 'mcq_option_id');
    }
}
