<?php

namespace App\Models\Mcq;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class McqOption extends Model
{
    protected $fillable = ['organization_id', 'mcq_question_id', 'option_text', 'is_correct'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function mcqQuestion()
    {
        return $this->belongsTo(McqQuestion::class);
    }
}
