<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Standard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionExamPaper extends Model
{
    protected $fillable = [
        'organization_id',
        'standard_id',
        'title',
        'file_path',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }
}
