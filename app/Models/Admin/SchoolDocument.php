<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class SchoolDocument extends Model
{
    protected $fillable = [
        'school_info_id',
        'title',
        'file_path',
        'file_type',
        'sort_order'
    ];

    public function schoolInfo()
    {
        return $this->belongsTo(SchoolInfo::class);
    }
}
